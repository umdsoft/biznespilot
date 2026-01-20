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
        Schema::create('content_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Kontent manbasi
            $table->enum('source_type', ['manual', 'instagram', 'telegram', 'facebook', 'imported'])->default('manual');
            $table->string('source_id')->nullable(); // Original post ID
            $table->string('source_url')->nullable();

            // Kontent turi
            $table->enum('content_type', ['post', 'story', 'reel', 'ad', 'carousel', 'article'])->default('post');
            $table->enum('purpose', ['educate', 'inspire', 'sell', 'engage', 'announce', 'entertain'])->default('engage');

            // Kontent
            $table->text('content'); // Asosiy matn
            $table->text('content_cleaned')->nullable(); // Hashtag va linklar olib tashlangan
            $table->json('hashtags')->nullable();
            $table->json('mentions')->nullable();
            $table->json('links')->nullable();
            $table->json('media_urls')->nullable();

            // Samaradorlik
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('saves_count')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->float('engagement_rate')->default(0);
            $table->float('performance_score')->default(0); // 0-100 hisoblangan ball

            // AI tahlil
            $table->json('ai_analysis')->nullable();
            /*
            {
                "tone": "friendly",
                "topics": ["marketing", "tips"],
                "sentiment": "positive",
                "cta_type": "direct",
                "hook_type": "question",
                "key_phrases": ["muhim", "yangilik"]
            }
            */

            // Kanal uchun moslashuv
            $table->string('target_channel')->nullable();

            // Holat
            $table->boolean('is_top_performer')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'content_type']);
            $table->index(['business_id', 'is_top_performer']);
            $table->index(['business_id', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_templates');
    }
};
