<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reliability strikes (abandoned and rejected work) are counted from
     * work_submissions rather than cached in a column, so they can never drift out
     * of sync with the underlying records.
     *
     * This timestamp is the admin override: only submissions finalised AFTER it
     * count. Clearing someone's strikes sets it to now, which forgives history
     * without deleting or editing any submission row.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('strikes_cleared_at')
                ->nullable()
                ->comment('Only reliability strikes after this moment count')
                ->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('strikes_cleared_at');
        });
    }
};
