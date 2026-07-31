<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // Public contact + footer links (managed from Admin → Settings → Links & Social)
            $table->string('contact_email')->nullable()->after('email_from');
            $table->string('contact_phone', 40)->nullable()->after('contact_email');
            $table->string('support_hours')->nullable()->after('contact_phone');
            $table->string('facebook')->nullable()->after('support_hours');
            $table->string('twitter')->nullable()->after('facebook');
            $table->string('linkedin')->nullable()->after('twitter');
            $table->string('instagram')->nullable()->after('linkedin');
            $table->string('app_store_url')->nullable()->after('instagram');
            $table->string('play_store_url')->nullable()->after('app_store_url');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_email', 'contact_phone', 'support_hours',
                'facebook', 'twitter', 'linkedin', 'instagram',
                'app_store_url', 'play_store_url',
            ]);
        });
    }
};
