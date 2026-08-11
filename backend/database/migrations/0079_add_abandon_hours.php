<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separates the two clocks that were sharing one column.
 *
 * Until now, works.auto_approve_hours set BOTH how long a worker had to do the task
 * and how long an admin had to review it once submitted — the same number used for
 * two unrelated deadlines. Setting the review window to 48 hours therefore also gave
 * the worker only 48 hours to complete, and lengthening one lengthened the other.
 *
 *   abandon_after_hours   how long a worker has after approval before the assignment
 *                         is cancelled and the slot released. Default 120 (5 days).
 *   default_review_hours  how long an admin has after submission before the work is
 *                         auto-approved and paid. Already exists, default 48.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('app_settings', 'abandon_after_hours')) {
                $table->unsignedSmallInteger('abandon_after_hours')
                    ->default(120)
                    ->comment('Hours a worker has after approval before the assignment is cancelled')
                    ->after('default_review_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            if (Schema::hasColumn('app_settings', 'abandon_after_hours')) {
                $table->dropColumn('abandon_after_hours');
            }
        });
    }
};
