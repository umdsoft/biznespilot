<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend product_analyses table
        Schema::table('product_analyses', function (Blueprint $table) {
            $table->uuid('product_id')->nullable()->after('business_id');
            $table->decimal('cost', 15, 2)->nullable()->after('price');
            $table->json('features')->nullable()->after('target_audience');
            $table->string('pricing_model')->default('one_time')->after('category'); // one_time, subscription, freemium
            $table->string('life_cycle_stage')->default('growth')->after('marketing_status'); // introduction, growth, maturity, decline
            $table->integer('trend_alignment_score')->default(0)->after('weaknesses_count');
            $table->integer('competitor_position_score')->default(0)->after('trend_alignment_score');
            $table->json('ai_analysis')->nullable()->after('metadata');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_analysis');
            $table->json('sales_summary')->nullable()->after('ai_analyzed_at');
            $table->timestamp('sales_updated_at')->nullable()->after('sales_summary');
        });

        // Product ↔ Competitor product mappings
        Schema::create('product_competitor_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_analysis_id')->constrained('product_analyses')->cascadeOnDelete();
            $table->foreignUuid('competitor_product_id')->constrained('competitor_products')->cascadeOnDelete();
            $table->integer('similarity_score')->default(50); // 0-100
            $table->text('comparison_notes')->nullable();
            $table->string('mapped_by')->default('manual'); // manual, ai
            $table->timestamps();

            $table->unique(['product_analysis_id', 'competitor_product_id'], 'pcm_unique');
        });

        // Product insights (quick actionable recommendations)
        Schema::create('product_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_analysis_id')->nullable()->constrained('product_analyses')->nullOnDelete();
            $table->string('type'); // price_alert, usp_gap, marketing_gap, competitor_threat, trend_shift, sales_decline
            $table->string('priority')->default('medium'); // high, medium, low
            $table->string('title');
            $table->text('description');
            $table->string('action_text')->nullable();
            $table->json('data')->nullable();
            $table->string('status')->default('active'); // active, dismissed, acted_on
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['product_analysis_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_insights');
        Schema::dropIfExists('product_competitor_mappings');

        Schema::table('product_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'product_id', 'cost', 'features', 'pricing_model', 'life_cycle_stage',
                'trend_alignment_score', 'competitor_position_score',
                'ai_analysis', 'ai_analyzed_at', 'sales_summary', 'sales_updated_at',
            ]);
        });
    }
};
