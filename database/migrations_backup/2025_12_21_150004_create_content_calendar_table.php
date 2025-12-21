<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_calendar', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('weekly_plan_id')->nullable()->constrained('weekly_plans')->nullOnDelete();
            $table->foreignId('monthly_plan_id')->nullable()->constrained('monthly_plans')->nullOnDelete();

            // Content details
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('content_text')->nullable();
            $table->json('media_urls')->nullable(); // Images, videos
            $table->json('hashtags')->nullable();

            // Type and format
            $table->enum('content_type', [
                'post', 'story', 'reel', 'video', 'article',
                'carousel', 'live', 'poll', 'ad', 'email', 'sms', 'other'
            ])->default('post');
            $table->string('format')->nullable(); // Image, Video, Text, etc.

            // Platform/Channel
            $table->string('channel'); // instagram, telegram, facebook, tiktok, etc.
            $table->string('channel_account')->nullable(); // Specific account if multiple

            // Scheduling
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('timezone')->default('Asia/Tashkent');

            // Status
            $table->enum('status', [
                'idea', 'draft', 'pending_review', 'approved',
                'scheduled', 'published', 'failed', 'archived'
            ])->default('idea');

            // Publishing
            $table->timestamp('published_at')->nullable();
            $table->string('external_post_id')->nullable(); // ID from the platform
            $table->string('post_url')->nullable();

            // Performance metrics (updated after publishing)
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('saves')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);

            // Campaign linkage
            $table->string('campaign_name')->nullable();
            $table->string('campaign_id')->nullable();

            // AI generated
            $table->boolean('is_ai_generated')->default(false);
            $table->json('ai_suggestions')->nullable();
            $table->text('ai_caption_suggestion')->nullable();

            // Categorization
            $table->json('tags')->nullable();
            $table->string('theme')->nullable();
            $table->string('goal')->nullable(); // awareness, engagement, conversion

            // Approval
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->integer('priority')->default(0);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'scheduled_date']);
            $table->index(['business_id', 'channel', 'status']);
            $table->index(['weekly_plan_id']);
            $table->index(['monthly_plan_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_calendar');
    }
};
