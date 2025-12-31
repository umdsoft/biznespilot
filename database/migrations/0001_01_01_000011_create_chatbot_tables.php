<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Chatbot Configs
        Schema::create('chatbot_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('platform', 50); // whatsapp, telegram, instagram, web
            $table->string('name');
            $table->text('welcome_message')->nullable();
            $table->text('fallback_message')->nullable();
            $table->json('ai_settings')->nullable();
            $table->json('quick_replies')->nullable();
            $table->boolean('ai_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('platform');
            $table->unique(['business_id', 'platform']);
            $table->softDeletes();
        });

        // Chatbot Conversations
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('config_id');
            $table->uuid('customer_id')->nullable();
            $table->uuid('lead_id')->nullable();
            $table->string('platform', 50);
            $table->string('platform_user_id')->nullable();
            $table->string('platform_conversation_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->integer('messages_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('config_id')->references('id')->on('chatbot_configs')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');

            $table->index('business_id');
            $table->index('platform');
            $table->index('status');
            $table->index('platform_user_id');
            $table->index('last_message_at');
            $table->softDeletes();
        });

        // Chatbot Messages
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->string('direction', 10); // inbound, outbound
            $table->string('type', 20)->default('text'); // text, image, audio, video, document
            $table->text('content')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_type', 50)->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->string('status', 20)->default('sent');
            $table->integer('response_time_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('chatbot_conversations')->onDelete('cascade');
            $table->index('conversation_id');
            $table->index('direction');
            $table->index('created_at');
        });

        // Chatbot Knowledge Base
        Schema::create('chatbot_knowledge', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('category', 100)->nullable();
            $table->text('question');
            $table->text('answer');
            $table->json('keywords')->nullable();
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('category');
            // Fulltext index only for MySQL/PostgreSQL (SQLite doesn't support it)
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['question', 'answer']);
            }
            $table->softDeletes();
        });

        // Chatbot Templates
        Schema::create('chatbot_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('type', 50);
            $table->string('category', 50)->nullable();
            $table->text('content');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('type');
            $table->softDeletes();
        });

        // Chatbot Daily Stats
        Schema::create('chatbot_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('config_id');
            $table->date('stat_date');
            $table->integer('conversations_count')->default(0);
            $table->integer('messages_received')->default(0);
            $table->integer('messages_sent')->default(0);
            $table->integer('ai_responses')->default(0);
            $table->integer('human_handoffs')->default(0);
            $table->integer('avg_response_time_ms')->default(0);
            $table->decimal('satisfaction_score', 3, 2)->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('config_id')->references('id')->on('chatbot_configs')->onDelete('cascade');
            $table->unique(['config_id', 'stat_date']);
            $table->index(['business_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_daily_stats');
        Schema::dropIfExists('chatbot_templates');
        Schema::dropIfExists('chatbot_knowledge');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('chatbot_configs');
    }
};
