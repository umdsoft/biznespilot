<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');

            // Period
            $table->date('week_start');
            $table->date('week_end');

            // Raw statistics (JSON)
            $table->json('summary_stats')->nullable();
            $table->json('channel_stats')->nullable();
            $table->json('operator_stats')->nullable();
            $table->json('time_stats')->nullable();
            $table->json('lost_reason_stats')->nullable();
            $table->json('trend_stats')->nullable();

            // AI results
            $table->json('ai_good_results')->nullable();
            $table->json('ai_problems')->nullable();
            $table->json('ai_recommendations')->nullable();
            $table->text('ai_next_week_goal')->nullable();
            $table->text('ai_raw_response')->nullable();

            // Meta
            $table->integer('tokens_used')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'week_start']);
            $table->index(['business_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_analytics');
    }
};
