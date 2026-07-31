<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->decimal('coin_rate', 18, 4)->nullable()->after('coin_symbol');
            $table->string('coin_rate_currency', 20)->nullable()->after('coin_rate');
            $table->boolean('show_coin_rate')->default(false)->after('coin_rate_currency');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['coin_rate', 'coin_rate_currency', 'show_coin_rate']);
        });
    }
};
