<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employer_id');   // User who posted
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('title', 120);
            $table->string('slug', 140)->unique();
            $table->longText('description');
            $table->string('location', 100)->nullable();       // "Remote" or city
            $table->tinyInteger('location_type')->default(1);  // 1=remote 2=onsite 3=hybrid
            $table->string('employment_type', 40)->default('full_time'); // full_time, part_time, contract, freelance
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_currency', 10)->default('USD');
            $table->tinyInteger('salary_visible')->default(1);
            $table->text('requirements')->nullable();           // bullet list / text
            $table->text('benefits')->nullable();
            $table->string('cover_image')->nullable();
            $table->tinyInteger('status')->default(0);         // 0=pending 1=active 2=closed 3=rejected
            $table->string('rejection_reason')->nullable();
            $table->unsignedInteger('application_count')->default(0);
            $table->timestamp('closes_at')->nullable();        // application deadline
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
