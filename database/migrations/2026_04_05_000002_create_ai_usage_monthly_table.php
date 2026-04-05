<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oylik AI xarajat umumlashtirish jadvali.
 * Har bir biznes uchun oylik token va xarajat statistikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_monthly', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->date('month');
            $table->integer('total_requests')->default(0);
            $table->integer('cache_hit_count')->default(0);
            $table->integer('total_tokens_input')->default(0);
            $table->integer('total_tokens_output')->default(0);
            $table->decimal('total_cost_usd', 10, 4)->default(0);
            $table->json('model_breakdown')->nullable(); // {"haiku":{"count":100,"cost":0.5},...}
            $table->timestamps();

            $table->unique(['business_id', 'month'], 'idx_business_month');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_monthly');
    }
};
