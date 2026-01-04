<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_ads_campaign_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('google_ads_campaigns')->onDelete('cascade');
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->date('date');

            // Core Metrics
            $table->decimal('cost', 15, 2)->default(0);
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->decimal('ctr', 10, 4)->default(0);
            $table->decimal('cpc', 10, 4)->default(0);
            $table->decimal('cpm', 10, 4)->default(0);

            // Conversions
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_rate', 10, 4)->default(0);
            $table->decimal('conversion_value', 15, 2)->default(0);
            $table->decimal('cost_per_conversion', 10, 4)->default(0);
            $table->decimal('roas', 10, 4)->default(0);

            // Quality Metrics
            $table->decimal('avg_quality_score', 5, 2)->nullable();
            $table->decimal('search_impression_share', 10, 4)->nullable();

            // Video Metrics
            $table->bigInteger('video_views')->default(0);
            $table->decimal('video_view_rate', 10, 4)->nullable();

            $table->json('actions')->nullable();
            $table->json('breakdown_data')->nullable();

            $table->timestamps();

            $table->unique(['campaign_id', 'date']);
            $table->index(['business_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ads_campaign_insights');
    }
};
