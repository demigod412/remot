<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phases 1 and 3: task payloads with public IDs, and the submission review clock.
 *
 * WHY task_json IS A COLUMN AND NOT AN UPLOADED FILE
 *
 * The spec allowed either. A column wins because the payload has to be read on
 * every task open, edited when a question is wrong, and — critically — served in two
 * different shapes: the full version for scoring and admin review, and a version
 * with nothing sensitive in it for the browser. Doing that from a file means reading
 * and re-parsing on every request, and leaves orphaned files behind whenever a work
 * row is deleted.
 *
 * WHY review_deadline IS STORED RATHER THAN COMPUTED
 *
 * submitted_at + 48h could be worked out on the fly, but then changing the window
 * would silently move the deadline of every submission already waiting, including
 * ones already past it. A stored deadline is a promise made at submission time and
 * kept even if the setting changes afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            if (! Schema::hasColumn('works', 'task_id')) {
                // Public reference, shown to admin and worker. Generated at creation;
                // never parsed out of the uploaded file, so a file cannot claim an ID
                // that belongs to another task.
                $table->string('task_id', 20)->nullable()->unique()->after('slug');
            }

            if (! Schema::hasColumn('works', 'task_json')) {
                // longText: a task with long context passages and 40 questions runs
                // past the 64KB that `text` allows, and MySQL truncates silently in
                // non-strict mode rather than erroring.
                $table->longText('task_json')->nullable()->after('description');
            }

            if (! Schema::hasColumn('works', 'question_count')) {
                // Denormalised from task_json so lists and filters do not decode a
                // large blob per row just to show "12 questions".
                $table->unsignedSmallInteger('question_count')->default(0)->after('task_json');
            }
        });

        Schema::table('work_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('work_submissions', 'result_payload')) {
                // What the console posts back. Stored whole rather than shredded into
                // columns: the shape is defined by the task, and admin review needs
                // the timings and flags as much as the answers.
                $table->longText('result_payload')->nullable()->after('result_file');
            }

            if (! Schema::hasColumn('work_submissions', 'progress_payload')) {
                // Autosave. Separate from result_payload so a resumed half-finished
                // attempt can never be mistaken for a submission.
                $table->longText('progress_payload')->nullable()->after('result_payload');
                $table->timestamp('progress_saved_at')->nullable()->after('progress_payload');
            }

            if (! Schema::hasColumn('work_submissions', 'review_deadline')) {
                $table->timestamp('review_deadline')->nullable()->after('submitted_at');
            }

            if (! Schema::hasColumn('work_submissions', 'credited_at')) {
                // The idempotency guard for payout. Both the hourly job and an admin
                // clicking Approve can reach the same row; whichever sets this first
                // inside the transaction is the one that pays.
                $table->timestamp('credited_at')->nullable()->after('review_deadline');
            }

            $table->index(['delivery_status', 'review_deadline'], 'submissions_due_index');
        });

        Schema::table('app_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('app_settings', 'default_review_hours')) {
                // The fallback when a task does not set its own auto_approve_hours.
                // 48 per the spec, but editable rather than hardcoded.
                $table->unsignedSmallInteger('default_review_hours')->default(48)->after('min_cashout');
            }
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            if (Schema::hasColumn('app_settings', 'default_review_hours')) {
                $table->dropColumn('default_review_hours');
            }
        });

        Schema::table('work_submissions', function (Blueprint $table) {
            $table->dropIndex('submissions_due_index');

            foreach (['credited_at', 'review_deadline', 'progress_saved_at', 'progress_payload', 'result_payload'] as $col) {
                if (Schema::hasColumn('work_submissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('works', function (Blueprint $table) {
            foreach (['question_count', 'task_json'] as $col) {
                if (Schema::hasColumn('works', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('works', 'task_id')) {
                $table->dropUnique(['task_id']);
                $table->dropColumn('task_id');
            }
        });
    }
};
