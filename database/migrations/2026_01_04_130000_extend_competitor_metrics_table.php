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
        Schema::table('competitor_metrics', function (Blueprint $table) {
            // Rename recorded_date to date for consistency with model
            // Actually, we'll keep recorded_date and update the model

            // Instagram metrics
            $table->integer('instagram_followers')->nullable()->after('recorded_date');
            $table->integer('instagram_following')->nullable()->after('instagram_followers');
            $table->integer('instagram_posts')->nullable()->after('instagram_following');
            $table->decimal('instagram_engagement_rate', 5, 2)->nullable()->after('instagram_posts');
            $table->integer('instagram_avg_likes')->nullable()->after('instagram_engagement_rate');
            $table->integer('instagram_avg_comments')->nullable()->after('instagram_avg_likes');

            // Telegram metrics
            $table->integer('telegram_members')->nullable()->after('instagram_avg_comments');
            $table->integer('telegram_posts_count')->nullable()->after('telegram_members');
            $table->decimal('telegram_engagement_rate', 5, 2)->nullable()->after('telegram_posts_count');
            $table->integer('telegram_avg_views')->nullable()->after('telegram_engagement_rate');

            // Facebook metrics
            $table->integer('facebook_followers')->nullable()->after('telegram_avg_views');
            $table->integer('facebook_likes')->nullable()->after('facebook_followers');
            $table->integer('facebook_posts')->nullable()->after('facebook_likes');
            $table->decimal('facebook_engagement_rate', 5, 2)->nullable()->after('facebook_posts');

            // TikTok metrics
            $table->integer('tiktok_followers')->nullable()->after('facebook_engagement_rate');
            $table->integer('tiktok_likes')->nullable()->after('tiktok_followers');
            $table->integer('tiktok_videos')->nullable()->after('tiktok_likes');
            $table->decimal('tiktok_engagement_rate', 5, 2)->nullable()->after('tiktok_videos');

            // YouTube metrics
            $table->integer('youtube_subscribers')->nullable()->after('tiktok_engagement_rate');
            $table->integer('youtube_videos')->nullable()->after('youtube_subscribers');
            $table->bigInteger('youtube_total_views')->nullable()->after('youtube_videos');

            // Website metrics
            $table->integer('website_traffic')->nullable()->after('youtube_total_views');
            $table->integer('website_page_views')->nullable()->after('website_traffic');
            $table->decimal('website_bounce_rate', 5, 2)->nullable()->after('website_page_views');

            // Growth rates
            $table->decimal('follower_growth_rate', 8, 2)->nullable()->after('website_bounce_rate');
            $table->decimal('engagement_growth_rate', 8, 2)->nullable()->after('follower_growth_rate');

            // Additional data
            $table->json('raw_data')->nullable()->after('engagement_growth_rate');
            $table->string('data_source', 50)->default('manual')->after('raw_data');

            // Make existing columns nullable (they were for generic format)
            $table->string('metric_type', 50)->nullable()->change();
            $table->decimal('value', 14, 2)->nullable()->change();
            $table->string('platform', 50)->nullable()->change();

            // Index for date queries
            $table->index('recorded_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitor_metrics', function (Blueprint $table) {
            $table->dropIndex(['recorded_date']);

            $table->dropColumn([
                'instagram_followers',
                'instagram_following',
                'instagram_posts',
                'instagram_engagement_rate',
                'instagram_avg_likes',
                'instagram_avg_comments',
                'telegram_members',
                'telegram_posts_count',
                'telegram_engagement_rate',
                'telegram_avg_views',
                'facebook_followers',
                'facebook_likes',
                'facebook_posts',
                'facebook_engagement_rate',
                'tiktok_followers',
                'tiktok_likes',
                'tiktok_videos',
                'tiktok_engagement_rate',
                'youtube_subscribers',
                'youtube_videos',
                'youtube_total_views',
                'website_traffic',
                'website_page_views',
                'website_bounce_rate',
                'follower_growth_rate',
                'engagement_growth_rate',
                'raw_data',
                'data_source',
            ]);
        });
    }
};
