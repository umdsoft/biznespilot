<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instagram Metrics
        Schema::create('instagram_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketing_channel_id');
            $table->date('metric_date');

            // Profile metrics
            $table->integer('followers_count')->default(0);
            $table->integer('following_count')->default(0);
            $table->integer('media_count')->default(0);

            // Engagement metrics
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('saves')->default(0);

            // Reach & Impressions
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('profile_views')->default(0);

            // Stories metrics
            $table->integer('stories_posted')->default(0);
            $table->integer('stories_reach')->default(0);
            $table->integer('stories_impressions')->default(0);
            $table->integer('stories_replies')->default(0);

            // Reels metrics
            $table->integer('reels_posted')->default(0);
            $table->integer('reels_plays')->default(0);
            $table->integer('reels_reach')->default(0);
            $table->integer('reels_likes')->default(0);
            $table->integer('reels_comments')->default(0);
            $table->integer('reels_shares')->default(0);

            // Calculated metrics
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->integer('new_followers')->default(0);
            $table->integer('lost_followers')->default(0);

            // Website clicks
            $table->integer('website_clicks')->default(0);
            $table->integer('email_contacts')->default(0);
            $table->integer('phone_calls')->default(0);

            $table->timestamps();

            $table->foreign('marketing_channel_id')->references('id')->on('marketing_channels')->onDelete('cascade');
            $table->unique(['marketing_channel_id', 'metric_date']);
            $table->index('metric_date');
        });

        // Telegram Metrics
        Schema::create('telegram_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketing_channel_id');
            $table->date('metric_date');

            // Channel metrics
            $table->integer('subscribers_count')->default(0);
            $table->integer('new_subscribers')->default(0);
            $table->integer('left_subscribers')->default(0);

            // Engagement metrics
            $table->integer('views')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('reactions')->default(0);
            $table->integer('comments')->default(0);

            // Post metrics
            $table->integer('posts_count')->default(0);
            $table->decimal('views_per_post', 10, 2)->default(0);

            // Engagement rate
            $table->decimal('engagement_rate', 5, 2)->default(0);

            $table->timestamps();

            $table->foreign('marketing_channel_id')->references('id')->on('marketing_channels')->onDelete('cascade');
            $table->unique(['marketing_channel_id', 'metric_date']);
            $table->index('metric_date');
        });

        // Facebook Metrics
        Schema::create('facebook_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketing_channel_id');
            $table->date('metric_date');

            // Page metrics
            $table->integer('page_likes')->default(0);
            $table->integer('page_followers')->default(0);
            $table->integer('page_reach')->default(0);
            $table->integer('page_impressions')->default(0);

            // Engagement metrics
            $table->integer('post_likes')->default(0);
            $table->integer('post_comments')->default(0);
            $table->integer('post_shares')->default(0);
            $table->integer('post_clicks')->default(0);

            // Video metrics
            $table->integer('video_views')->default(0);
            $table->integer('video_3s_views')->default(0);

            // Calculated metrics
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->integer('new_likes')->default(0);
            $table->integer('unlikes')->default(0);

            $table->timestamps();

            $table->foreign('marketing_channel_id')->references('id')->on('marketing_channels')->onDelete('cascade');
            $table->unique(['marketing_channel_id', 'metric_date']);
            $table->index('metric_date');
        });

        // Google Ads Metrics
        Schema::create('google_ads_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketing_channel_id');
            $table->date('metric_date');

            // Campaign info
            $table->string('campaign_id')->nullable();
            $table->string('campaign_name')->nullable();

            // Cost metrics
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('currency', 3)->default('UZS');

            // Performance metrics
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_value', 12, 2)->default(0);

            // Calculated metrics
            $table->decimal('ctr', 5, 2)->default(0); // Click-through rate
            $table->decimal('cpc', 10, 2)->default(0); // Cost per click
            $table->decimal('cpa', 10, 2)->default(0); // Cost per acquisition
            $table->decimal('roas', 8, 2)->default(0); // Return on ad spend

            $table->timestamps();

            $table->foreign('marketing_channel_id')->references('id')->on('marketing_channels')->onDelete('cascade');
            $table->unique(['marketing_channel_id', 'metric_date', 'campaign_id'], 'google_ads_unique');
            $table->index('metric_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ads_metrics');
        Schema::dropIfExists('facebook_metrics');
        Schema::dropIfExists('telegram_metrics');
        Schema::dropIfExists('instagram_metrics');
    }
};
