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
        Schema::create('content_generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Generatsiya so'rovi
            $table->string('topic'); // Mavzu
            $table->text('prompt')->nullable(); // Foydalanuvchi qo'shimcha ko'rsatmasi
            $table->enum('content_type', ['post', 'story', 'reel', 'ad', 'carousel', 'article'])->default('post');
            $table->enum('purpose', ['educate', 'inspire', 'sell', 'engage', 'announce', 'entertain'])->default('engage');
            $table->string('target_channel')->nullable(); // instagram, telegram, facebook

            // Generatsiya natijasi
            $table->text('generated_content')->nullable();
            $table->json('generated_hashtags')->nullable();
            $table->json('generated_variations')->nullable(); // A/B test uchun variantlar
            /*
            [
                {"content": "Variant 1...", "hook_type": "question"},
                {"content": "Variant 2...", "hook_type": "statistic"},
                {"content": "Variant 3...", "hook_type": "story"}
            ]
            */

            // AI parametrlari
            $table->string('ai_model')->default('claude-3-haiku');
            $table->float('temperature')->default(0.7);
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->float('cost_usd')->default(0); // Token narxi

            // Reference templates
            $table->json('reference_template_ids')->nullable(); // Qaysi templatelardan o'rgangan

            // Holat
            $table->enum('status', ['pending', 'generating', 'completed', 'failed', 'published'])->default('pending');
            $table->text('error_message')->nullable();

            // Foydalanuvchi feedback
            $table->enum('user_rating', ['good', 'neutral', 'bad'])->nullable();
            $table->text('user_feedback')->nullable();
            $table->boolean('was_edited')->default(false);
            $table->text('edited_content')->nullable();
            $table->boolean('was_published')->default(false);

            // Publish tracking
            $table->string('published_post_id')->nullable();
            $table->timestamp('published_at')->nullable();

            // Performance (agar publish qilinsa)
            $table->float('post_engagement_rate')->nullable();
            $table->integer('post_likes')->nullable();
            $table->integer('post_comments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'content_type']);
            $table->index(['business_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_generations');
    }
};
