<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname', 40)->nullable();
            $table->string('lastname', 40)->nullable();
            $table->string('username', 40)->unique();
            $table->string('email', 40)->unique();
            $table->string('country_code', 40)->nullable();
            $table->string('mobile', 40)->nullable();
            $table->unsignedInteger('referred_by')->default(0);
            $table->decimal('coin_balance', 28, 8)->default(0);
            $table->string('password');
            $table->string('image')->nullable();
            $table->text('address')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0=banned, 1=active');
            $table->text('kyc_data')->nullable();
            $table->tinyInteger('kyc_status')->default(0)->comment('0=unverified, 1=verified, 2=pending');
            $table->tinyInteger('email_verified')->default(0);
            $table->tinyInteger('phone_verified')->default(0);
            $table->tinyInteger('onboarding_step')->default(0);
            $table->string('verify_code', 40)->nullable();
            $table->dateTime('verify_code_sent_at')->nullable();
            $table->tinyInteger('two_fa_enabled')->default(0);
            $table->tinyInteger('two_fa_verified')->default(1);
            $table->string('two_fa_secret')->nullable();
            $table->string('ban_reason')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
