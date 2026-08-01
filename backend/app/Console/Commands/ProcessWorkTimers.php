<?php

namespace App\Console\Commands;

use App\Models\Work;
use App\Models\WorkSubmission;
use App\Services\ApplicationException;
use App\Services\CoinService;
use App\Services\TaskReviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWorkTimers extends Command
{
    protected $signature   = 'jobstation:process-timers';
    protected $description = 'Expire tasks past their deadline, auto-approve unreviewed deliveries, cancel abandoned assignments';

    public function __construct(protected TaskReviewService $reviews)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->expireWorks();
        $this->autoApproveDeliveries();
        $this->cancelAbandoned();

        $this->info('Timers processed at ' . now()->toDateTimeString());
        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // 1. Tasks whose expires_at has passed
    // -------------------------------------------------------------------------

    private function expireWorks(): void
    {
        $expired = Work::where('work_status', 1)
            ->where('approval_status', 1)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $work) {
            DB::transaction(function () use ($work) {
                $work->update(['work_status' => 2]);

                // Only user-posted tasks were funded from a user balance, so only
                // those get an unspent-budget refund. Admin-posted tasks are the
                // platform's own money and have nothing to return.
                if ($work->poster_type !== 2) {
                    return;
                }

                $spent  = $work->approvedSubmissions()->count() * (float) $work->coins_per_worker;
                $refund = (float) $work->total_coins - $spent;

                if ($refund > 0 && ($poster = $work->poster)) {
                    CoinService::credit(
                        $poster,
                        $refund,
                        $work->slug,
                        'work_refund',
                        'Refund: expired task — ' . $work->title
                    );
                }
            });

            $this->line("  Expired task #{$work->id}: {$work->title}");
        }

        $this->info("Expired {$expired->count()} tasks.");
    }

    // -------------------------------------------------------------------------
    // 2. Submitted work admin never reviewed — auto-approve and pay
    // -------------------------------------------------------------------------

    private function autoApproveDeliveries(): void
    {
        $submissions = WorkSubmission::overdueForAutoApproval()
            ->with(['work.category', 'worker'])
            ->get();

        $count = 0;

        foreach ($submissions as $submission) {
            try {
                // The service handles the lock, the commission split and the audit
                // row, so the cron and an admin clicking approve cannot double pay.
                $this->reviews->approveSubmission($submission, isAuto: true);
                $count++;
                $this->line("  Auto-approved submission #{$submission->id}");
            } catch (ApplicationException $e) {
                // Already actioned between the query and now. Not an error.
                $this->line("  Skipped #{$submission->id}: {$e->getMessage()}");
            } catch (\Throwable $e) {
                Log::error("Auto-approval failed for submission #{$submission->id}: " . $e->getMessage());
                $this->error("  Failed #{$submission->id}: {$e->getMessage()}");
            }
        }

        $this->info("Auto-approved {$count} deliveries.");
    }

    // -------------------------------------------------------------------------
    // 3. Assigned but never delivered — free the slot, keep the fee
    // -------------------------------------------------------------------------

    private function cancelAbandoned(): void
    {
        $abandoned = WorkSubmission::abandoned()->with('work')->get();

        $count = 0;

        foreach ($abandoned as $submission) {
            try {
                $this->reviews->expireAbandoned($submission);
                $count++;
                $this->line("  Cancelled abandoned submission #{$submission->id}, slot released");
            } catch (\Throwable $e) {
                Log::error("Abandonment cancel failed for #{$submission->id}: " . $e->getMessage());
            }
        }

        $this->info("Cancelled {$count} abandoned assignments.");
    }
}
