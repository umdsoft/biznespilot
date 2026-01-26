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
        Schema::table('content_calendar', function (Blueprint $table) {
            // Add UUID column if not exists
            if (!Schema::hasColumn('content_calendar', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }

            // Add content_text if not exists (alias for content)
            if (!Schema::hasColumn('content_calendar', 'content_text')) {
                $table->text('content_text')->nullable()->after('description');
            }

            // Add channel column if not exists (some migrations use 'platform')
            if (!Schema::hasColumn('content_calendar', 'channel')) {
                $table->string('channel', 50)->nullable()->after('content_type');
            }

            // Add format column if not exists
            if (!Schema::hasColumn('content_calendar', 'format')) {
                $table->string('format', 50)->nullable()->after('channel');
            }

            // Add channel_account if not exists
            if (!Schema::hasColumn('content_calendar', 'channel_account')) {
                $table->string('channel_account')->nullable()->after('format');
            }

            // Add scheduled_at if not exists
            if (!Schema::hasColumn('content_calendar', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('scheduled_time');
            }

            // Add timezone if not exists
            if (!Schema::hasColumn('content_calendar', 'timezone')) {
                $table->string('timezone', 50)->nullable()->default('Asia/Tashkent')->after('scheduled_at');
            }

            // Add published_at if not exists
            if (!Schema::hasColumn('content_calendar', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }

            // Add external_post_id if not exists
            if (!Schema::hasColumn('content_calendar', 'external_post_id')) {
                $table->string('external_post_id')->nullable();
            }

            // Add post_url if not exists
            if (!Schema::hasColumn('content_calendar', 'post_url')) {
                $table->string('post_url')->nullable();
            }

            // Stats columns
            if (!Schema::hasColumn('content_calendar', 'views')) {
                $table->integer('views')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'likes')) {
                $table->integer('likes')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'comments')) {
                $table->integer('comments')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'shares')) {
                $table->integer('shares')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'saves')) {
                $table->integer('saves')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'clicks')) {
                $table->integer('clicks')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'reach')) {
                $table->integer('reach')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'impressions')) {
                $table->integer('impressions')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'engagement_rate')) {
                $table->decimal('engagement_rate', 8, 4)->default(0);
            }

            // Campaign columns
            if (!Schema::hasColumn('content_calendar', 'campaign_name')) {
                $table->string('campaign_name')->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'campaign_id')) {
                $table->uuid('campaign_id')->nullable();
            }

            // AI columns
            if (!Schema::hasColumn('content_calendar', 'is_ai_generated')) {
                $table->boolean('is_ai_generated')->default(false);
            }
            if (!Schema::hasColumn('content_calendar', 'ai_suggestions')) {
                $table->json('ai_suggestions')->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'ai_caption_suggestion')) {
                $table->text('ai_caption_suggestion')->nullable();
            }

            // Other columns
            if (!Schema::hasColumn('content_calendar', 'goal')) {
                $table->string('goal', 50)->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'theme')) {
                $table->string('theme')->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'priority')) {
                $table->integer('priority')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'tags')) {
                $table->json('tags')->nullable();
            }

            // Approval columns
            if (!Schema::hasColumn('content_calendar', 'created_by')) {
                $table->uuid('created_by')->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'approved_by')) {
                $table->uuid('approved_by')->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop columns in down() as they may have been created by other migrations
    }
};
