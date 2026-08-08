<?php

namespace App\Console\Commands;

use App\Models\WorkSubmission;
use App\Services\ApplicationException;
use App\Services\TaskReviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3: auto-approve submissions whose review window has elapsed.
 *
 * Hourly. Finds every submission still awaiting review past its deadline and
 * approves it, which credits the worker through the same commission split an admin
 * approval uses.
 *
 * A LATE RUN CATCHES UP RATHER THAN SKIPPING
 *
 * The query is `review_deadline <= now()`, not "deadline fell in the last hour". If
 * the scheduler is down for six hours, the next run approves everything that came
 * due meanwhile. The alternative silently strands submissions past their promised
 * window, and nobody notices until a worker asks where their money is.
 */
class ProcessReviewDeadlines extends Command
{
    protected $signature = 'jobstation:process-review-deadlines {--limit=200}';

    protected $description = 'Approve submissions whose 48-hour review window has passed';

    public function handle(TaskReviewService $reviews): int
    {
        $due = WorkSubmission::with('work.category', 'worker')
            ->where('delivery_status', WorkSubmission::DEL_SUBMITTED)
            ->whereNotNull('review_deadline')
            ->where('review_deadline', '<=', now())
            ->whereNull('credited_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        $approved = 0;
        $failed   = 0;

        foreach ($due as $submission) {
            try {
                // The same method an admin's Approve button calls. Auto-approval must
                // not be a second payout path — one of them would drift.
                $reviews->approveSubmission($submission, isAuto: true);
                $approved++;
            } catch (ApplicationException $e) {
                // Already reviewed between the query and now. Expected under a race
                // with an admin, and not an error.
                continue;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Auto-approval failed', [
                    'submission_id' => $submission->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        $summary = "{$approved} submission(s) auto-approved" . ($failed > 0 ? ", {$failed} failed" : '');
        $this->info($summary);
        Log::info('Review deadlines processed: ' . $summary);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
