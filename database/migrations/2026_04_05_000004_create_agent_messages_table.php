<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agent xabarlar jadvali.
 * Suhbatdagi har bir xabar (foydalanuvchi va agent) saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->char('business_id', 36);
            $table->enum('role', ['user', 'agent', 'system']);
            $table->text('content');
            $table->enum('agent_type', [
                'orchestrator', 'marketing', 'sales', 'analytics', 'call_center', 'evaluator',
            ])->nullable();
            $table->enum('model_used', ['none', 'haiku', 'sonnet', 'groq_whisper'])->default('none');
            $table->integer('tokens_input')->default(0);
            $table->integer('tokens_output')->default(0);
            $table->decimal('cost_usd', 8, 6)->default(0);
            $table->json('routed_to')->nullable(); // ["marketing","sales"] kabi
            $table->integer('processing_time_ms')->default(0);
            $table->timestamps();

            $table->index('conversation_id', 'idx_conversation');
            $table->index('business_id', 'idx_business');
            $table->foreign('conversation_id')->references('id')->on('agent_conversations')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_messages');
    }
};
