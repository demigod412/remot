<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable()->default(0);
            $table->string('name', 40)->nullable();
            $table->string('email', 40)->nullable();
            $table->string('ticket_number', 40)->nullable();
            $table->string('subject')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=open, 1=answered, 2=replied, 3=closed');
            $table->tinyInteger('priority')->default(0)->comment('1=low, 2=medium, 3=high');
            $table->dateTime('last_reply_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_tickets');
    }
};
