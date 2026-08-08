<?php

namespace App\Services;

use App\Models\WorkCategory;
use App\Models\WorkSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Random-rate batch approval of pending task applications.
 *
 * Runs every 3 hours. For each category with batch approval switched on, pending
 * applications are grouped by worker, and a proportion of each worker's group is
 * approved at random.
 *
 * THE RATE IS DRAWN ONCE PER WORKER, PER CATEGORY, PER DAY
 *
 * Not per run. A rate redrawn eight times a day would make "your approval rate is
 * 40-70%" describe nothing a worker could ever observe, and would let the same
 * person be treated generously at 9am and harshly at noon for no reason. The draw
 * is stored, so it is also answerable: when someone asks why they got 2 of 5, there
 * is a row saying the rate was 47% that day.
 *
 * REJECTIONS DO NOT REFUND THE FEE
 *
 * This is a deliberate decision by the platform owner and it is why this service
 * writes the rejection itself rather than calling TaskReviewService::rejectApplication(),
 * which refunds. Routing batch rejections through that method would silently refund
 * every lottery loser and the no-refund decision would never take effect — the sort
 * of bug that looks like nothing and quietly reverses a policy.
 *
 * Rejected applications still free their slot, exactly as an admin rejection does,
 * so the task stays open to other workers.
 */
class BatchApplicationApprovalService
{
    /**
     * @return array{categories:int, considered:int, approved:int, rejected:int}
     */
    public function run(): array
    {
        $stats = ['categories' => 0, 'considered' => 0, 'approved' => 0, 'rejected' => 0];

        $categories = WorkCategory::where('status', 1)
            ->where('batch_approval_enabled', true)
            ->get();

        foreach ($categories as $category) {
            $result = $this->runCategory($category);

            if ($result['considered'] > 0) {
                $stats['categories']++;
                $stats['considered'] += $result['considered'];
                $stats['approved']   += $result['approved'];
                $stats['rejected']   += $result['rejected'];
            }
        }

        return $stats;
    }

    /**
     * @return array{considered:int, approved:int, rejected:int}
     */
    public function runCategory(WorkCategory $category): array
    {
        $pending = WorkSubmission::where('application_status', WorkSubmission::APP_APPLIED)
            ->whereHas('work', fn ($q) => $q->where('category_id', $category->id))
            ->get()
            ->groupBy('worker_id');

        $considered = 0;
        $approved   = 0;
        $rejected   = 0;

        foreach ($pending as $workerId => $group) {
            $rate = $this->rateFor((int) $workerId, $category);

            // round(), so a rate of 60% on 5 applications approves 3 rather than
            // truncating to 2. Over many runs the observed rate then sits close to
            // the drawn one instead of consistently below it.
            $target = (int) round($group->count() * ($rate / 100));

            // shuffle() before slicing: without it, approval would follow insertion
            // order and the earliest applications would win every time, which is a
            // queue wearing a lottery's clothes.
            $shuffled  = $group->shuffle();
            $toApprove = $shuffled->take($target);
            $toReject  = $shuffled->slice($target);

            foreach ($toApprove as $submission) {
                if ($this->approve($submission)) {
                    $approved++;
                }
            }

            foreach ($toReject as $submission) {
                if ($this->reject($submission)) {
                    $rejected++;
                }
            }

            $considered += $group->count();

            $this->recordOutcome((int) $workerId, $category, $group->count(), $toApprove->count());
        }

        return ['considered' => $considered, 'approved' => $approved, 'rejected' => $rejected];
    }

    /**
     * The worker's rate for this category today, drawn once and reused.
     */
    public function rateFor(int $userId, WorkCategory $category): int
    {
        $min = (int) $category->min_approval_rate;
        $max = (int) $category->max_approval_rate;

        // A category configured backwards would otherwise make random_int() throw.
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        $today = now()->toDateString();

        $existing = DB::table('application_approval_draws')
            ->where('user_id', $userId)
            ->where('category_id', $category->id)
            ->where('draw_date', $today)
            ->value('rate');

        if ($existing !== null) {
            return (int) $existing;
        }

        $rate = random_int($min, $max);

        try {
            DB::table('application_approval_draws')->insert([
                'user_id'     => $userId,
                'category_id' => $category->id,
                'draw_date'   => $today,
                'rate'        => $rate,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Another run drew first. Use theirs rather than overwriting: two rates
            // for one worker on one day is exactly what the unique index prevents.
            return (int) DB::table('application_approval_draws')
                ->where('user_id', $userId)
                ->where('category_id', $category->id)
                ->where('draw_date', $today)
                ->value('rate');
        }

        return $rate;
    }

    private function approve(WorkSubmission $submission): bool
    {
        return DB::transaction(function () use ($submission) {
            $locked = WorkSubmission::whereKey($submission->id)->lockForUpdate()->first();

            // Re-checked under the lock: an admin may have decided this one by hand
            // between the query above and now, and their decision must win.
            if (! $locked || $locked->application_status !== WorkSubmission::APP_APPLIED) {
                return false;
            }

            $locked->update([
                'application_status' => WorkSubmission::APP_APPROVED,
                'delivery_status'    => WorkSubmission::DEL_NOT_STARTED,
                'annotate_code'      => $this->generateAnnotateCode(),
                'approved_by_batch'  => true,
                'task_delivered_at'  => now(),
            ]);

            ActivityLogger::log('application.batch_approved', $locked, [
                'worker_id' => $locked->worker_id,
                'work_id'   => $locked->work_id,
            ]);

            return true;
        });
    }

    private function reject(WorkSubmission $submission): bool
    {
        return DB::transaction(function () use ($submission) {
            $locked = WorkSubmission::whereKey($submission->id)->lockForUpdate()->first();

            if (! $locked || $locked->application_status !== WorkSubmission::APP_APPLIED) {
                return false;
            }

            // Written here rather than via TaskReviewService::rejectApplication(),
            // which refunds the fee. See the class docblock.
            $locked->update([
                'application_status' => WorkSubmission::APP_REJECTED,
                'reviewed_at'        => now(),
                'admin_note'         => 'Not selected in this round. You can apply again.',
            ]);

            ActivityLogger::log('application.batch_rejected', $locked, [
                'worker_id'  => $locked->worker_id,
                'work_id'    => $locked->work_id,
                'fee_paid'   => (float) $locked->fee_paid,
                'refunded'   => false,
            ]);

            return true;
        });
    }

    /**
     * Codes are read aloud, retyped and pasted into chat, so the alphabet excludes
     * characters that are read wrong: 0/O and 1/I/L.
     */
    private function generateAnnotateCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

        do {
            $code = 'AN-';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (WorkSubmission::where('annotate_code', $code)->exists());

        return $code;
    }

    private function recordOutcome(int $userId, WorkCategory $category, int $considered, int $approved): void
    {
        DB::table('application_approval_draws')
            ->where('user_id', $userId)
            ->where('category_id', $category->id)
            ->where('draw_date', now()->toDateString())
            ->update([
                'considered' => DB::raw('considered + ' . $considered),
                'approved'   => DB::raw('approved + ' . $approved),
                'updated_at' => now(),
            ]);
    }
}
