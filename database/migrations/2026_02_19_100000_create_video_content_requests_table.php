<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_content_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('content_generation_id')->nullable()->constrained('content_generations')->nullOnDelete();

            // Video source
            $table->text('video_url');
            $table->string('platform', 30)->nullable(); // youtube, instagram, tiktok
            $table->string('video_title')->nullable();
            $table->integer('video_duration')->nullable(); // seconds
            $table->string('thumbnail_url')->nullable();

            // Processing
            $table->string('status', 30)->default('pending');
            // pending → extracting → transcribing → analyzing → generating → completed → failed
            $table->text('error_message')->nullable();

            // Transcript
            $table->longText('transcript')->nullable();
            $table->json('key_points')->nullable();
            // {topic, hooks[], facts[], story_elements[], cta, target_audience}

            // Generation settings
            $table->string('content_type', 50)->default('post'); // post, reel, carousel, story
            $table->string('purpose', 50)->default('engage');
            $table->string('target_channel', 50)->nullable(); // instagram, telegram, youtube

            // Costs
            $table->decimal('stt_cost', 10, 6)->default(0);
            $table->decimal('analysis_cost', 10, 6)->default(0);
            $table->decimal('total_cost', 10, 6)->default(0);
            $table->integer('processing_time_ms')->nullable();

            // AI metadata
            $table->string('stt_model')->nullable();
            $table->string('analysis_model')->nullable();
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_content_requests');
    }
};
