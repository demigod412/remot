<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * application_cost is the number of coins a worker spends to apply to any
     * task in this category. Decided as a per-category price, so it is set here
     * and inherited by every task in the category rather than set per task.
     *
     * Precision matches works.total_coins / works.coins_per_worker, which are
     * both decimal(10, 2).
     */
    public function up(): void
    {
        Schema::table('work_categories', function (Blueprint $table) {
            $table->decimal('commission_percent', 5, 2)
                ->default(0)
                ->comment('Platform cut taken from the worker payout, 0.00 to 100.00')
                ->after('status');

            $table->decimal('application_cost', 10, 2)
                ->default(0)
                ->comment('Coins a worker spends to apply to a task in this category')
                ->after('commission_percent');

            $table->tinyInteger('eligible_user_type')
                ->default(0)
                ->comment('0=both, 1=individual only, 2=business only')
                ->after('application_cost');

            $table->text('description')->nullable()->after('eligible_user_type');
        });
    }

    public function down(): void
    {
        Schema::table('work_categories', function (Blueprint $table) {
            $table->dropColumn([
                'commission_percent',
                'application_cost',
                'eligible_user_type',
                'description',
            ]);
        });
    }
};
