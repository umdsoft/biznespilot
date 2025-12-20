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
        Schema::create('google_ads_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_channel_id')->constrained()->onDelete('cascade');
            $table->date('metric_date'); // Daily metrics

            // Campaign info
            $table->string('campaign_id')->nullable();
            $table->string('campaign_name')->nullable();
            $table->string('ad_group_id')->nullable();
            $table->string('ad_group_name')->nullable();

            // Core metrics
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);

            // Cost metrics (in kopeks for precision)
            $table->bigInteger('cost')->default(0); // Store in kopeks/cents
            $table->bigInteger('avg_cpc')->default(0); // Cost per click
            $table->bigInteger('avg_cpm')->default(0); // Cost per 1000 impressions
            $table->bigInteger('avg_cpa')->default(0); // Cost per acquisition

            // Quality metrics
            $table->decimal('quality_score', 3, 1)->nullable();
            $table->decimal('ctr', 5, 2)->default(0); // Click-through rate %
            $table->decimal('conversion_rate', 5, 2)->default(0); // Percentage

            // Value metrics
            $table->bigInteger('conversion_value')->default(0); // Revenue in kopeks
            $table->decimal('roas', 8, 2)->default(0); // Return on Ad Spend

            // Engagement metrics
            $table->integer('video_views')->default(0);
            $table->integer('video_quartile_25')->default(0);
            $table->integer('video_quartile_50')->default(0);
            $table->integer('video_quartile_75')->default(0);
            $table->integer('video_quartile_100')->default(0);

            $table->timestamps();

            $table->index(['marketing_channel_id', 'metric_date']);
            $table->index('campaign_id');
            $table->index('metric_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_ads_metrics');
    }
};
