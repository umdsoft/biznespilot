<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained('meta_ad_accounts')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // Object reference
            $table->string('object_type'); // account, campaign, adset, ad
            $table->string('object_id');
            $table->string('object_name')->nullable();

            // Date
            $table->date('date');

            // Core Metrics
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('reach')->default(0);
            $table->decimal('frequency', 10, 4)->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->bigInteger('unique_clicks')->default(0);
            $table->bigInteger('link_clicks')->default(0);
            $table->decimal('cpc', 15, 4)->default(0);
            $table->decimal('cpm', 15, 4)->default(0);
            $table->decimal('cpp', 15, 4)->default(0);
            $table->decimal('ctr', 10, 4)->default(0);
            $table->decimal('unique_ctr', 10, 4)->default(0);
            $table->decimal('spend', 15, 2)->default(0);

            // Engagement
            $table->integer('post_engagement')->default(0);
            $table->integer('page_engagement')->default(0);
            $table->integer('post_reactions')->default(0);
            $table->integer('post_comments')->default(0);
            $table->integer('post_shares')->default(0);
            $table->integer('post_saves')->default(0);

            // Video Metrics
            $table->integer('video_views')->default(0);
            $table->integer('video_views_p25')->default(0);
            $table->integer('video_views_p50')->default(0);
            $table->integer('video_views_p75')->default(0);
            $table->integer('video_views_p100')->default(0);
            $table->decimal('video_avg_time_watched', 10, 2)->default(0);

            // Conversions
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_value', 15, 2)->default(0);
            $table->decimal('cost_per_conversion', 15, 4)->default(0);
            $table->decimal('roas', 10, 4)->default(0);

            // Actions (JSON)
            $table->json('actions')->nullable();
            $table->json('action_values')->nullable();
            $table->json('cost_per_action_type')->nullable();

            // Breakdowns
            $table->string('age_range')->nullable();
            $table->string('gender')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('publisher_platform')->nullable();
            $table->string('platform_position')->nullable();
            $table->string('device_platform')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'date']);
            $table->index(['ad_account_id', 'object_type', 'date']);
            $table->index(['business_id', 'publisher_platform', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_insights');
    }
};
