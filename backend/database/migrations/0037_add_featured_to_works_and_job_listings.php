<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('work_status');
            $table->timestamp('featured_until')->nullable()->after('is_featured');
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
            $table->timestamp('featured_until')->nullable()->after('is_featured');
        });

        // Boost pricing in app_settings
        Schema::table('app_settings', function (Blueprint $table) {
            $table->decimal('boost_cost_work', 10, 2)->default(50)->after('contract_commission');
            $table->integer('boost_days_work')->default(7)->after('boost_cost_work');
            $table->decimal('boost_cost_job', 10, 2)->default(100)->after('boost_days_work');
            $table->integer('boost_days_job')->default(7)->after('boost_cost_job');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'featured_until']);
        });
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'featured_until']);
        });
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['boost_cost_work', 'boost_days_work', 'boost_cost_job', 'boost_days_job']);
        });
    }
};
