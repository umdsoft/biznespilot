<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KPI davr yig'indisi jadvali
     * Haftalik va oylik umumiy natijalarni saqlaydi
     */
    public function up(): void
    {
        Schema::create('sales_kpi_period_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Davr ma'lumotlari
            $table->string('period_type', 20); // weekly, monthly, quarterly
            $table->date('period_start');
            $table->date('period_end');

            // Umumiy natijalar
            $table->integer('overall_score')->default(0); // 0-100 umumiy ball
            $table->decimal('total_weight', 5, 2)->default(0); // Jami vazn
            $table->decimal('weighted_score', 8, 2)->default(0); // Vaznli ball

            // Har bir KPI bo'yicha ballar (tezkor kirish uchun)
            $table->json('kpi_scores')->nullable();
            /*
            Format:
            [
                {
                    "kpi_setting_id": "uuid",
                    "kpi_type": "leads_converted",
                    "actual": 15,
                    "target": 10,
                    "achievement_percent": 150,
                    "score": 92,
                    "weight": 25
                },
                ...
            ]
            */

            // Reyting
            $table->integer('rank_in_team')->nullable(); // Jamoadagi o'rni
            $table->integer('previous_rank')->nullable(); // Oldingi davrdagi o'rni
            $table->integer('rank_change')->default(0); // O'zgarish (+/-)

            // Performance daraja
            $table->string('performance_tier', 30)->default('developing');
            // exceptional, excellent, good, meets, developing, needs_improvement

            // Meta
            $table->integer('working_days')->default(0); // Ish kunlari soni
            $table->integer('active_kpis_count')->default(0); // Faol KPIlar soni

            $table->timestamps();

            // Unikal indeks
            $table->unique(
                ['business_id', 'user_id', 'period_type', 'period_start'],
                'unique_period_summary'
            );

            // Tezkor qidiruv
            $table->index(['business_id', 'period_type', 'period_start'], 'skps_biz_type_start_idx');
            $table->index(['user_id', 'period_type'], 'skps_user_type_idx');
            $table->index(['business_id', 'period_start', 'overall_score'], 'skps_biz_start_score_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_kpi_period_summaries');
    }
};
