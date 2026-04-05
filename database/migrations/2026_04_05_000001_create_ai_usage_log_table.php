<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI token va xarajat kuzatuv jadvali.
 * Har bir AI chaqiriq qayd qilinadi — model, token soni, xarajat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36)->nullable();
            $table->string('agent_type', 50);
            $table->string('model', 50);
            $table->integer('tokens_input')->default(0);
            $table->integer('tokens_output')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->boolean('cache_hit')->default(false);
            $table->string('prompt_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'created_at'], 'idx_business_date');
            $table->index('model', 'idx_model');
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_log');
    }
};
