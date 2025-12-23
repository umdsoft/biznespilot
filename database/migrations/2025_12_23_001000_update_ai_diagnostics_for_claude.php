<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_diagnostics', function (Blueprint $table) {
            // Status and overall
            $table->enum('status_level', ['critical', 'weak', 'medium', 'good', 'excellent'])->nullable()->after('overall_score');
            $table->text('status_message')->nullable()->after('status_level');
            $table->unsignedTinyInteger('industry_avg_score')->nullable()->after('status_message');

            // JSON analysis fields
            $table->json('money_loss_analysis')->nullable()->after('industry_avg_score');
            $table->json('similar_businesses')->nullable()->after('money_loss_analysis');
            $table->json('ideal_customer_analysis')->nullable()->after('similar_businesses');
            $table->json('offer_strength')->nullable()->after('ideal_customer_analysis');
            $table->json('channels_analysis')->nullable()->after('offer_strength');
            $table->json('funnel_analysis')->nullable()->after('channels_analysis');
            $table->json('automation_analysis')->nullable()->after('funnel_analysis');
            $table->json('risks')->nullable()->after('automation_analysis');
            $table->json('action_plan')->nullable()->after('risks');
            $table->json('expected_results')->nullable()->after('action_plan');
            $table->json('platform_recommendations')->nullable()->after('expected_results');
            $table->json('recommended_videos')->nullable()->after('platform_recommendations');

            // AI Meta
            $table->string('ai_model', 50)->default('claude-3-sonnet')->after('recommended_videos');
            $table->unsignedInteger('tokens_used')->default(0)->after('ai_model');
            $table->unsignedInteger('generation_time_ms')->nullable()->after('tokens_used');
            $table->timestamp('expires_at')->nullable()->after('generation_time_ms');
        });

        // Business Success Stories
        Schema::create('business_success_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('industry', 100);
            $table->string('sub_industry', 100)->nullable();

            // Results
            $table->unsignedTinyInteger('initial_score')->nullable();
            $table->unsignedTinyInteger('final_score')->nullable();
            $table->decimal('growth_percent', 5, 2)->nullable();
            $table->unsignedTinyInteger('duration_months')->nullable();

            // Actions
            $table->json('actions_taken')->nullable();
            $table->json('metrics_before')->nullable();
            $table->json('metrics_after')->nullable();

            // Display
            $table->string('display_name', 100)->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['industry', 'growth_percent'], 'idx_industry_score');
        });

        // Diagnostic Action Progress
        Schema::create('diagnostic_action_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('diagnostic_id');
            $table->uuid('business_id');

            $table->unsignedTinyInteger('step_order');
            $table->string('step_title', 255);
            $table->string('module_route', 100)->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Result
            $table->unsignedTinyInteger('result_score_before')->nullable();
            $table->unsignedTinyInteger('result_score_after')->nullable();

            $table->timestamps();

            $table->foreign('diagnostic_id')->references('id')->on('ai_diagnostics')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['diagnostic_id', 'step_order'], 'idx_diagnostic_step');
        });

        // Update industry_benchmarks with more fields
        Schema::table('industry_benchmarks', function (Blueprint $table) {
            $table->string('industry', 100)->nullable()->after('id');
            $table->string('sub_industry', 100)->nullable()->after('industry');

            // Average metrics
            $table->decimal('avg_health_score', 5, 2)->nullable()->after('sub_industry');
            $table->decimal('avg_conversion_rate', 5, 2)->nullable()->after('avg_health_score');
            $table->decimal('avg_engagement_rate', 5, 2)->nullable()->after('avg_conversion_rate');
            $table->unsignedInteger('avg_response_time_minutes')->nullable()->after('avg_engagement_rate');
            $table->decimal('avg_repeat_purchase_rate', 5, 2)->nullable()->after('avg_response_time_minutes');

            // Top 10% metrics
            $table->decimal('top_health_score', 5, 2)->nullable()->after('avg_repeat_purchase_rate');
            $table->decimal('top_conversion_rate', 5, 2)->nullable()->after('top_health_score');
            $table->decimal('top_engagement_rate', 5, 2)->nullable()->after('top_conversion_rate');
            $table->unsignedInteger('top_response_time_minutes')->nullable()->after('top_engagement_rate');
            $table->decimal('top_repeat_purchase_rate', 5, 2)->nullable()->after('top_response_time_minutes');

            // Optimal parameters
            $table->unsignedInteger('optimal_post_frequency_weekly')->nullable()->after('top_repeat_purchase_rate');
            $table->unsignedInteger('optimal_stories_daily')->nullable()->after('optimal_post_frequency_weekly');
            $table->string('optimal_caption_length', 20)->nullable()->after('optimal_stories_daily');
            $table->string('optimal_hashtag_count', 20)->nullable()->after('optimal_caption_length');
            $table->json('optimal_posting_times')->nullable()->after('optimal_hashtag_count');

            // Tactics
            $table->json('proven_tactics')->nullable()->after('optimal_posting_times');
            $table->unsignedInteger('businesses_count')->default(0)->after('proven_tactics');
            $table->timestamp('last_calculated_at')->nullable()->after('businesses_count');
        });
    }

    public function down(): void
    {
        Schema::table('industry_benchmarks', function (Blueprint $table) {
            $table->dropColumn([
                'industry', 'sub_industry',
                'avg_health_score', 'avg_conversion_rate', 'avg_engagement_rate',
                'avg_response_time_minutes', 'avg_repeat_purchase_rate',
                'top_health_score', 'top_conversion_rate', 'top_engagement_rate',
                'top_response_time_minutes', 'top_repeat_purchase_rate',
                'optimal_post_frequency_weekly', 'optimal_stories_daily',
                'optimal_caption_length', 'optimal_hashtag_count', 'optimal_posting_times',
                'proven_tactics', 'businesses_count', 'last_calculated_at'
            ]);
        });

        Schema::dropIfExists('diagnostic_action_progress');
        Schema::dropIfExists('business_success_stories');

        Schema::table('ai_diagnostics', function (Blueprint $table) {
            $table->dropColumn([
                'status_level', 'status_message', 'industry_avg_score',
                'money_loss_analysis', 'similar_businesses', 'ideal_customer_analysis',
                'offer_strength', 'channels_analysis', 'funnel_analysis',
                'automation_analysis', 'risks', 'action_plan', 'expected_results',
                'platform_recommendations', 'recommended_videos',
                'ai_model', 'tokens_used', 'generation_time_ms', 'expires_at'
            ]);
        });
    }
};
