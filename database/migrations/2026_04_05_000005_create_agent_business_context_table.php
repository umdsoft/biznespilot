<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biznes xotirasi jadvali (2-qatlam).
 * Agent qarorlari, foydalanuvchi afzalliklari, biznes holati saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_business_context', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->enum('context_type', ['decision', 'preference', 'snapshot', 'feedback']);
            $table->string('context_key', 100);
            $table->json('context_value');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'context_type'], 'idx_business_type');
            $table->index('expires_at', 'idx_expires');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_business_context');
    }
};
