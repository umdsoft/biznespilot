<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Obro' va sharhlar - Google, 2GIS, Yandex reviews
     */
    public function up(): void
    {
        // Review sources/locations for competitor
        Schema::create('competitor_review_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('platform', 50); // google, 2gis, yandex, facebook, telegram
            $table->string('place_id')->nullable(); // Google Place ID, 2GIS ID
            $table->string('profile_url')->nullable();
            $table->string('name')->nullable(); // Business name on platform

            // Current ratings
            $table->decimal('current_rating', 3, 2)->nullable(); // 4.5
            $table->integer('total_reviews')->default(0);
            $table->decimal('rating_trend', 5, 2)->nullable(); // +0.2 or -0.1

            // Rating breakdown
            $table->integer('five_star_count')->default(0);
            $table->integer('four_star_count')->default(0);
            $table->integer('three_star_count')->default(0);
            $table->integer('two_star_count')->default(0);
            $table->integer('one_star_count')->default(0);

            // Tracking
            $table->boolean('is_tracked')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->unique(['competitor_id', 'platform', 'place_id']);
            $table->index(['competitor_id', 'platform']);
        });

        // Individual reviews
        Schema::create('competitor_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_id'); // competitor_review_sources.id
            $table->uuid('competitor_id');
            $table->string('review_id')->nullable(); // Platform review ID
            $table->string('author_name')->nullable();
            $table->string('author_avatar')->nullable();
            $table->text('review_text')->nullable();
            $table->integer('rating'); // 1-5
            $table->timestamp('review_date')->nullable();

            // Platform-specific
            $table->integer('likes_count')->default(0);
            $table->boolean('has_owner_response')->default(false);
            $table->text('owner_response')->nullable();
            $table->timestamp('owner_response_date')->nullable();

            // Analysis
            $table->string('sentiment', 20)->nullable(); // positive, negative, neutral
            $table->decimal('sentiment_score', 5, 4)->nullable(); // -1 to 1
            $table->json('topics')->nullable(); // ['service', 'price', 'quality']
            $table->json('keywords')->nullable(); // Key phrases extracted
            $table->boolean('is_fake_suspected')->default(false);

            // Flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_critical')->default(false); // Needs attention

            $table->timestamps();

            $table->foreign('source_id')->references('id')->on('competitor_review_sources')->onDelete('cascade');
            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->unique(['source_id', 'review_id']);
            $table->index(['competitor_id', 'sentiment']);
            $table->index(['competitor_id', 'rating']);
            $table->index('review_date');
        });

        // Review stats over time
        Schema::create('competitor_review_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('platform', 50);
            $table->date('stat_date');

            // Counts
            $table->integer('new_reviews_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->decimal('cumulative_rating', 3, 2)->nullable();
            $table->integer('cumulative_reviews')->default(0);

            // Sentiment breakdown
            $table->integer('positive_reviews')->default(0);
            $table->integer('neutral_reviews')->default(0);
            $table->integer('negative_reviews')->default(0);
            $table->decimal('sentiment_score', 5, 4)->nullable(); // Average

            // Response metrics
            $table->integer('responded_reviews')->default(0);
            $table->decimal('response_rate', 5, 2)->nullable();
            $table->integer('avg_response_time_hours')->nullable();

            // Top topics
            $table->json('top_positive_topics')->nullable();
            $table->json('top_negative_topics')->nullable();

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
        Schema::dropIfExists('competitor_review_stats');
        Schema::dropIfExists('competitor_reviews');
        Schema::dropIfExists('competitor_review_sources');
    }
};
