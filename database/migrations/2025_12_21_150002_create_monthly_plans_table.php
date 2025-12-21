<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('quarterly_plan_id')->nullable()->constrained('quarterly_plans')->nullOnDelete();

            // Month identification
            $table->year('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->string('title');
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');

            // Theme and focus
            $table->string('theme')->nullable();
            $table->text('executive_summary')->nullable();
            $table->json('monthly_objectives')->nullable();

            // Goals
            $table->json('goals')->nullable(); // [{name, target, metric, weekly_targets}]
            $table->json('okrs')->nullable();

            // Targets
            $table->decimal('revenue_target', 15, 2)->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->integer('lead_target')->nullable();
            $table->integer('customer_target')->nullable();
            $table->integer('content_pieces_target')->nullable();
            $table->integer('posts_target')->nullable();

            // Week-by-week breakdown
            $table->json('week_1_plan')->nullable();
            $table->json('week_2_plan')->nullable();
            $table->json('week_3_plan')->nullable();
            $table->json('week_4_plan')->nullable();
            $table->json('week_5_plan')->nullable(); // Some months have 5 weeks

            // Content strategy
            $table->json('content_themes')->nullable();
            $table->json('content_types')->nullable(); // posts, stories, reels, articles
            $table->json('content_calendar_summary')->nullable();

            // Marketing activities
            $table->json('campaigns')->nullable();
            $table->json('promotions')->nullable();
            $table->json('events')->nullable();

            // Channel strategy
            $table->json('channel_focus')->nullable();
            $table->json('channel_budget')->nullable();
            $table->json('channel_targets')->nullable();

            // Sales activities
            $table->json('sales_activities')->nullable();
            $table->json('offers')->nullable();
            $table->json('pricing_actions')->nullable();

            // AI insights
            $table->json('ai_recommendations')->nullable();
            $table->json('ai_content_suggestions')->nullable();
            $table->text('ai_summary')->nullable();
            $table->unsignedTinyInteger('confidence_score')->nullable();

            // Progress tracking
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->json('weekly_progress')->nullable();
            $table->json('actual_results')->nullable();
            $table->decimal('success_rate', 5, 2)->nullable();

            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['business_id', 'year', 'month']);
            $table->index(['quarterly_plan_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_plans');
    }
};
