<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master skills list (admin managed)
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained('work_categories')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // User → Skills (many-to-many)
        Schema::create('user_skills', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'skill_id']);
        });

        // Work → Required Skills (many-to-many)
        Schema::create('work_skills', function (Blueprint $table) {
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['work_id', 'skill_id']);
        });

        // Job Listing → Required Skills (many-to-many)
        Schema::create('job_listing_skills', function (Blueprint $table) {
            $table->foreignId('job_listing_id')->constrained('job_listings')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['job_listing_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listing_skills');
        Schema::dropIfExists('work_skills');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('skills');
    }
};
