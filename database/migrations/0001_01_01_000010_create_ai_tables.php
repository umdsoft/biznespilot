<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI Diagnostics (v2 — soddalashtirilgan)
        if (! Schema::hasTable('ai_diagnostics')) {
            Schema::create('ai_diagnostics', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('business_id');
                $table->string('type', 50)->default('full');
                $table->string('status', 20)->default('pending');
                $table->json('input_data')->nullable();
                $table->json('results')->nullable();
                $table->decimal('overall_score', 5, 2)->nullable();
                $table->json('recommendations')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
                $table->index('business_id');
                $table->index('type');
                $table->index('status');
            });
        }

        // Industry Benchmarks
        Schema::create('industry_benchmarks', function (Blueprint $table) {
            $table->id();

            // Industry identifiers
            $table->uuid('industry_id')->nullable();
            $table->string('industry', 100)->nullable()->index();
            $table->string('sub_industry', 100)->nullable();

            // Metric information
            $table->string('metric_code', 100)->nullable()->index();
            $table->string('metric_name')->nullable();
            $table->string('metric_name_uz')->nullable();
            $table->string('metric_name_en')->nullable();
            $table->string('metric_type', 50)->nullable();
            $table->text('description')->nullable();

            // Threshold values (for traditional metrics)
            $table->decimal('min_value', 14, 2)->nullable();
            $table->decimal('max_value', 14, 2)->nullable();
            $table->decimal('avg_value', 14, 2)->nullable();
            $table->decimal('average_value', 15, 4)->nullable();
            $table->decimal('good_value', 14, 2)->nullable();
            $table->decimal('excellent_value', 14, 2)->nullable();
            $table->decimal('poor_threshold', 15, 4)->nullable();
            $table->decimal('good_threshold', 15, 4)->nullable();
            $table->decimal('excellent_threshold', 15, 4)->nullable();

            // Unit and direction
            $table->string('unit', 50)->nullable();
            $table->string('direction', 50)->nullable();

            // AI Diagnostics fields
            $table->decimal('avg_health_score', 5, 2)->nullable();
            $table->decimal('avg_conversion_rate', 7, 2)->nullable();
            $table->decimal('avg_engagement_rate', 7, 2)->nullable();
            $table->integer('avg_response_time_minutes')->nullable();
            $table->decimal('avg_repeat_purchase_rate', 7, 2)->nullable();

            $table->decimal('top_health_score', 5, 2)->nullable();
            $table->decimal('top_conversion_rate', 7, 2)->nullable();
            $table->decimal('top_engagement_rate', 7, 2)->nullable();
            $table->integer('top_response_time_minutes')->nullable();
            $table->decimal('top_repeat_purchase_rate', 7, 2)->nullable();

            // Social media optimization
            $table->integer('optimal_post_frequency_weekly')->nullable();
            $table->integer('optimal_stories_daily')->nullable();
            $table->integer('optimal_caption_length')->nullable();
            $table->integer('optimal_hashtag_count')->nullable();
            $table->json('optimal_posting_times')->nullable();
            $table->json('proven_tactics')->nullable();

            // Additional algorithm fields
            $table->decimal('churn_rate', 7, 2)->nullable();
            $table->json('funnel_conversion')->nullable();
            $table->json('social_benchmarks')->nullable();
            $table->json('content_benchmarks')->nullable();

            // Metadata
            $table->integer('businesses_count')->nullable()->default(0);
            $table->string('source', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->integer('year')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('industry_id')->references('id')->on('industries')->onDelete('set null');
            $table->index(['industry_id', 'metric_type']);
            $table->index(['industry', 'sub_industry']);
            $table->index(['metric_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_benchmarks');
        Schema::dropIfExists('ai_diagnostics');
    }
};
