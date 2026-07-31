<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 40)->nullable();
            $table->string('coin_name', 40)->nullable()->default('Job Station Coins');
            $table->string('coin_symbol', 40)->nullable()->default('JC');
            $table->string('cur_text', 40)->nullable()->comment('currency text e.g. USD');
            $table->string('cur_sym', 40)->nullable()->comment('currency symbol e.g. $');
            $table->decimal('ref_commission', 28, 2)->nullable();
            $table->string('email_from', 40)->nullable();
            $table->text('email_template')->nullable();
            $table->string('sms_body')->nullable();
            $table->string('sms_from')->nullable();
            $table->string('base_color', 40)->nullable();
            $table->string('secondary_color', 40)->nullable();
            $table->text('mail_config')->nullable();
            $table->text('sms_config')->nullable();
            $table->text('global_shortcodes')->nullable();
            $table->tinyInteger('kyc_required')->default(0);
            $table->tinyInteger('email_verification')->default(0)->comment('0=off, 1=on');
            $table->tinyInteger('email_notify')->default(0)->comment('0=off, 1=on');
            $table->tinyInteger('phone_verification')->default(0)->comment('0=off, 1=on');
            $table->tinyInteger('sms_notify')->default(0)->comment('0=off, 1=on');
            $table->tinyInteger('force_ssl')->default(0);
            $table->tinyInteger('maintenance_mode')->default(0);
            $table->tinyInteger('secure_password')->default(0);
            $table->tinyInteger('agree')->default(0);
            $table->tinyInteger('registration')->default(1)->comment('0=off, 1=on');
            $table->string('active_template', 40)->nullable()->default('default');
            $table->text('system_info')->nullable();
            $table->longText('socialite_credentials')->nullable();
            $table->text('custom_css')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
