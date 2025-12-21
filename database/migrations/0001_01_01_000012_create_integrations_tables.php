<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Integrations
        Schema::create('integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('type', 50); // amocrm, instagram, telegram, whatsapp, meta_ads, etc.
            $table->string('name');
            $table->string('status', 20)->default('inactive');
            $table->string('account_id')->nullable();
            $table->string('access_token', 1000)->nullable();
            $table->string('refresh_token', 1000)->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('type');
            $table->index('status');
            $table->unique(['business_id', 'type']);
            $table->softDeletes();
        });

        // Instagram specific tables
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('integration_id');
            $table->string('instagram_id');
            $table->string('username');
            $table->string('name')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->integer('followers_count')->default(0);
            $table->integer('following_count')->default(0);
            $table->integer('media_count')->default(0);
            $table->string('account_type', 50)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');
            $table->index('business_id');
            $table->index('instagram_id');
        });

        Schema::create('instagram_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('media_id');
            $table->string('media_type', 50);
            $table->text('caption')->nullable();
            $table->string('permalink')->nullable();
            $table->string('media_url', 1000)->nullable();
            $table->string('thumbnail_url', 1000)->nullable();
            $table->integer('like_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('saved')->default(0);
            $table->integer('shares')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->index('account_id');
            $table->index('media_id');
            $table->index('posted_at');
        });

        Schema::create('instagram_daily_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->date('insight_date');
            $table->integer('impressions')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('profile_views')->default(0);
            $table->integer('website_clicks')->default(0);
            $table->integer('email_contacts')->default(0);
            $table->integer('follower_count')->default(0);
            $table->integer('new_followers')->default(0);
            $table->integer('unfollowers')->default(0);
            $table->json('audience_demographics')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->unique(['account_id', 'insight_date']);
            $table->index('insight_date');
        });

        Schema::create('instagram_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('lead_id')->nullable();
            $table->string('conversation_id');
            $table->string('participant_id');
            $table->string('participant_username')->nullable();
            $table->string('participant_name')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->integer('messages_count')->default(0);
            $table->boolean('is_ai_enabled')->default(true);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->index('account_id');
            $table->index('conversation_id');
            $table->index('status');
        });

        Schema::create('instagram_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->string('message_id');
            $table->string('direction', 10); // inbound, outbound
            $table->string('type', 20)->default('text');
            $table->text('content')->nullable();
            $table->string('media_url', 1000)->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('instagram_conversations')->onDelete('cascade');
            $table->index('conversation_id');
            $table->index('message_id');
        });

        // Instagram Automation
        Schema::create('instagram_automations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('name');
            $table->string('type', 50); // comment_reply, dm_auto_reply, welcome_dm
            $table->string('trigger_type', 50)->nullable();
            $table->json('trigger_config')->nullable();
            $table->json('action_config')->nullable();
            $table->json('flow_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('executions_count')->default(0);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->index('account_id');
            $table->index('type');
            $table->softDeletes();
        });

        // Meta Ads Tables
        Schema::create('meta_ad_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('integration_id');
            $table->string('account_id');
            $table->string('name');
            $table->string('currency', 3)->default('USD');
            $table->string('timezone')->nullable();
            $table->string('status', 20)->default('active');
            $table->decimal('spend_cap', 14, 2)->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');
            $table->index('business_id');
        });

        Schema::create('meta_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ad_account_id');
            $table->string('campaign_id');
            $table->string('name');
            $table->string('objective', 50)->nullable();
            $table->string('status', 20)->default('active');
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->decimal('lifetime_budget', 12, 2)->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('stop_time')->nullable();
            $table->timestamps();

            $table->foreign('ad_account_id')->references('id')->on('meta_ad_accounts')->onDelete('cascade');
            $table->index('ad_account_id');
            $table->index('campaign_id');
        });

        Schema::create('meta_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ad_account_id');
            $table->uuid('campaign_id')->nullable();
            $table->date('date_start');
            $table->date('date_stop');
            $table->integer('impressions')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 12, 2)->default(0);
            $table->decimal('cpc', 8, 4)->default(0);
            $table->decimal('cpm', 8, 4)->default(0);
            $table->decimal('ctr', 5, 4)->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('cost_per_conversion', 10, 2)->default(0);
            $table->json('actions')->nullable();
            $table->timestamps();

            $table->foreign('ad_account_id')->references('id')->on('meta_ad_accounts')->onDelete('cascade');
            $table->index(['ad_account_id', 'date_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_insights');
        Schema::dropIfExists('meta_campaigns');
        Schema::dropIfExists('meta_ad_accounts');
        Schema::dropIfExists('instagram_automations');
        Schema::dropIfExists('instagram_messages');
        Schema::dropIfExists('instagram_conversations');
        Schema::dropIfExists('instagram_daily_insights');
        Schema::dropIfExists('instagram_media');
        Schema::dropIfExists('instagram_accounts');
        Schema::dropIfExists('integrations');
    }
};
