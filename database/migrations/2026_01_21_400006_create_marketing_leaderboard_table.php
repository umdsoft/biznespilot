<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_leaderboard', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Period
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('period_type', ['weekly', 'monthly'])->default('weekly');

            // Rankings
            $table->unsignedInteger('overall_rank')->default(0);
            $table->unsignedInteger('leads_rank')->default(0);
            $table->unsignedInteger('conversion_rank')->default(0);
            $table->unsignedInteger('roi_rank')->default(0);

            // Scores
            $table->decimal('overall_score', 8, 2)->default(0);
            $table->decimal('leads_score', 8, 2)->default(0);
            $table->decimal('conversion_score', 8, 2)->default(0);
            $table->decimal('roi_score', 8, 2)->default(0);

            // Achievements
            $table->json('achievements')->nullable();
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('coins_earned')->default(0);

            // Streak
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('best_streak')->default(0);

            $table->timestamps();

            // Unique constraint
            $table->unique(['business_id', 'user_id', 'period_start', 'period_type'], 'marketing_leaderboard_unique');

            // Indexes (with shorter names)
            $table->index(['business_id', 'period_start', 'overall_rank'], 'mkt_lb_period_rank_idx');
            $table->index(['business_id', 'user_id', 'period_type'], 'mkt_lb_user_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_leaderboard');
    }
};
