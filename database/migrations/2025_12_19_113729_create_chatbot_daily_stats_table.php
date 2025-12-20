<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chatbot_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->date('date')->index();

            // Conversation metrics
            $table->integer('total_conversations')->default(0);
            $table->integer('new_conversations')->default(0);
            $table->integer('active_conversations')->default(0);
            $table->integer('closed_conversations')->default(0);
            $table->integer('handed_off_conversations')->default(0);

            // Message metrics
            $table->integer('total_messages')->default(0);
            $table->integer('bot_messages')->default(0);
            $table->integer('user_messages')->default(0);
            $table->decimal('avg_response_time_seconds', 8, 2)->nullable();

            // Channel breakdown
            $table->integer('telegram_conversations')->default(0);
            $table->integer('instagram_conversations')->default(0);
            $table->integer('facebook_conversations')->default(0);

            // Funnel metrics
            $table->integer('awareness_stage')->default(0);
            $table->integer('interest_stage')->default(0);
            $table->integer('consideration_stage')->default(0);
            $table->integer('intent_stage')->default(0);
            $table->integer('purchase_stage')->default(0);
            $table->integer('post_purchase_stage')->default(0);

            // Lead generation
            $table->integer('leads_created')->default(0);
            $table->integer('leads_converted')->default(0);
            $table->decimal('conversion_rate', 5, 2)->nullable();
            $table->decimal('total_conversion_value', 12, 2)->default(0);

            // Intent detection
            $table->json('intent_breakdown')->nullable(); // {GREETING: 50, PRODUCT: 30, ...}
            $table->json('sentiment_breakdown')->nullable(); // {positive: 60, neutral: 30, negative: 10}

            // Customer satisfaction
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->integer('total_ratings')->default(0);

            // Performance
            $table->decimal('avg_conversation_duration_minutes', 8, 2)->nullable();
            $table->decimal('avg_messages_per_conversation', 6, 2)->nullable();

            $table->timestamps();

            $table->unique(['business_id', 'date']);
            $table->index(['business_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_daily_stats');
    }
};
