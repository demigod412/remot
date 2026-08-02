<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separates earnings (USD) from spending money (JC coins).
 *
 * The two currencies never convert into one another. Coins are bought via topup
 * and spent on application fees. USD is earned when admin approves delivered
 * work and leaves only via withdrawal. Because nothing converts, there is no
 * exchange rate to store, own, or version — which is the whole point of the
 * model. Do not add one later without revisiting this comment.
 *
 * Schema only. No behaviour changes here: usd_balance stays 0 and payout_usd
 * stays 0 until the service layer is switched over in the following commits.
 *
 * NOTE on column names. Every `after()` target below was checked against the
 * real schema: users.coin_balance (0001), works.coins_per_worker (0009),
 * ledger_entries.category (0016). The previous attempt at this migration
 * referenced `amount` and `type` on ledger_entries, neither of which exists.
 * SQLite ignores a bad after() and silently degrades a bad index column to a
 * string literal, so the tests passed while MySQL would have rejected it
 * outright. Verify column names against the create migration, not from memory.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Worker earnings balance. 4dp: USD needs 2, but payouts are split by a
        // commission percentage and rounding a share at 2dp loses cents against
        // the gross. The extra places absorb the split; display rounds to 2.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'usd_balance')) {
                $table->decimal('usd_balance', 18, 4)
                    ->default(0)
                    ->after('coin_balance');
            }
        });

        // Admin enters the USD payout per task explicitly. Deliberately a second
        // column rather than a reinterpretation of coins_per_worker: that column
        // still drives the legacy user-gig flow, and silently changing what an
        // existing number means would rewrite the value of every task already in
        // the database.
        Schema::table('works', function (Blueprint $table) {
            if (! Schema::hasColumn('works', 'payout_usd')) {
                $table->decimal('payout_usd', 18, 4)
                    ->default(0)
                    ->after('coins_per_worker');
            }
        });

        // Which currency a ledger row is denominated in. Defaults to 'coin' so
        // every historical row keeps its existing meaning without a backfill.
        Schema::table('ledger_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('ledger_entries', 'currency')) {
                $table->string('currency', 10)
                    ->default('coin')
                    ->after('category');
            }
        });

        // Reporting reads the ledger by user and currency constantly once there
        // are two balances, so index that pair rather than scanning.
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->index(['user_id', 'currency'], 'ledger_entries_user_currency_index');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropIndex('ledger_entries_user_currency_index');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            if (Schema::hasColumn('ledger_entries', 'currency')) {
                $table->dropColumn('currency');
            }
        });

        Schema::table('works', function (Blueprint $table) {
            if (Schema::hasColumn('works', 'payout_usd')) {
                $table->dropColumn('payout_usd');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'usd_balance')) {
                $table->dropColumn('usd_balance');
            }
        });
    }
};
