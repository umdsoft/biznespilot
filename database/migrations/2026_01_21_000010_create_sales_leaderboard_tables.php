<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sotuv leaderboard jadvallari
     * Raqobat va reyting tizimi uchun
     */
    public function up(): void
    {
        // Leaderboard yozuvlari - kunlik/haftalik/oylik reytinglar
        Schema::create('sales_leaderboard_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Davr ma'lumotlari
            $table->string('period_type', 20); // daily, weekly, monthly
            $table->date('period_start');
            $table->date('period_end');

            // Reyting ma'lumotlari
            $table->integer('rank')->default(0); // 1, 2, 3...
            $table->integer('previous_rank')->nullable(); // Oldingi davrdagi reyting
            $table->integer('rank_change')->default(0); // +2, -1, 0

            // KPI natijalari
            $table->decimal('total_score', 8, 2)->default(0); // Umumiy ball
            $table->decimal('weighted_score', 8, 2)->default(0); // Vaznli ball
            $table->json('kpi_scores')->nullable(); // Har bir KPI bo'yicha ball

            // Faoliyat statistikasi
            $table->integer('leads_converted')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->integer('calls_made')->default(0);
            $table->integer('tasks_completed')->default(0);

            // Qo'shimcha metrikalar
            $table->decimal('conversion_rate', 8, 2)->default(0);
            $table->decimal('avg_deal_size', 15, 2)->default(0);

            // Medal yoki mukofot
            $table->string('medal', 20)->nullable(); // gold, silver, bronze
            $table->boolean('is_top_performer')->default(false);

            $table->timestamps();

            // Unikal indeks
            $table->unique(
                ['business_id', 'user_id', 'period_type', 'period_start'],
                'unique_leaderboard_entry'
            );

            // Qidiruv indekslari
            $table->index(['business_id', 'period_type', 'period_start', 'rank'], 'sle_biz_type_start_rank_idx');
            $table->index(['user_id', 'period_type'], 'sle_user_type_idx');
            $table->index(['business_id', 'medal'], 'sle_biz_medal_idx');
        });

        // Leaderboard history - tarixiy rekordlar
        Schema::create('sales_leaderboard_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Rekord turi
            $table->string('record_type', 50); // highest_daily_score, most_leads_month, longest_streak, etc.
            $table->string('record_period', 20); // daily, weekly, monthly, all_time

            // Rekord qiymatlari
            $table->decimal('record_value', 15, 2);
            $table->date('achieved_at');
            $table->json('context_data')->nullable(); // Qo'shimcha ma'lumotlar

            // Hali ham rekordmi?
            $table->boolean('is_current_record')->default(true);
            $table->foreignUuid('broken_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('broken_at')->nullable();

            $table->timestamps();

            // Indekslar
            $table->index(['business_id', 'record_type', 'is_current_record'], 'slr_biz_type_current_idx');
            $table->index(['user_id', 'record_type'], 'slr_user_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_leaderboard_records');
        Schema::dropIfExists('sales_leaderboard_entries');
    }
};
