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
        Schema::create('instagram_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_channel_id')->constrained()->onDelete('cascade');
            $table->date('metric_date'); // Daily metrics

            // Profile metrics
            $table->integer('followers_count')->default(0);
            $table->integer('following_count')->default(0);
            $table->integer('media_count')->default(0);

            // Engagement metrics
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('saves')->default(0);

            // Reach & Impressions
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('profile_views')->default(0);

            // Stories metrics
            $table->integer('stories_posted')->default(0);
            $table->integer('stories_reach')->default(0);
            $table->integer('stories_impressions')->default(0);
            $table->integer('stories_replies')->default(0);

            // Reels metrics
            $table->integer('reels_posted')->default(0);
            $table->integer('reels_plays')->default(0);
            $table->integer('reels_reach')->default(0);
            $table->integer('reels_likes')->default(0);
            $table->integer('reels_comments')->default(0);
            $table->integer('reels_shares')->default(0);

            // Calculated metrics
            $table->decimal('engagement_rate', 5, 2)->default(0); // Percentage
            $table->integer('new_followers')->default(0);
            $table->integer('lost_followers')->default(0);

            // Website clicks
            $table->integer('website_clicks')->default(0);
            $table->integer('email_contacts')->default(0);
            $table->integer('phone_calls')->default(0);

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
        Schema::dropIfExists('instagram_metrics');
    }
};
