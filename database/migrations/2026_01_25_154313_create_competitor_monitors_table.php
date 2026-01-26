<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Competitor Monitor - Hybrid Spy Module
 *
 * Stores competitor data with "Internal vs External" logic.
 * If target is OUR client -> use internal data (Cost = $0).
 * If external -> use RapidAPI (Cost = $$$).
 *
 * Data cached for 14 days to minimize API costs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_monitors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Target identification
            $table->string('target_url', 500)->unique()->comment('Website or Instagram URL');
            $table->string('target_type', 50)->default('instagram')->comment('instagram, website, telegram');
            $table->string('target_username', 100)->nullable()->index();
            $table->string('target_domain', 255)->nullable()->index();

            // Internal matching (Hybrid Logic)
            $table->foreignUuid('internal_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('If set, this competitor is OUR client');

            $table->foreignUuid('internal_business_id')
                ->nullable()
                ->constrained('businesses')
                ->nullOnDelete()
                ->comment('Matched business in our system');

            $table->boolean('is_internal')->default(false)->index()->comment('True if matched to internal user');

            // Stats data
            $table->json('stats_json')->nullable()->comment('followers, engagement_rate, posts_count');
            $table->integer('followers_count')->nullable();
            $table->decimal('engagement_rate', 5, 2)->nullable();
            $table->integer('posts_count')->nullable();
            $table->integer('avg_likes')->nullable();
            $table->integer('avg_comments')->nullable();

            // Growth data
            $table->json('growth_json')->nullable()->comment('Historical growth data');
            $table->decimal('weekly_growth_rate', 5, 2)->nullable();
            $table->decimal('monthly_growth_rate', 5, 2)->nullable();

            // Content analysis
            $table->json('content_analysis_json')->nullable()->comment('Post frequency, best times, hashtags');
            $table->json('top_hashtags')->nullable();
            $table->string('posting_frequency', 50)->nullable()->comment('daily, 3x_week, weekly');

            // API metadata
            $table->string('data_source', 100)->nullable()->comment('rapidapi, internal, scraper');
            $table->decimal('api_cost', 8, 4)->default(0)->comment('Cost of API call');
            $table->integer('api_calls_count')->default(0);

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('When data becomes stale (14 days)');

            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['target_type', 'is_active']);
            $table->index(['is_internal', 'last_scraped_at']);
            $table->index(['target_domain', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_monitors');
    }
};
