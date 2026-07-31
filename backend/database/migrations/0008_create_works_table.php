<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poster_id');
            $table->tinyInteger('poster_type')->comment('1=admin, 2=user');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->bigInteger('topup_id')->default(0);
            $table->string('slug', 100)->unique();
            $table->string('title', 70);
            $table->string('cover_image')->nullable();
            $table->integer('worker_slots');
            $table->longText('description');
            $table->decimal('total_coins', 10, 2);
            $table->decimal('coins_per_worker', 10, 2);
            $table->integer('avg_minutes')->nullable();
            $table->tinyInteger('work_status')->default(1)->comment('0=holding, 1=active, 2=finished');
            $table->tinyInteger('approval_status')->default(0)->comment('0=pending, 1=approved, 2=rejected');
            $table->string('rejection_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('auto_approve_hours')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('work_categories')->onDelete('restrict');
            $table->foreign('subcategory_id')->references('id')->on('work_subcategories')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
