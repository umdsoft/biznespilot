<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('telegram_conversations')->cascadeOnDelete();

            // Telegram message ID
            $table->bigInteger('telegram_message_id')->nullable();
            $table->bigInteger('telegram_chat_id');

            // Direction
            $table->enum('direction', ['incoming', 'outgoing']);

            // Sender
            $table->enum('sender_type', ['user', 'bot', 'operator']);
            $table->foreignUuid('operator_id')->nullable()->constrained('users')->nullOnDelete();

            // Content type
            $table->enum('content_type', [
                'text',
                'photo',
                'video',
                'document',
                'voice',
                'audio',
                'sticker',
                'location',
                'contact',
                'callback_query',
                'command'
            ]);

            // Content
            $table->json('content');

            // Keyboard
            $table->json('keyboard')->nullable();

            // Funnel tracking
            $table->foreignUuid('funnel_id')->nullable()->constrained('telegram_funnels')->nullOnDelete();
            $table->foreignUuid('step_id')->nullable()->constrained('telegram_funnel_steps')->nullOnDelete();

            // Status
            $table->boolean('is_read')->default(false);

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['conversation_id', 'created_at']);
            $table->index('telegram_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
