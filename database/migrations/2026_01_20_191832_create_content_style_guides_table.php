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
        Schema::create('content_style_guides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Asosiy ton va stil
            $table->enum('tone', ['formal', 'casual', 'professional', 'friendly', 'playful'])->default('professional');
            $table->enum('language_style', ['simple', 'technical', 'creative', 'persuasive'])->default('simple');

            // Emoji va formatting
            $table->enum('emoji_frequency', ['none', 'low', 'medium', 'high'])->default('medium');
            $table->json('common_emojis')->nullable(); // ["🔥", "✅", "💡"]

            // Post parametrlari
            $table->integer('avg_post_length')->default(200); // belgilar
            $table->integer('min_post_length')->default(100);
            $table->integer('max_post_length')->default(500);

            // Hashtag strategiyasi
            $table->json('common_hashtags')->nullable(); // ["#marketing", "#biznes"]
            $table->integer('avg_hashtag_count')->default(5);
            $table->boolean('use_branded_hashtags')->default(true);

            // CTA patterns
            $table->json('cta_patterns')->nullable(); // ["Hoziroq bog'laning!", "Link bio da"]
            $table->enum('cta_style', ['soft', 'direct', 'urgent', 'none'])->default('direct');

            // Posting strategiyasi
            $table->json('best_posting_times')->nullable(); // {"monday": ["09:00", "18:00"], ...}
            $table->json('content_pillars')->nullable(); // ["ta'lim", "ilhom", "sotish"]

            // Kanal bo'yicha sozlamalar
            $table->json('channel_specific_settings')->nullable();
            /*
            {
                "instagram": {"hashtag_count": 10, "use_stories": true},
                "telegram": {"use_formatting": true, "pin_important": true},
                "facebook": {"include_link": true}
            }
            */

            // Tahlil statistikasi
            $table->integer('analyzed_posts_count')->default(0);
            $table->float('avg_engagement_rate')->nullable();
            $table->json('top_performing_topics')->nullable();

            // AI model settings
            $table->string('ai_model')->default('claude-3-haiku');
            $table->float('creativity_level')->default(0.7); // 0-1 temperature

            $table->timestamp('last_analyzed_at')->nullable();
            $table->timestamps();

            $table->unique('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_style_guides');
    }
};
