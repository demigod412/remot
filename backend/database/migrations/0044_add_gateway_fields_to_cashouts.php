<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashouts', function (Blueprint $table) {
            // PayIn or other gateway's transaction reference (e.g. PAY-X9Y8Z7W6V5U4)
            $table->string('gateway_reference', 100)->nullable()->after('reference');
            // status: 0=pending, 1=approved, 2=rejected, 3=disbursing, 4=disburse_failed
        });
    }

    public function down(): void
    {
        Schema::table('cashouts', function (Blueprint $table) {
            $table->dropColumn('gateway_reference');
        });
    }
};
