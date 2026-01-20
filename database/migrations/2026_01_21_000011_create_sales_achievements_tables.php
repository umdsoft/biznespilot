<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Yutuqlar va streaklar jadvallari
     * Gamification tizimi uchun
     */
    public function up(): void
    {
        // Yutuq ta'riflari - qanday yutuqlar bor
        Schema::create('sales_achievement_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();

            // Asosiy ma'lumotlar
            $table->string('code', 50)->unique(); // first_sale, sales_master, streak_7, etc.
            $table->string('name'); // "Birinchi sotuv"
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable(); // Icon nomi yoki URL

            // Kategoriya va daraja
            $table->string('category', 30); // sales, activity, quality, streak, special
            $table->string('tier', 20)->default('bronze'); // bronze, silver, gold, platinum, diamond
            $table->integer('points')->default(0); // Yutuq uchun beriladigan ball

            // Qo'lga kiritish shartlari
            $table->string('trigger_type', 30); // threshold, cumulative, streak, milestone, special
            $table->string('metric', 50); // leads_converted, revenue, calls_made, etc.
            $table->decimal('target_value', 15, 2); // Maqsad qiymat
            $table->json('conditions')->nullable(); // Qo'shimcha shartlar

            // Takrorlanish
            $table->boolean('is_repeatable')->default(false); // Bir martami yoki ko'p marta
            $table->integer('max_times')->nullable(); // Maksimal takrorlanish

            // Holat
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // Tizim tomonidan yaratilgan
            $table->boolean('is_secret')->default(false); // Yashirin yutuq

            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indekslar
            $table->index(['business_id', 'category', 'is_active'], 'sad_biz_cat_active_idx');
            $table->index(['trigger_type', 'metric'], 'sad_trigger_metric_idx');
        });

        // Foydalanuvchi yutuqlari
        Schema::create('sales_user_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('achievement_id')->constrained('sales_achievement_definitions')->cascadeOnDelete();

            // Qachon olindi
            $table->timestamp('earned_at');
            $table->integer('times_earned')->default(1); // Necha marta olindi

            // Qanday qilib olindi
            $table->decimal('achieved_value', 15, 2)->nullable(); // Qiymat
            $table->json('context_data')->nullable(); // Qo'shimcha ma'lumotlar

            // Ball
            $table->integer('points_awarded')->default(0);

            // Foydalanuvchi ko'rdimi
            $table->boolean('is_seen')->default(false);
            $table->timestamp('seen_at')->nullable();

            // Pinned (profilda ko'rsatish)
            $table->boolean('is_pinned')->default(false);

            $table->timestamps();

            // Indekslar
            $table->index(['user_id', 'earned_at'], 'sua_user_earned_idx');
            $table->index(['business_id', 'achievement_id'], 'sua_biz_ach_idx');
            $table->index(['user_id', 'is_seen'], 'sua_user_seen_idx');
        });

        // Streaklar - ketma-ket kunlik yutuqlar
        Schema::create('sales_user_streaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Streak turi
            $table->string('streak_type', 50); // daily_target, calls, tasks, login, etc.

            // Hozirgi streak
            $table->integer('current_streak')->default(0);
            $table->date('streak_start_date')->nullable();
            $table->date('last_activity_date')->nullable();

            // Eng yaxshi streak
            $table->integer('best_streak')->default(0);
            $table->date('best_streak_start')->nullable();
            $table->date('best_streak_end')->nullable();

            // Statistika
            $table->integer('total_streaks')->default(0); // Jami boshlangan streaklar
            $table->integer('total_streak_days')->default(0); // Jami streak kunlari

            // Bonus
            $table->decimal('streak_multiplier', 5, 2)->default(1.0); // Streak bonus multiplier
            $table->boolean('is_frozen')->default(false); // Streak muzlatilganmi (ta'til)
            $table->date('frozen_until')->nullable();

            $table->timestamps();

            // Unikal indeks
            $table->unique(['business_id', 'user_id', 'streak_type'], 'unique_user_streak');

            // Qidiruv indekslari
            $table->index(['business_id', 'streak_type', 'current_streak'], 'sus_biz_type_streak_idx');
            $table->index(['user_id', 'streak_type'], 'sus_user_type_idx');
        });

        // Streak tarix - streak o'zgarishlari
        Schema::create('sales_streak_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('streak_id')->constrained('sales_user_streaks')->cascadeOnDelete();

            // O'zgarish turi
            $table->string('event_type', 30); // increment, break, freeze, unfreeze, milestone

            // Qiymatlar
            $table->integer('streak_value'); // O'sha paytdagi streak
            $table->date('event_date');
            $table->json('event_data')->nullable();

            $table->timestamps();

            // Indeks
            $table->index(['streak_id', 'event_date']);
        });

        // Gamification points - umumiy ball tizimi
        Schema::create('sales_user_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Umumiy ballar
            $table->integer('total_points')->default(0);
            $table->integer('available_points')->default(0); // Sarflash mumkin
            $table->integer('spent_points')->default(0); // Sarflangan

            // Daraja
            $table->integer('level')->default(1);
            $table->integer('experience')->default(0); // XP
            $table->integer('next_level_xp')->default(100); // Keyingi daraja uchun

            // Statistika
            $table->integer('achievements_count')->default(0);
            $table->integer('best_rank')->nullable();
            $table->integer('gold_medals')->default(0);
            $table->integer('silver_medals')->default(0);
            $table->integer('bronze_medals')->default(0);

            $table->timestamps();

            // Unikal
            $table->unique(['business_id', 'user_id'], 'unique_user_points');

            // Indeks
            $table->index(['business_id', 'total_points']);
            $table->index(['business_id', 'level']);
        });

        // Points tarix
        Schema::create('sales_points_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_points_id')->constrained('sales_user_points')->cascadeOnDelete();

            // Tranzaksiya turi
            $table->string('type', 30); // earned, spent, bonus, penalty, expired
            $table->string('source', 50); // achievement, streak, leaderboard, manual, etc.
            $table->foreignUuid('source_id')->nullable(); // Manba ID

            // Qiymat
            $table->integer('points'); // + yoki -
            $table->integer('balance_after'); // Tranzaksiyadan keyingi balans

            // Tavsif
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indeks
            $table->index(['user_points_id', 'created_at']);
            $table->index(['type', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_points_transactions');
        Schema::dropIfExists('sales_user_points');
        Schema::dropIfExists('sales_streak_history');
        Schema::dropIfExists('sales_user_streaks');
        Schema::dropIfExists('sales_user_achievements');
        Schema::dropIfExists('sales_achievement_definitions');
    }
};
