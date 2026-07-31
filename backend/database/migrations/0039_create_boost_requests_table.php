<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boost_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('boostable');           // boostable_type, boostable_id
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days');
            $table->decimal('coins_paid', 18, 8)->default(0);
            $table->tinyInteger('status')->default(0); // 0=pending, 1=approved, 2=rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boost_requests');
    }
};
