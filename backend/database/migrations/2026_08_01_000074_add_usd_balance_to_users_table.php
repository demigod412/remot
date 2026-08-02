<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'usd_balance')) {
                $table->decimal('usd_balance', 18, 4)
                    ->default(0.0000)
                    ->after('coin_balance');
            }
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('ledger_entries', 'currency')) {
                $table->string('currency', 10)
                    ->default('coin')
                    ->after('amount');
            }
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'type', 'reference'],
                'ledger_entries_user_type_reference_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropUnique('ledger_entries_user_type_reference_unique');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            if (Schema::hasColumn('ledger_entries', 'currency')) {
                $table->dropColumn('currency');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'usd_balance')) {
                $table->dropColumn('usd_balance');
            }
        });
    }
};