<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_channel_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('telegram_channel_id')
                ->constrained('telegram_channels')
                ->cascadeOnDelete();

            $table->date('stat_date');

            // Subscriber counts
            $table->integer('subscriber_count')->default(0);
            $table->integer('new_subscribers')->default(0);
            $table->integer('left_subscribers')->default(0);
            $table->integer('net_growth')->default(0);

            // Post metrics
            $table->integer('posts_count')->default(0);
            $table->integer('total_views')->default(0);
            $table->integer('average_views')->default(0);
            $table->integer('total_reactions')->default(0);
            $table->integer('total_forwards')->default(0);
            $table->integer('total_replies')->default(0);

            // Calculated
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->decimal('growth_rate', 5, 2)->default(0);

            // Top post of the day
            $table->uuid('top_post_id')->nullable();

            $table->timestamps();

            $table->unique(['telegram_channel_id', 'stat_date'], 'tcds_channel_date_idx');
            $table->index('stat_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_channel_daily_stats');
    }
};
