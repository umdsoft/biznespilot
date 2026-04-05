<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agent suhbatlar jadvali.
 * Foydalanuvchi va AI agent o'rtasidagi suhbatlarni saqlaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->char('user_id', 36);
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->timestamps();

            $table->index('business_id', 'idx_business');
            $table->index('user_id', 'idx_user');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_conversations');
    }
};
