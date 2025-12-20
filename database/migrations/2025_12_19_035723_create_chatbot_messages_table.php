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
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->onDelete('cascade');
            $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
            $table->enum('sender_type', ['customer', 'bot', 'human'])->default('customer');
            $table->text('content');
            $table->string('message_type')->default('text'); // text, image, video, audio, file, etc.
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at');
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
