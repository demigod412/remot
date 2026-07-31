<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('description');
            $table->decimal('amount', 18, 8);      // coins held in escrow
            $table->dateTime('deadline_at')->nullable();

            /*
             * Status:
             *  0 = offered      — sent, worker hasn't responded
             *  1 = accepted     — worker accepted, coins deducted from employer
             *  2 = submitted    — worker submitted deliverable
             *  3 = completed    — employer approved, worker paid
             *  4 = declined     — worker declined
             *  5 = cancelled    — cancelled before submission (coins refunded)
             *  6 = disputed     — employer raised a dispute after submission
             */
            $table->tinyInteger('status')->default(0);

            $table->string('reference')->unique();          // REF-XXXXXX
            $table->text('worker_note')->nullable();        // submission notes
            $table->string('proof_file')->nullable();       // uploaded deliverable
            $table->text('employer_note')->nullable();      // cancel/dispute reason
            $table->text('declined_reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
