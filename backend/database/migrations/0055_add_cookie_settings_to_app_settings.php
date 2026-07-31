<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('cookie_enabled')->default(true)->after('custom_css');
            $table->text('cookie_text')->nullable()->after('cookie_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['cookie_enabled', 'cookie_text']);
        });
    }
};
