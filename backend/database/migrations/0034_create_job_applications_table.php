<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_listing_id');
            $table->unsignedBigInteger('applicant_id');        // User applying
            $table->text('cover_letter')->nullable();
            $table->string('resume')->nullable();               // uploaded file
            $table->string('portfolio_url')->nullable();
            $table->decimal('expected_salary', 10, 2)->nullable();
            $table->string('expected_salary_currency', 10)->default('USD');
            $table->tinyInteger('status')->default(0);         // 0=pending 1=reviewed 2=shortlisted 3=accepted 4=rejected
            $table->text('employer_note')->nullable();          // employer's private note
            $table->tinyInteger('is_read')->default(0);        // employer read this?
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['job_listing_id', 'applicant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
