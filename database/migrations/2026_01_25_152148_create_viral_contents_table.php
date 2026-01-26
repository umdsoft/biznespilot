<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Viral Contents - TrendSee Module
 *
 * Stores viral Instagram Reels/posts for analysis and inspiration.
 * Data fetched via RapidAPI (RocketAPI) and analyzed by AI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viral_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')
                ->nullable()
                ->constrained('businesses')
                ->cascadeOnDelete();

            // Platform identification
            $table->string('platform', 20)->default('instagram');
            $table->string('platform_id', 100)->unique()->comment('Original post ID from Instagram');
            $table->string('platform_username', 100)->nullable();

            // Content data
            $table->string('niche', 100)->index()->comment('Hashtag niche category');
            $table->text('caption')->nullable();
            $table->text('video_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->text('permalink')->nullable();

            // Metrics
            $table->json('metrics_json')->nullable()->comment('plays, likes, comments, shares');
            $table->unsignedBigInteger('play_count')->default(0)->index();
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('comment_count')->default(0);

            // AI Analysis
            $table->json('ai_analysis_json')->nullable()->comment('hook_score, summary, psychology, replication_tip');
            $table->tinyInteger('hook_score')->nullable()->comment('1-10 viral potential score');
            $table->text('ai_summary')->nullable()->comment('Short AI-generated summary');

            // Music/Audio info
            $table->string('music_title', 255)->nullable();
            $table->string('music_artist', 255)->nullable();

            // Processing status
            $table->boolean('is_processed')->default(false)->index();
            $table->boolean('is_super_viral')->default(false)->index()->comment('500k+ views');
            $table->boolean('alert_sent')->default(false);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('analyzed_at')->nullable();

            $table->timestamps();

            // Indexes for queries
            $table->index(['niche', 'play_count']);
            $table->index(['platform', 'is_processed']);
            $table->index(['is_super_viral', 'alert_sent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viral_contents');
    }
};
