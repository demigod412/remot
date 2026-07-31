<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_submissions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('work_id');
            $table->unsignedBigInteger('work_poster_id');
            $table->tinyInteger('work_poster_type')->comment('1=admin, 2=user');
            $table->unsignedInteger('worker_id');
            $table->tinyInteger('worker_type')->default(2)->comment('1=admin, 2=user');
            $table->longText('proof_files')->nullable()->comment('JSON array of file paths');
            $table->longText('proof_note')->nullable();
            $table->longText('rejection_reason')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=applied, 1=pending review, 2=approved, 3=rejected');
            $table->tinyInteger('is_read')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_submissions');
    }
};
