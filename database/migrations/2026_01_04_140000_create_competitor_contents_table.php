<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Kontent tahlili - postlar, engagement, hashtags
     */
    public function up(): void
    {
        Schema::create('competitor_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('platform', 50); // instagram, telegram, facebook, tiktok
            $table->string('content_type', 50); // post, reel, story, video, carousel
            $table->string('external_id')->nullable(); // Platform post ID
            $table->text('caption')->nullable();
            $table->json('hashtags')->nullable(); // ['hashtag1', 'hashtag2']
            $table->json('mentions')->nullable(); // ['@user1', '@user2']
            $table->string('media_type', 50)->nullable(); // image, video, carousel
            $table->string('media_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('permalink')->nullable();

            // Engagement metrics
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('saves')->default(0);
            $table->integer('views')->default(0);
            $table->decimal('engagement_rate', 8, 4)->nullable();

            // Analysis
            $table->string('sentiment', 20)->nullable(); // positive, negative, neutral
            $table->json('topics')->nullable(); // AI-detected topics
            $table->boolean('is_sponsored')->default(false);
            $table->boolean('is_viral')->default(false); // engagement > threshold

            // Timing
            $table->timestamp('published_at')->nullable();
            $table->string('day_of_week', 20)->nullable();
            $table->integer('hour_of_day')->nullable();

            $table->timestamps();

            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->index(['competitor_id', 'platform']);
            $table->index(['competitor_id', 'published_at']);
            $table->index('content_type');
            $table->unique(['competitor_id', 'platform', 'external_id']);
        });

        // Content aggregated stats per day
        Schema::create('competitor_content_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('platform', 50);
            $table->date('stat_date');

            // Daily posting stats
            $table->integer('posts_count')->default(0);
            $table->integer('reels_count')->default(0);
            $table->integer('stories_count')->default(0);
            $table->integer('videos_count')->default(0);

            // Daily engagement
            $table->integer('total_likes')->default(0);
            $table->integer('total_comments')->default(0);
            $table->integer('total_shares')->default(0);
            $table->integer('total_views')->default(0);
            $table->decimal('avg_engagement_rate', 8, 4)->nullable();

            // Best performing content
            $table->uuid('top_content_id')->nullable();
            $table->integer('top_content_engagement')->default(0);

            // Hashtag analysis
            $table->json('top_hashtags')->nullable(); // [{tag: '#sale', count: 5}]

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
        Schema::dropIfExists('competitor_content_stats');
        Schema::dropIfExists('competitor_contents');
    }
};
