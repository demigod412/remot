<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ratee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('ratable_id');
            $table->string('ratable_type');
            $table->tinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();

            $table->index(['ratable_id', 'ratable_type']);
            $table->index('ratee_id');
            $table->unique(['rater_id', 'ratee_id', 'ratable_id', 'ratable_type'], 'unique_ratable_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
