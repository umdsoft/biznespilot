<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Reklama razvedkasi - Meta Ad Library, reklama kreativlari
     */
    public function up(): void
    {
        Schema::create('competitor_ads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('platform', 50); // facebook, instagram, google, tiktok
            $table->string('ad_id')->nullable(); // Meta Ad Library ID
            $table->string('page_id')->nullable();
            $table->string('page_name')->nullable();

            // Ad content
            $table->text('headline')->nullable();
            $table->text('body_text')->nullable();
            $table->text('link_caption')->nullable();
            $table->string('call_to_action', 100)->nullable();
            $table->string('destination_url')->nullable();

            // Media
            $table->string('media_type', 50)->nullable(); // image, video, carousel
            $table->json('media_urls')->nullable(); // Array of media URLs
            $table->string('thumbnail_url')->nullable();

            // Ad details
            $table->string('ad_status', 50)->default('active'); // active, inactive, removed
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->integer('days_running')->default(0);
            $table->boolean('is_active')->default(true);

            // Targeting & reach
            $table->json('targeting_countries')->nullable();
            $table->json('targeting_demographics')->nullable();
            $table->json('targeting_interests')->nullable();
            $table->string('reach_estimate', 100)->nullable(); // "10K-50K"

            // Spend estimates (if available)
            $table->decimal('estimated_spend_min', 12, 2)->nullable();
            $table->decimal('estimated_spend_max', 12, 2)->nullable();
            $table->string('currency', 10)->default('UZS');

            // Analysis
            $table->json('detected_products')->nullable();
            $table->json('detected_offers')->nullable();
            $table->string('ad_category', 100)->nullable(); // promo, awareness, conversion
            $table->decimal('creative_score', 5, 2)->nullable(); // AI-based score

            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->index(['competitor_id', 'platform']);
            $table->index(['competitor_id', 'is_active']);
            $table->index('started_at');
            $table->unique(['competitor_id', 'platform', 'ad_id']);
        });

        // Ad spend aggregated stats
        Schema::create('competitor_ad_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('platform', 50);
            $table->date('stat_date');

            // Counts
            $table->integer('total_active_ads')->default(0);
            $table->integer('new_ads')->default(0);
            $table->integer('stopped_ads')->default(0);

            // Spend estimates
            $table->decimal('estimated_daily_spend_min', 12, 2)->nullable();
            $table->decimal('estimated_daily_spend_max', 12, 2)->nullable();
            $table->decimal('estimated_monthly_spend_min', 12, 2)->nullable();
            $table->decimal('estimated_monthly_spend_max', 12, 2)->nullable();

            // Ad types breakdown
            $table->integer('image_ads_count')->default(0);
            $table->integer('video_ads_count')->default(0);
            $table->integer('carousel_ads_count')->default(0);

            // Top performing
            $table->uuid('longest_running_ad_id')->nullable();
            $table->integer('longest_running_days')->default(0);

            $table->timestamps();

            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->unique(['competitor_id', 'platform', 'stat_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_ad_stats');
        Schema::dropIfExists('competitor_ads');
    }
};
