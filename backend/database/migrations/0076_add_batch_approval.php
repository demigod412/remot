<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch application approval: per-category rates, the daily draw, and annotate codes.
 *
 * THE DAILY DRAW TABLE EXISTS BECAUSE THE RATE MUST BE STABLE
 *
 * The rate is drawn once per worker, per category, per calendar day, and every run
 * that day reuses it. Redrawing each run would mean a worker's odds swing eight
 * times a day, so "your approval rate is 40-70%" would describe nothing they could
 * observe. Storing the draw also makes it auditable: when someone asks why they got
 * 2 of 5, there is a row saying the rate was 47% that day.
 *
 * NO SEPARATE annotate_sessions TABLE
 *
 * The spec described one, but that assumed workers were assigned tasks from a
 * category pool. Under the model actually chosen, a worker picks their own task and
 * work_submissions IS the assignment — it already holds the worker, the work, the
 * fee paid, the status and the deadline. A parallel sessions table would duplicate
 * every one of those and create two places for the truth to live. The annotate code
 * is therefore a column on the submission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('work_categories', 'min_approval_rate')) {
                // Percentages, whole numbers. 0/0 would mean nothing is ever approved,
                // so the defaults are live values rather than a disabled state — the
                // batch is opt-in per category via batch_approval_enabled below.
                $table->unsignedTinyInteger('min_approval_rate')
                    ->default(40)
                    ->after('daily_application_limit');

                $table->unsignedTinyInteger('max_approval_rate')
                    ->default(70)
                    ->after('min_approval_rate');
            }

            if (! Schema::hasColumn('work_categories', 'batch_approval_enabled')) {
                // Off by default. Turning this on hands approval decisions to a
                // scheduled job, and that should be a deliberate act per category
                // rather than something that starts happening after a deploy.
                $table->boolean('batch_approval_enabled')
                    ->default(false)
                    ->after('max_approval_rate');
            }
        });

        // One drawn rate per worker per category per day.
        Schema::create('application_approval_draws', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->date('draw_date');
            $table->unsignedTinyInteger('rate')->comment('Percent, drawn between the category min and max');
            $table->unsignedInteger('considered')->default(0)->comment('Applications seen across the day');
            $table->unsignedInteger('approved')->default(0)->comment('Applications approved across the day');
            $table->timestamps();

            // The guarantee that one worker gets one rate per category per day. A
            // race between two runs would otherwise draw twice and the second would
            // silently replace the first.
            $table->unique(['user_id', 'category_id', 'draw_date'], 'approval_draw_unique');
            $table->index('draw_date');
        });

        Schema::table('work_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('work_submissions', 'annotate_code')) {
                // Issued when an application is approved. Unique so it can be the
                // sole key a worker types to open their task.
                $table->string('annotate_code', 24)
                    ->nullable()
                    ->unique()
                    ->after('fee_reference');
            }

            if (! Schema::hasColumn('work_submissions', 'approved_by_batch')) {
                // Distinguishes a lottery approval from an admin's decision. Without
                // it there is no way to answer "was this a human or the scheduler?"
                // when a worker disputes an outcome.
                $table->boolean('approved_by_batch')
                    ->default(false)
                    ->after('annotate_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('work_submissions', 'approved_by_batch')) {
                $table->dropColumn('approved_by_batch');
            }
            if (Schema::hasColumn('work_submissions', 'annotate_code')) {
                $table->dropUnique(['annotate_code']);
                $table->dropColumn('annotate_code');
            }
        });

        Schema::dropIfExists('application_approval_draws');

        Schema::table('work_categories', function (Blueprint $table) {
            foreach (['batch_approval_enabled', 'max_approval_rate', 'min_approval_rate'] as $col) {
                if (Schema::hasColumn('work_categories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
