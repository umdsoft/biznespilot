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
        Schema::create('facebook_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_channel_id')->constrained()->onDelete('cascade');
            $table->date('metric_date'); // Daily metrics

            // Page metrics
            $table->integer('page_likes')->default(0);
            $table->integer('page_followers')->default(0);
            $table->integer('new_likes')->default(0);
            $table->integer('new_followers')->default(0);

            // Post metrics
            $table->integer('posts_count')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);

            // Engagement metrics
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('reactions')->default(0);

            // Video metrics
            $table->integer('video_views')->default(0);
            $table->integer('video_reach')->default(0);
            $table->decimal('average_watch_time', 8, 2)->default(0); // seconds

            // Page views
            $table->integer('page_views')->default(0);
            $table->integer('page_views_unique')->default(0);

            // CTA metrics
            $table->integer('cta_clicks')->default(0);
            $table->integer('website_clicks')->default(0);
            $table->integer('phone_clicks')->default(0);
            $table->integer('direction_clicks')->default(0);

            // Calculated metrics
            $table->decimal('engagement_rate', 5, 2)->default(0); // Percentage

            $table->timestamps();

            $table->unique(['marketing_channel_id', 'metric_date']);
            $table->index('metric_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_metrics');
    }
};
