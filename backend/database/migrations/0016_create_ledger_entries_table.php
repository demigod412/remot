<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->default(0);
            $table->decimal('coins', 28, 8)->default(0);
            $table->decimal('fee', 28, 8)->default(0);
            $table->decimal('balance_after', 28, 8)->default(0);
            $table->string('entry_type', 40)->nullable()->comment('+ or -');
            $table->string('reference', 40)->nullable();
            $table->string('description')->nullable();
            $table->string('category', 40)->nullable()->comment('topup/cashout/work_earn/work_spend/work_refund/referral/admin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
