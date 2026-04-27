<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * YouTube integratsiyasi uchun jadvallar.
 *
 *  - youtube_channels    — biznes ulagan YouTube kanali (OAuth orqali)
 *  - youtube_videos      — kanaldagi har bir video (uploads playlist'dan kelgan)
 *
 * Auth: OAuth 2.0 (refresh_token). Quota: 10,000 units/day default — `videos.list`
 *  va `playlistItems.list` chaqirishlar uchun yetarli (har biri 1 unit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youtube_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('connected_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // YouTube Data API identifiers
            $table->string('channel_id', 64)->unique();         // UC...
            $table->string('uploads_playlist_id', 64)->nullable(); // UU... (uploads)
            $table->string('handle', 64)->nullable();             // @handle
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('country', 8)->nullable();

            // OAuth credentials (encrypted via cast)
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Snapshot
            $table->bigInteger('subscriber_count')->default(0);
            $table->bigInteger('view_count')->default(0);
            $table->integer('video_count')->default(0);

            // Lifecycle
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });

        Schema::create('youtube_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('youtube_channel_id')->constrained()->cascadeOnDelete();

            $table->string('video_id', 24)->unique();   // YouTube video id
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();

            // Lifecycle
            $table->timestamp('published_at')->nullable();
            $table->string('privacy_status', 20)->nullable(); // public/unlisted/private
            $table->boolean('is_short')->default(false);

            // Stats
            $table->bigInteger('view_count')->default(0);
            $table->bigInteger('like_count')->default(0);
            $table->bigInteger('comment_count')->default(0);
            $table->decimal('engagement_rate', 6, 4)->nullable();
            $table->timestamp('stats_updated_at')->nullable();

            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['youtube_channel_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_videos');
        Schema::dropIfExists('youtube_channels');
    }
};
