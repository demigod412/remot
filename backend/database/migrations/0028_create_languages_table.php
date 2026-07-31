<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->nullable();
            $table->string('code', 40)->nullable();
            $table->string('icon')->nullable();
            $table->tinyInteger('text_align')->default(0)->comment('0=ltr, 1=rtl');
            $table->tinyInteger('is_default')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
