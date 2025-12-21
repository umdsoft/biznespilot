<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_strategies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('diagnostic_id')->nullable()->constrained('ai_diagnostics')->nullOnDelete();

            // Year and status
            $table->year('year');
            $table->string('title');
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');

            // Vision and summary
            $table->text('vision_statement')->nullable();
            $table->text('mission_statement')->nullable();
            $table->text('executive_summary')->nullable();

            // Strategic goals (SMART format)
            $table->json('strategic_goals')->nullable(); // [{name, description, target, metric, deadline}]
            $table->json('okrs')->nullable(); // Objectives and Key Results

            // Focus areas and priorities
            $table->json('focus_areas')->nullable(); // Top 3-5 priorities
            $table->json('growth_drivers')->nullable();
            $table->json('risk_factors')->nullable();

            // Financial targets
            $table->decimal('revenue_target', 15, 2)->nullable();
            $table->decimal('profit_target', 15, 2)->nullable();
            $table->decimal('annual_budget', 15, 2)->nullable();
            $table->json('budget_by_quarter')->nullable();

            // Marketing targets
            $table->integer('lead_target')->nullable();
            $table->integer('customer_target')->nullable();
            $table->decimal('cac_target', 10, 2)->nullable(); // Customer Acquisition Cost
            $table->decimal('ltv_target', 10, 2)->nullable(); // Customer Lifetime Value

            // Channel strategy
            $table->json('primary_channels')->nullable();
            $table->json('channel_budget_allocation')->nullable();

            // AI-generated insights
            $table->json('ai_recommendations')->nullable();
            $table->text('ai_summary')->nullable();
            $table->unsignedTinyInteger('confidence_score')->nullable();

            // Progress tracking
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->json('milestones')->nullable();
            $table->json('actual_results')->nullable();

            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['business_id', 'year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_strategies');
    }
};
