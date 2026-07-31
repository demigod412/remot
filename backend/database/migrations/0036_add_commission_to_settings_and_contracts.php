<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add contract commission rate to global settings
        Schema::table('app_settings', function (Blueprint $table) {
            $table->decimal('contract_commission', 5, 2)->default(5.00)->after('ref_commission');
        });

        // Track commission earned per contract
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('commission_amount', 18, 8)->default(0)->after('amount');
            $table->decimal('worker_payout', 18, 8)->default(0)->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('contract_commission');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['commission_amount', 'worker_payout']);
        });
    }
};