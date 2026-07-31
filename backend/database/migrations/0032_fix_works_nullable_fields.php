<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            // subcategory_id is optional — must be nullable
            $table->dropForeign(['subcategory_id']);
            $table->unsignedBigInteger('subcategory_id')->nullable()->change();
            $table->foreign('subcategory_id')->references('id')->on('work_subcategories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropForeign(['subcategory_id']);
            $table->unsignedBigInteger('subcategory_id')->nullable(false)->change();
            $table->foreign('subcategory_id')->references('id')->on('work_subcategories')->onDelete('restrict');
        });
    }
};
