<?php

namespace App\Console\Commands;

use App\Models\LedgerEntry;
use App\Models\Work;
use App\Models\WorkSubmission;
use App\Services\CoinService;
use App\Services\NotifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWorkTimers extends Command
{
    protected $signature   = 'jobstation:process-timers';
    protected $description = 'Expire works past their deadline, auto-approve submissions past their window, set deadlines on new submissions';

    public function handle(): int
    {
        $this->expireWorks();
        $this->autoApproveSubmissions();

        $this->info('Job Station timers processed at ' . now()->toDateTimeString());
        return self::SUCCESS;
    }

    // ── 1. Expire works whose expires_at has passed ─────────────────────────

    private function expireWorks(): void
    {
        $expired = Work::where('work_status', 1)          // active
            ->where('approval_status', 1)                  // approved
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $work) {
            DB::transaction(function () use ($work) {
                $work->update(['work_status' => 2]);       // finished

                // Refund remaining budget (unspent coins)
                $approvedCount = $work->approvedSubmissions()->count();
                $spent         = $approvedCount * $work->coins_per_worker;
                $refund        = $work->total_coins - $spent;

                if ($refund > 0 && $work->poster_type === 2) {
                    $poster = $work->poster;
                    if ($poster) {
                        CoinService::credit(
                            $poster,
                            $refund,
                            $work->slug,
                            'work_refund',
                            'Refund: expired work — ' . $work->title
                        );
                    }
                }
            });

            $this->line("  Expired work #{$work->id}: {$work->title}");
            Log::info("Job Station: expired work #{$work->id}");
        }

        $this->info("Expired {$expired->count()} works.");
    }

    // ── 2. Auto-approve submissions past their window ────────────────────────

    private function autoApproveSubmissions(): void
    {
        // Find submissions in "under review" state (status=1)
        // where work.auto_approve_hours is set
        // and submitted_at + auto_approve_hours <= now()
        $submissions = WorkSubmission::where('status', 1)
            ->whereNotNull('submitted_at')
            ->whereHas('work', fn ($q) => $q->whereNotNull('auto_approve_hours'))
            ->with(['work', 'worker'])
            ->get()
            ->filter(function (WorkSubmission $sub) {
                $hours = $sub->work->auto_approve_hours;
                return $hours && $sub->submitted_at->addHours($hours)->lte(now());
            });

        $count = 0;
        foreach ($submissions as $sub) {
            DB::transaction(function () use ($sub, &$count) {
                $sub->update(['status' => 2, 'is_read' => 1]);

                $worker = $sub->worker;
                if ($worker instanceof \App\Models\User) {
                    CoinService::credit(
                        $worker,
                        $sub->work->coins_per_worker,
                        $sub->work->slug,
                        'work_earn',
                        'Auto-approved: ' . $sub->work->title
                    );
                    NotifyService::send($worker, 'SUBMISSION_APPROVED', [
                        'work_title' => $sub->work->title,
                        'coins'      => number_format($sub->work->coins_per_worker, 0),
                    ]);
                }

                // Mark work finished if all slots filled
                $approvedCount = $sub->work->approvedSubmissions()->count();
                if ($approvedCount >= $sub->work->worker_slots) {
                    $sub->work->update(['work_status' => 2]);
                }

                $count++;
            });

            $this->line("  Auto-approved submission #{$sub->id} for work: {$sub->work->title}");
        }

        $this->info("Auto-approved {$count} submissions.");
    }
}
