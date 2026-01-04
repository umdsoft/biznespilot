<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_ads_ad_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('google_ads_campaigns')->onDelete('cascade');
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('google_ad_group_id')->nullable();
            $table->string('name');
            $table->string('status')->default('PAUSED'); // ENABLED, PAUSED, REMOVED
            $table->string('type')->nullable(); // SEARCH_STANDARD, DISPLAY_STANDARD, etc.
            $table->decimal('cpc_bid', 15, 2)->nullable();
            $table->decimal('cpm_bid', 15, 2)->nullable();

            // Targeting
            $table->json('targeting')->nullable();
            $table->json('audience_settings')->nullable();

            // Aggregated metrics
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->bigInteger('total_impressions')->default(0);
            $table->bigInteger('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->decimal('avg_cpc', 10, 4)->default(0);
            $table->decimal('avg_ctr', 10, 4)->default(0);
            $table->decimal('avg_quality_score', 5, 2)->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'google_ad_group_id']);
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ads_ad_groups');
    }
};
