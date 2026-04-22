<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_channel_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('telegram_channel_id')
                ->constrained('telegram_channels')
                ->cascadeOnDelete();

            // Telegram message_id within the channel
            $table->bigInteger('message_id');
            $table->timestamp('posted_at');

            // text | photo | video | document | animation | audio | voice | poll | location | other
            $table->string('content_type', 30)->default('text');
            $table->integer('media_count')->default(0);
            $table->text('text_preview')->nullable();

            // Cumulative counters (refreshed periodically)
            $table->integer('views')->default(0);
            $table->integer('reactions_count')->default(0);
            $table->integer('forwards_count')->default(0);
            $table->integer('replies_count')->default(0);

            // Delta since previous snapshot (filled by sync job)
            $table->integer('views_delta_24h')->default(0);
            $table->integer('reactions_delta_24h')->default(0);

            $table->timestamp('last_snapshot_at')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->unique(['telegram_channel_id', 'message_id']);
            $table->index(['telegram_channel_id', 'posted_at']);
            $table->index('posted_at');
        });

        Schema::create('telegram_channel_post_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('telegram_channel_post_id')
                ->constrained('telegram_channel_posts')
                ->cascadeOnDelete();

            $table->timestamp('snapshot_at');
            $table->integer('views')->default(0);
            $table->integer('reactions_count')->default(0);
            $table->integer('forwards_count')->default(0);

            $table->timestamps();

            $table->index(['telegram_channel_post_id', 'snapshot_at'], 'tcps_post_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_channel_post_snapshots');
        Schema::dropIfExists('telegram_channel_posts');
    }
};
