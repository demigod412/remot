<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->comment('admins.id');
            $table->string('action', 80)->comment('e.g. membership.approve, submission.reject');
            $table->string('subject_type', 120)->nullable()->comment('Model class of the affected record');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable()->comment('Amounts, reasons, before/after values');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('admin_id');
            $table->index('action');
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_log');
    }
};
