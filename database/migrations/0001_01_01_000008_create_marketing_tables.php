<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marketing Channels
        Schema::create('marketing_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('type', 50); // social, search, email, referral, etc.
            $table->string('platform', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('monthly_budget', 12, 2)->default(0);
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('access_token')->nullable();
            $table->string('account_id')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('type');
            $table->index('is_active');
        });

        // Marketing Spends
        Schema::create('marketing_spends', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('channel_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('UZS');
            $table->date('spend_date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('marketing_channels')->onDelete('cascade');

            $table->index('business_id');
            $table->index('channel_id');
            $table->index('spend_date');
            $table->index(['business_id', 'spend_date']);
        });

        // Competitors
        Schema::create('competitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->json('social_links')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->string('price_range', 50)->nullable();
            $table->string('market_position', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
        });

        // Competitor Activities
        Schema::create('competitor_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('type', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->date('activity_date');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->index('competitor_id');
            $table->index('activity_date');
            $table->softDeletes();
        });

        // Competitor Metrics
        Schema::create('competitor_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('metric_type', 50);
            $table->string('platform', 50)->nullable();
            $table->decimal('value', 14, 2);
            $table->date('recorded_date');
            $table->timestamps();

            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->index(['competitor_id', 'recorded_date']);
            $table->softDeletes();
        });

        // Competitor Alerts
        Schema::create('competitor_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('competitor_id');
            $table->string('type', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity', 20)->default('medium');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->index(['business_id', 'is_read']);
            $table->softDeletes();
        });

        // Campaigns
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('channel_id')->nullable();
            $table->string('name');
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('budget', 12, 2)->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('target_audience')->nullable();
            $table->json('settings')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('marketing_channels')->onDelete('set null');

            $table->index('business_id');
            $table->index('status');
            $table->softDeletes();
        });

        // Campaign Messages
        Schema::create('campaign_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->string('type', 50);
            $table->string('subject')->nullable();
            $table->text('content');
            $table->json('media')->nullable();
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->index('campaign_id');
            $table->softDeletes();
        });

        // Content Posts
        Schema::create('content_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('channel_id')->nullable();
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('type', 50)->default('post');
            $table->string('status', 20)->default('draft');
            $table->json('media')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('marketing_channels')->onDelete('set null');

            $table->index('business_id');
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_posts');
        Schema::dropIfExists('campaign_messages');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('competitor_alerts');
        Schema::dropIfExists('competitor_metrics');
        Schema::dropIfExists('competitor_activities');
        Schema::dropIfExists('competitors');
        Schema::dropIfExists('marketing_spends');
        Schema::dropIfExists('marketing_channels');
    }
};
