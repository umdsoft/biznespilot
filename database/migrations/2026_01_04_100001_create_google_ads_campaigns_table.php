<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_ads_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('ad_integration_id');
            $table->foreign('ad_integration_id')->references('id')->on('ad_integrations')->onDelete('cascade');
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('google_campaign_id')->nullable();
            $table->string('name');
            $table->string('advertising_channel_type')->default('SEARCH'); // SEARCH, DISPLAY, VIDEO, SHOPPING
            $table->string('status')->default('PAUSED'); // ENABLED, PAUSED, REMOVED
            $table->string('serving_status')->nullable(); // SERVING, NONE, ENDED, PENDING, SUSPENDED
            $table->string('bidding_strategy_type')->nullable();
            $table->decimal('daily_budget', 15, 2)->nullable();
            $table->decimal('lifetime_budget', 15, 2)->nullable();
            $table->string('budget_delivery_method')->default('STANDARD'); // STANDARD, ACCELERATED
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Targeting settings stored as JSON
            $table->json('targeting_settings')->nullable();
            $table->json('geo_targets')->nullable();
            $table->json('device_targets')->nullable();
            $table->json('language_targets')->nullable();

            // Aggregated metrics
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->bigInteger('total_impressions')->default(0);
            $table->bigInteger('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->decimal('total_conversion_value', 15, 2)->default(0);
            $table->decimal('avg_cpc', 10, 4)->default(0);
            $table->decimal('avg_cpm', 10, 4)->default(0);
            $table->decimal('avg_ctr', 10, 4)->default(0);
            $table->decimal('avg_conversion_rate', 10, 4)->default(0);
            $table->decimal('roas', 10, 4)->default(0);

            // Sync info
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['ad_integration_id', 'google_campaign_id']);
            $table->index(['business_id', 'status']);
            $table->index('google_campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ads_campaigns');
    }
};
