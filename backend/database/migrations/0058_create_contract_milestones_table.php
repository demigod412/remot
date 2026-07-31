<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 8);
            $table->dateTime('deadline_at')->nullable();

            /*
             * 0 = pending    (waiting for worker to submit)
             * 1 = submitted  (worker submitted, waiting for employer)
             * 2 = approved   (employer approved, payment released)
             * 3 = disputed   (employer raised dispute)
             */
            $table->tinyInteger('status')->default(0);

            $table->text('worker_note')->nullable();
            $table->string('proof_file')->nullable();
            $table->decimal('commission_amount', 18, 8)->nullable();
            $table->decimal('worker_payout', 18, 8)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_milestones');
    }
};
