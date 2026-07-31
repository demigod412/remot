<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->boolean('requires_kyc')->default(false)->after('allow_multiple_submissions');
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->boolean('requires_kyc')->default(false)->after('featured_until');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn('requires_kyc');
        });
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn('requires_kyc');
        });
    }
};
