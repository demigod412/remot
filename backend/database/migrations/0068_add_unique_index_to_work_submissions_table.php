<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforces one application per worker per task.
     *
     * A worker can still apply to as many different tasks as they like. They just
     * cannot apply to the same task twice. This replaces the old per-task
     * allow_multiple_submissions flag, which is retired in migration 0070.
     */
    public function up(): void
    {
        // Refuse to run rather than silently destroying data.
        $duplicates = DB::table('work_submissions')
            ->select('work_id', 'worker_id', DB::raw('COUNT(*) as total'))
            ->groupBy('work_id', 'worker_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $detail = $duplicates
                ->map(fn ($row) => "work_id={$row->work_id} worker_id={$row->worker_id} ({$row->total} rows)")
                ->implode('; ');

            throw new RuntimeException(
                'Cannot add the unique index on work_submissions (work_id, worker_id): '
                . $duplicates->count() . ' duplicate pair(s) already exist. '
                . 'Resolve these by hand first, keeping the row you want to be authoritative. '
                . 'Duplicates: ' . $detail
            );
        }

        Schema::table('work_submissions', function (Blueprint $table) {
            $table->unique(['work_id', 'worker_id'], 'work_submissions_work_worker_unique');
        });
    }

    public function down(): void
    {
        Schema::table('work_submissions', function (Blueprint $table) {
            $table->dropUnique('work_submissions_work_worker_unique');
        });
    }
};
