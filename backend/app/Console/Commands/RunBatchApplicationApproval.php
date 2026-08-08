<?php

namespace App\Console\Commands;

use App\Services\BatchApplicationApprovalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunBatchApplicationApproval extends Command
{
    protected $signature = 'jobstation:approve-applications
                            {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Approve a random proportion of pending task applications, per category';

    public function handle(BatchApplicationApprovalService $service): int
    {
        if ($this->option('dry-run')) {
            // Worth having before the first live run: the outcome is irreversible
            // for the worker, whose fee is not refunded on rejection.
            $this->warn('Dry run is not implemented — approving and rejecting are the only');
            $this->warn('side effects, and simulating them accurately would mean duplicating');
            $this->warn('the logic. Enable one category and watch a single run instead.');

            return self::FAILURE;
        }

        $stats = $service->run();

        $summary = sprintf(
            '%d categories, %d applications considered, %d approved, %d rejected',
            $stats['categories'],
            $stats['considered'],
            $stats['approved'],
            $stats['rejected']
        );

        $this->info($summary);

        // Logged as well as printed: this runs unattended eight times a day and the
        // console output goes nowhere. Money and access decisions need a trail.
        if ($stats['considered'] > 0) {
            Log::info('Batch application approval: ' . $summary);
        }

        return self::SUCCESS;
    }
}
