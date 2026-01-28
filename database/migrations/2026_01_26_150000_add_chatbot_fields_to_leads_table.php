<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instagram Chatbot → CRM Integratsiyasi
 *
 * Bu migratsiya leads jadvaliga chatbot bilan bog'liq ustunlarni qo'shadi.
 * Maqsad: Instagram DM/Comment/Story dan kelgan murojaatlarni Lead sifatida saqlash.
 *
 * Foydalanish:
 * - Chatbot mijoz bilan suhbatlashadi
 * - Intent aniqlanadi (order, complaint, consultation)
 * - Lead avtomatik yaratiladi va CRM da ko'rinadi
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Instagram Conversation bog'lanishi
            $table->uuid('instagram_conversation_id')
                ->nullable()
                ->after('data')
                ->comment('InstagramConversation jadvaliga bog\'lanish');

            // Chatbot manba turi
            $table->string('chatbot_source_type', 30)
                ->nullable()
                ->after('instagram_conversation_id')
                ->comment('dm, comment, story_reply, story_mention');

            // Aniqlangan intent
            $table->string('chatbot_detected_intent', 50)
                ->nullable()
                ->after('chatbot_source_type')
                ->comment('order, complaint, consultation, price_inquiry, human_handoff');

            // Birinchi xabar matni
            $table->text('chatbot_first_message')
                ->nullable()
                ->after('chatbot_detected_intent')
                ->comment('Mijozning birinchi xabari');

            // Chatbot orqali yig'ilgan ma'lumotlar
            $table->json('chatbot_data')
                ->nullable()
                ->after('chatbot_first_message')
                ->comment('Voronka davomida yig\'ilgan ma\'lumotlar');

            // Indekslar
            $table->index('instagram_conversation_id');
            $table->index('chatbot_source_type');
            $table->index('chatbot_detected_intent');
        });

        // Foreign key constraint (agar instagram_conversations jadvali mavjud bo'lsa)
        if (Schema::hasTable('instagram_conversations')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreign('instagram_conversation_id')
                    ->references('id')
                    ->on('instagram_conversations')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Foreign key ni o'chirish
            if (Schema::hasTable('instagram_conversations')) {
                $table->dropForeign(['instagram_conversation_id']);
            }

            // Indekslarni o'chirish
            $table->dropIndex(['instagram_conversation_id']);
            $table->dropIndex(['chatbot_source_type']);
            $table->dropIndex(['chatbot_detected_intent']);

            // Ustunlarni o'chirish
            $table->dropColumn([
                'instagram_conversation_id',
                'chatbot_source_type',
                'chatbot_detected_intent',
                'chatbot_first_message',
                'chatbot_data',
            ]);
        });
    }
};
