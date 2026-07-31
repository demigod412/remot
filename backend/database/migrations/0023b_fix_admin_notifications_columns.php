<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            // Drop the old misnamed columns
            $table->dropColumn(['user_id', 'read_status', 'click_url']);
        });

        Schema::table('admin_notifications', function (Blueprint $table) {
            // Add properly named columns
            $table->unsignedBigInteger('admin_id')->default(0)->after('id');
            $table->text('message')->nullable()->after('title');
            $table->string('type', 20)->default('info')->after('message');
            $table->string('url')->nullable()->after('type');
            $table->boolean('is_read')->default(false)->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropColumn(['admin_id', 'message', 'type', 'url', 'is_read']);
        });

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->default(0)->after('id');
            $table->tinyInteger('read_status')->default(0);
            $table->text('click_url')->nullable();
        });
    }
};
