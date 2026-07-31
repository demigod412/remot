<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_topups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->default(0);
            $table->unsignedInteger('channel_code')->default(0);
            $table->decimal('amount', 28, 8)->default(0);
            $table->string('pay_currency', 40)->nullable();
            $table->decimal('charge', 28, 8)->default(0);
            $table->decimal('rate', 28, 8)->default(0);
            $table->decimal('payable_amount', 28, 8)->default(0);
            $table->text('gateway_response')->nullable();
            $table->string('crypto_amount')->nullable();
            $table->string('crypto_address')->nullable();
            $table->string('reference', 40)->nullable();
            $table->integer('attempts')->default(0);
            $table->tinyInteger('status')->default(0)->comment('0=initiated, 1=success, 2=pending, 3=cancelled');
            $table->tinyInteger('via_api')->default(0);
            $table->string('admin_note')->nullable();
            $table->decimal('coins_credited', 28, 8)->default(0);
            $table->unsignedBigInteger('package_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_topups');
    }
};
