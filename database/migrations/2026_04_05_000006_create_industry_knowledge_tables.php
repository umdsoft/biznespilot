<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sohaviy bilim jadvallari.
 * E'tiroz javoblari, kontent benchmarklar, funnel benchmarklar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sohaviy e'tiroz javoblari
        Schema::create('industry_objection_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('industry', 50);
            $table->enum('objection_type', ['price', 'trust', 'timing', 'product', 'need', 'authority']);
            $table->text('response_text');
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['industry', 'objection_type'], 'idx_industry_type');
            $table->index('success_rate', 'idx_success');
        });

        // Sohaviy kontent benchmarklar
        Schema::create('industry_content_benchmarks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('industry', 50);
            $table->string('content_type', 50); // video, image, carousel, text
            $table->string('platform', 30);     // instagram, telegram
            $table->decimal('avg_engagement_rate', 8, 4);
            $table->decimal('avg_reach_rate', 8, 4);
            $table->integer('sample_count');
            $table->json('optimal_times')->nullable();
            $table->json('optimal_caption_length')->nullable();
            $table->json('optimal_hashtag_count')->nullable();
            $table->json('best_ctas')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->index(['industry', 'platform'], 'idx_industry_platform');
        });

        // Sohaviy funnel benchmarklar
        Schema::create('industry_funnel_benchmarks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('industry', 50);
            $table->string('stage_from', 50);
            $table->string('stage_to', 50);
            $table->decimal('avg_conversion_rate', 5, 2);
            $table->decimal('avg_time_hours', 8, 2)->nullable();
            $table->integer('sample_count');
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->index('industry', 'idx_industry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_funnel_benchmarks');
        Schema::dropIfExists('industry_content_benchmarks');
        Schema::dropIfExists('industry_objection_responses');
    }
};
