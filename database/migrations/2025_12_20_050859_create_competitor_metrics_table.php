<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('competitor_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained()->onDelete('cascade');
            $table->date('date')->index();

            // Instagram metrics
            $table->integer('instagram_followers')->nullable();
            $table->integer('instagram_following')->nullable();
            $table->integer('instagram_posts')->nullable();
            $table->decimal('instagram_engagement_rate', 5, 2)->nullable();
            $table->integer('instagram_avg_likes')->nullable();
            $table->integer('instagram_avg_comments')->nullable();

            // Telegram metrics
            $table->integer('telegram_members')->nullable();
            $table->integer('telegram_posts_count')->nullable();
            $table->decimal('telegram_engagement_rate', 5, 2)->nullable();
            $table->integer('telegram_avg_views')->nullable();

            // Facebook metrics
            $table->integer('facebook_followers')->nullable();
            $table->integer('facebook_likes')->nullable();
            $table->integer('facebook_posts')->nullable();
            $table->decimal('facebook_engagement_rate', 5, 2)->nullable();

            // TikTok metrics
            $table->integer('tiktok_followers')->nullable();
            $table->integer('tiktok_likes')->nullable();
            $table->integer('tiktok_videos')->nullable();
            $table->decimal('tiktok_engagement_rate', 5, 2)->nullable();

            // YouTube metrics
            $table->integer('youtube_subscribers')->nullable();
            $table->integer('youtube_videos')->nullable();
            $table->integer('youtube_total_views')->nullable();

            // Website metrics (if available)
            $table->integer('website_traffic')->nullable();
            $table->integer('website_page_views')->nullable();
            $table->decimal('website_bounce_rate', 5, 2)->nullable();

            // Growth rates (calculated)
            $table->decimal('follower_growth_rate', 5, 2)->nullable(); // % change from previous period
            $table->decimal('engagement_growth_rate', 5, 2)->nullable();

            // Metadata
            $table->json('raw_data')->nullable(); // Store original API response
            $table->string('data_source')->nullable(); // manual, api, scrape

            $table->timestamps();

            $table->unique(['competitor_id', 'date']);
            $table->index(['competitor_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_metrics');
    }
};
