<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('coins');
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->integer('bonus_coins')->default(0);
            $table->string('badge_label', 40)->nullable();
            $table->tinyInteger('is_popular')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_packages');
    }
};
