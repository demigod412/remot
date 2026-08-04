<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two changes, both verified against the real column names.
 *
 * 1. work_categories.daily_application_limit — how many tasks in this category one
 *    worker may apply to per calendar day. 0 means unlimited, matching how
 *    JOBSTATION_MAX_STRIKES treats 0, so existing categories keep their behaviour
 *    without a backfill.
 *
 * 2. works.title widened from 70 to 200 characters. This is a bug fix, not a
 *    feature: Admin\WorkController has always validated title as max:200 while the
 *    column held 70. On MySQL in strict mode a title between 71 and 200 characters
 *    passes validation and then fails at insert with SQLSTATE 22001. Nothing caught
 *    it because no test posts a long title. The importer would have hit it
 *    constantly, since spreadsheet titles run long.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('work_categories', 'daily_application_limit')) {
                $table->unsignedInteger('daily_application_limit')
                    ->default(0)
                    ->comment('0 = unlimited. Max applications per worker per day in this category.')
                    ->after('application_cost');
            }
        });

        // Widen title to match the validation rule that has always been in place.
        Schema::table('works', function (Blueprint $table) {
            $table->string('title', 200)->change();
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            // Truncate first, or rows longer than 70 characters block the change.
            $table->string('title', 70)->change();
        });

        Schema::table('work_categories', function (Blueprint $table) {
            if (Schema::hasColumn('work_categories', 'daily_application_limit')) {
                $table->dropColumn('daily_application_limit');
            }
        });
    }
};
