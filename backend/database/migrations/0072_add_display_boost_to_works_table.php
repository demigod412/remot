<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cosmetic head start for the applicant count on a brand new task.
     *
     * A launch-day task showing "0 applied" reads as broken, so admin can seed a
     * number here.
     *
     * CRITICAL: this column is display-only and must never enter slot arithmetic.
     * Work::$slots_remaining, occupyingSubmissions() and the slot cap in
     * TaskApplicationService all deliberately ignore it. If it ever counted toward
     * worker_slots, a 100-slot task with an 80 boost would silently accept only 20
     * real workers.
     *
     * It is also NOT applied to slots_remaining anywhere in the UI, because
     * overstating scarcity to someone about to spend a non-refundable fee is a
     * different thing from a soft popularity signal.
     */
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->unsignedInteger('display_application_boost')
                ->default(0)
                ->comment('DISPLAY ONLY. Added to the shown applicant count. Never affects slots.')
                ->after('worker_slots');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn('display_application_boost');
        });
    }
};
