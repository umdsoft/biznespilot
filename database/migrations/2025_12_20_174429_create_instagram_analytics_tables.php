<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instagram Business Accounts
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->string('instagram_id')->unique();
            $table->string('username');
            $table->string('name')->nullable();
            $table->text('biography')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->string('website')->nullable();
            $table->integer('followers_count')->default(0);
            $table->integer('follows_count')->default(0);
            $table->integer('media_count')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'integration_id']);
        });

        // Instagram Media (Posts, Reels, Carousels, Stories)
        Schema::create('instagram_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('media_id')->unique();
            $table->enum('media_type', ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM', 'REELS', 'STORY']);
            $table->string('media_product_type')->nullable(); // FEED, REELS, STORY
            $table->text('caption')->nullable();
            $table->string('permalink')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('media_url')->nullable();
            $table->integer('like_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('saves_count')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('video_views')->default(0);
            $table->integer('plays')->default(0); // For Reels
            $table->integer('replies')->default(0); // For Stories
            $table->integer('taps_forward')->default(0); // Stories
            $table->integer('taps_back')->default(0); // Stories
            $table->integer('exits')->default(0); // Stories
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('mentions')->nullable();
            $table->json('insights_data')->nullable();
            $table->timestamps();

            $table->index(['instagram_account_id', 'media_type']);
            $table->index(['instagram_account_id', 'posted_at']);
            $table->index('media_product_type');
        });

        // Instagram Daily Insights (Account level metrics per day)
        Schema::create('instagram_daily_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('profile_views')->default(0);
            $table->integer('website_clicks')->default(0);
            $table->integer('email_contacts')->default(0);
            $table->integer('phone_call_clicks')->default(0);
            $table->integer('text_message_clicks')->default(0);
            $table->integer('get_directions_clicks')->default(0);
            $table->integer('follower_count')->default(0);
            $table->integer('followers_gained')->default(0);
            $table->integer('followers_lost')->default(0);
            $table->json('online_followers')->nullable(); // Hourly breakdown
            $table->json('audience_city')->nullable();
            $table->json('audience_country')->nullable();
            $table->json('audience_gender_age')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['instagram_account_id', 'date']);
            $table->index(['business_id', 'date']);
        });

        // Instagram Audience Demographics (Cached weekly)
        Schema::create('instagram_audience', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->json('age_gender')->nullable(); // Age-gender breakdown
            $table->json('top_cities')->nullable();
            $table->json('top_countries')->nullable();
            $table->json('online_hours')->nullable(); // Best posting times
            $table->json('online_days')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique('instagram_account_id');
        });

        // Instagram DM Analytics (Message counts, not content)
        Schema::create('instagram_dm_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('total_conversations')->default(0);
            $table->integer('new_conversations')->default(0);
            $table->integer('messages_received')->default(0);
            $table->integer('messages_sent')->default(0);
            $table->string('source_media_id')->nullable(); // Which post drove the DM
            $table->integer('dm_from_post')->default(0);
            $table->integer('dm_from_reel')->default(0);
            $table->integer('dm_from_story')->default(0);
            $table->integer('dm_from_profile')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['instagram_account_id', 'date']);
        });

        // Instagram Hashtag Performance
        Schema::create('instagram_hashtag_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('hashtag');
            $table->integer('usage_count')->default(0);
            $table->integer('total_reach')->default(0);
            $table->integer('total_impressions')->default(0);
            $table->integer('total_engagement')->default(0);
            $table->decimal('avg_engagement_rate', 8, 4)->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['instagram_account_id', 'hashtag']);
            $table->index(['business_id', 'hashtag']);
        });

        // Sync logs for tracking what was synced
        Schema::create('instagram_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->cascadeOnDelete();
            $table->enum('sync_type', ['full', 'incremental', 'media', 'insights', 'audience']);
            $table->enum('status', ['pending', 'running', 'completed', 'failed']);
            $table->integer('items_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_sync_logs');
        Schema::dropIfExists('instagram_hashtag_stats');
        Schema::dropIfExists('instagram_dm_stats');
        Schema::dropIfExists('instagram_audience');
        Schema::dropIfExists('instagram_daily_insights');
        Schema::dropIfExists('instagram_media');
        Schema::dropIfExists('instagram_accounts');
    }
};
