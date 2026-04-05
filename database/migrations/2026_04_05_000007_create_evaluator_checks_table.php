<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tekshiruvchi tizimi jadvali.
 * Agent qarorlari tekshiruvi natijalari saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluator_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->string('agent_type', 50);
            $table->string('action_type', 100);
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->string('check_method', 20); // skip, rule, haiku, sonnet
            $table->json('input_data');
            $table->enum('result', ['approved', 'rejected', 'modified']);
            $table->text('rejection_reason')->nullable();
            $table->string('model_used', 20)->nullable();
            $table->integer('tokens_used')->default(0);
            $table->integer('processing_time_ms')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('business_id', 'idx_business');
            $table->index('result', 'idx_result');
            $table->index('agent_type', 'idx_agent_type');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluator_checks');
    }
};
