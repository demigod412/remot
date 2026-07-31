<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('form_id')->default(0);
            $table->integer('code')->nullable();
            $table->string('name', 40)->nullable();
            $table->string('driver', 40)->default('NULL');
            $table->tinyInteger('status')->default(1)->comment('1=enabled, 2=disabled');
            $table->text('credentials')->nullable();
            $table->text('currencies')->nullable();
            $table->tinyInteger('is_crypto')->default(0)->comment('0=fiat, 1=crypto');
            $table->text('webhook_info')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_channels');
    }
};
