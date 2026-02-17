<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Production tozalash: yetim jadvallarni olib tashlash.
 *
 * Bu jadvallar hech qayerda ishlatilmaydi:
 * - diagnostic_questions: model/controller yo'q
 * - diagnostic_reports: DailyBusinessDiagnosticJob AiDiagnostic ga o'tkazildi
 *
 * ai_insights, ai_conversations, chat_messages, ai_monthly_strategies
 * — allaqachon drop qilingan (oldingi migratsiya orqali)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('diagnostic_reports');
        Schema::dropIfExists('diagnostic_questions');
    }

    public function down(): void
    {
        // Bu jadvallar qayta yaratilmaydi — ular tashlab yuborilgan
    }
};
