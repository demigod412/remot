<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();

            // Applicant (individual fields, always required)
            $table->string('full_name', 120);
            $table->string('email', 120)->unique();
            $table->string('phone', 40)->nullable();
            $table->string('country', 80)->nullable();
            $table->tinyInteger('applicant_type')->default(1)->comment('1=individual, 2=business');
            $table->string('resume_path')->nullable();
            $table->string('cover_letter_path')->nullable();

            // Business fields (only used when applicant_type = 2)
            $table->string('business_name', 160)->nullable();
            $table->string('business_email', 120)->nullable();
            $table->string('business_registration_doc')->nullable();
            $table->string('business_country', 80)->nullable();

            // Review workflow
            $table->tinyInteger('status')->default(0)->comment('0=pending, 1=approved, 2=rejected');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('admins.id');
            $table->timestamp('reviewed_at')->nullable();

            // Public lookup + abuse tracking
            $table->string('reference_code', 40)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('applicant_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
