<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Soha bo'yicha mavzu natijalari — "Jamoaviy Aql" uchun
        Schema::create('niche_topic_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->string('topic', 255);
            $table->string('category', 100)->nullable(); // educational, promotional, engagement, behind_scenes
            $table->string('content_type', 50)->default('post'); // post, reel, story, carousel
            $table->unsignedInteger('total_posts')->default(0);
            $table->unsignedBigInteger('total_engagement')->default(0);
            $table->decimal('avg_engagement_rate', 8, 4)->default(0);
            $table->unsignedBigInteger('avg_reach')->default(0);
            $table->unsignedInteger('avg_saves')->default(0);
            $table->unsignedInteger('avg_shares')->default(0);
            $table->decimal('score', 5, 2)->default(0); // 0-100 weighted niche score
            $table->string('trend', 20)->default('stable'); // rising, stable, falling
            $table->json('contributing_businesses')->nullable(); // anonim business_id lar
            $table->json('sample_hashtags')->nullable();
            $table->json('best_posting_times')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['industry_id', 'topic', 'content_type'], 'nts_industry_topic_type_unique');
            $table->index(['industry_id', 'score'], 'nts_industry_score_idx');
            $table->index('trend');
        });

        // 2. So'rovnoma og'riqlari → Kontent xaritasi
        Schema::create('pain_point_content_maps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('pain_point_category', 100); // frustrations, fears, dreams, daily_routine
            $table->text('pain_point_text');
            $table->json('extracted_keywords')->nullable(); // og'riqdan ajratilgan kalit so'zlar
            $table->json('suggested_topics')->nullable(); // tavsiya etilgan mavzular
            $table->json('suggested_content_types')->nullable();
            $table->json('suggested_hooks')->nullable(); // e'tibor tortuvchi hook lar
            $table->decimal('relevance_score', 5, 2)->default(0);
            $table->unsignedInteger('times_used')->default(0);
            $table->decimal('avg_engagement_rate', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'pain_point_category'], 'ppcm_biz_category_idx');
            $table->index(['business_id', 'relevance_score'], 'ppcm_biz_relevance_idx');
        });

        // 3. Kontent plan generatsiyasi tarixi
        Schema::create('content_plan_generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_type', 20); // weekly, monthly
            $table->foreignUuid('weekly_plan_id')->nullable()->constrained('weekly_plans')->nullOnDelete();
            $table->foreignUuid('monthly_plan_id')->nullable()->constrained('monthly_plans')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->json('input_data')->nullable(); // industry, pain_points, niche_scores
            $table->json('niche_scores_used')->nullable(); // qaysi niche scorlar ishlatilgan
            $table->json('pain_points_used')->nullable(); // qaysi og'riqlar ishlatilgan
            $table->json('algorithm_breakdown')->nullable(); // scoring tafsiloti
            $table->unsignedInteger('items_generated')->default(0);
            $table->string('status', 20)->default('generated'); // generated, approved, active, completed
            $table->decimal('performance_score', 5, 2)->nullable(); // yakuniy natija
            $table->json('performance_details')->nullable(); // batafsil natija
            $table->timestamps();

            $table->index(['business_id', 'status'], 'cpg_biz_status_idx');
            $table->index(['business_id', 'plan_type', 'period_start'], 'cpg_biz_type_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_plan_generations');
        Schema::dropIfExists('pain_point_content_maps');
        Schema::dropIfExists('niche_topic_scores');
    }
};
