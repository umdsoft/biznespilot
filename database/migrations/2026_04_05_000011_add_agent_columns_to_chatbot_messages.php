<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mavjud chatbot_messages jadvaliga sotuv agenti uchun yangi ustunlar qo'shish.
 * Lead baholash, intent aniqlash, e'tiroz turi, AI model va token ma'lumotlari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->integer('lead_score_snapshot')->nullable()->after('metadata');
            $table->string('intent_detected', 50)->nullable()->after('lead_score_snapshot');
            $table->string('objection_type', 50)->nullable()->after('intent_detected');
            $table->string('ai_model_used', 20)->nullable()->after('objection_type');
            $table->integer('tokens_used')->default(0)->after('ai_model_used');
            $table->enum('response_source', ['rule', 'cache', 'haiku', 'sonnet'])->default('rule')->after('tokens_used');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->dropColumn([
                'lead_score_snapshot', 'intent_detected', 'objection_type',
                'ai_model_used', 'tokens_used', 'response_source',
            ]);
        });
    }
};
