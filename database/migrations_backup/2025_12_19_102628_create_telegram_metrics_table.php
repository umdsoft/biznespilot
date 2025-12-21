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
        Schema::create('telegram_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_channel_id')->constrained()->onDelete('cascade');
            $table->date('metric_date'); // Daily metrics

            // Channel/Group metrics
            $table->integer('members_count')->default(0);
            $table->integer('new_members')->default(0);
            $table->integer('left_members')->default(0);

            // Post metrics
            $table->integer('posts_count')->default(0);
            $table->integer('total_views')->default(0);
            $table->integer('average_views')->default(0);

            // Engagement metrics
            $table->integer('reactions')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('forwards')->default(0);
            $table->integer('shares')->default(0);

            // Bot metrics (if bot is active)
            $table->integer('bot_messages_sent')->default(0);
            $table->integer('bot_messages_received')->default(0);
            $table->integer('bot_commands_used')->default(0);
            $table->integer('bot_active_users')->default(0);

            // Link clicks
            $table->integer('link_clicks')->default(0);

            // Calculated metrics
            $table->decimal('engagement_rate', 5, 2)->default(0); // Percentage
            $table->decimal('growth_rate', 5, 2)->default(0); // Percentage

            $table->timestamps();

            $table->unique(['marketing_channel_id', 'metric_date']);
            $table->index('metric_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_metrics');
    }
};
