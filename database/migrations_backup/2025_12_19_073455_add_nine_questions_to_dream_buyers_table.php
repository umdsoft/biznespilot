<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 9 ta savol - "Sell Like Crazy" kitobidan
     */
    public function up(): void
    {
        Schema::table('dream_buyers', function (Blueprint $table) {
            // 1. Qayerda vaqt o'tkazadi?
            $table->text('where_spend_time')->nullable()->after('data');

            // 2. Ma'lumot olish uchun qayerga murojaat qiladi?
            $table->text('info_sources')->nullable();

            // 3. Eng katta frustratsiyalari va qiyinchiliklari?
            $table->text('frustrations')->nullable();

            // 4. Orzulari va umidlari?
            $table->text('dreams')->nullable();

            // 5. Eng katta qo'rquvlari?
            $table->text('fears')->nullable();

            // 6. Qaysi kommunikatsiya shaklini afzal ko'radi?
            $table->text('communication_preferences')->nullable();

            // 7. Qanday til va jargon ishlatadi?
            $table->text('language_style')->nullable();

            // 8. Kundalik hayoti qanday?
            $table->text('daily_routine')->nullable();

            // 9. Nima uni baxtli qiladi?
            $table->text('happiness_triggers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dream_buyers', function (Blueprint $table) {
            $table->dropColumn([
                'where_spend_time',
                'info_sources',
                'frustrations',
                'dreams',
                'fears',
                'communication_preferences',
                'language_style',
                'daily_routine',
                'happiness_triggers',
            ]);
        });
    }
};
