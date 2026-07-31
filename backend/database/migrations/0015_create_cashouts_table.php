<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->default(0);
            $table->unsignedInteger('payout_method_id')->default(0);
            $table->decimal('coin_amount', 28, 8)->default(0);
            $table->string('payout_currency', 40)->nullable();
            $table->decimal('coin_to_currency_rate', 28, 8)->default(0);
            $table->decimal('fee', 28, 8)->default(0);
            $table->string('reference', 40)->nullable();
            $table->decimal('payout_amount', 28, 8)->default(0);
            $table->decimal('net_coins_deducted', 28, 8)->default(0);
            $table->text('payout_details')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=pending, 1=approved, 2=rejected');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashouts');
    }
};
