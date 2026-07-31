<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coin_topups', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('proof_image');
        });
    }

    public function down(): void
    {
        Schema::table('coin_topups', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });
    }
};
