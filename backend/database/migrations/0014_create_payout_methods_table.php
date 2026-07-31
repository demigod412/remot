<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('form_id')->default(0);
            $table->string('name', 40)->nullable();
            $table->decimal('min_coins', 28, 8)->nullable();
            $table->decimal('max_coins', 28, 8)->default(0);
            $table->decimal('fixed_fee', 28, 8)->nullable();
            $table->decimal('coin_to_currency_rate', 28, 8)->nullable();
            $table->decimal('percent_fee', 5, 2)->nullable();
            $table->string('currency', 40)->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_methods');
    }
};
