<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarterly_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('annual_strategy_id')->nullable()->constrained('annual_strategies')->nullOnDelete();

            // Quarter identification
            $table->year('year');
            $table->unsignedTinyInteger('quarter'); // 1, 2, 3, 4
            $table->string('title');
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');

            // Theme and focus
            $table->string('theme')->nullable(); // e.g., "Growth Quarter", "Optimization"
            $table->text('executive_summary')->nullable();
            $table->json('quarterly_objectives')->nullable(); // 3-5 main objectives

            // Goals linked to annual strategy
            $table->json('goals')->nullable(); // [{name, target, metric, linked_annual_goal_id}]
            $table->json('key_results')->nullable();

            // Targets
            $table->decimal('revenue_target', 15, 2)->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->integer('lead_target')->nullable();
            $table->integer('customer_target')->nullable();

            // Action plan
            $table->json('initiatives')->nullable(); // Major initiatives for the quarter
            $table->json('campaigns')->nullable(); // Planned campaigns
            $table->json('experiments')->nullable(); // A/B tests, new channels to try

            // Channel focus
            $table->json('channel_priorities')->nullable();
            $table->json('channel_budget')->nullable();

            // Resources
            $table->json('resource_requirements')->nullable();
            $table->json('team_assignments')->nullable();

            // AI insights
            $table->json('ai_recommendations')->nullable();
            $table->text('ai_summary')->nullable();
            $table->unsignedTinyInteger('confidence_score')->nullable();

            // Progress
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->json('monthly_breakdown')->nullable();
            $table->json('actual_results')->nullable();

            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['business_id', 'year', 'quarter']);
            $table->index(['annual_strategy_id', 'quarter']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarterly_plans');
    }
};
