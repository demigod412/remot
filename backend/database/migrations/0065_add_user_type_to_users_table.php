<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: user_type is deliberately separate from the existing account_type
     * column (1=worker, 2=employer). account_type describes what someone does
     * on the platform. user_type describes what kind of legal entity they are,
     * and is what work_categories.eligible_user_type is matched against.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('user_type')
                ->default(1)
                ->comment('1=individual, 2=business')
                ->after('account_type');

            $table->boolean('must_change_password')
                ->default(false)
                ->comment('True for accounts created by admin approval with a temp password')
                ->after('user_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('user_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['user_type']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'must_change_password']);
        });
    }
};
