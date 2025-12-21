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
        Schema::create('ai_monthly_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');

            // Strategy period
            $table->integer('year'); // e.g., 2025
            $table->integer('month'); // 1-12
            $table->string('period_label'); // e.g., "January 2025"

            // Strategy content
            $table->string('title');
            $table->text('executive_summary');
            $table->json('goals')->nullable(); // Monthly goals and targets
            $table->json('action_plan')->nullable(); // Week-by-week action items
            $table->json('focus_areas')->nullable(); // Key areas to focus on

            // Marketing strategy
            $table->json('content_strategy')->nullable(); // Content recommendations
            $table->json('advertising_strategy')->nullable(); // Ad spend and targeting
            $table->json('channel_strategy')->nullable(); // Which channels to focus on

            // Sales strategy
            $table->json('sales_targets')->nullable(); // Sales goals and metrics
            $table->json('pricing_recommendations')->nullable(); // Pricing adjustments
            $table->json('offer_recommendations')->nullable(); // Offer suggestions

            // Budget allocation
            $table->decimal('recommended_budget', 15, 2)->nullable();
            $table->json('budget_breakdown')->nullable(); // By channel/campaign

            // Predictions
            $table->json('predicted_metrics')->nullable(); // Revenue, leads, etc.
            $table->decimal('confidence_score', 5, 2)->nullable(); // 0-100

            // Status tracking
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');
            $table->timestamp('generated_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Results
            $table->json('actual_results')->nullable(); // Actual metrics achieved
            $table->decimal('success_rate', 5, 2)->nullable(); // % of goals achieved

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['business_id', 'year', 'month']);
            $table->index(['business_id', 'status']);
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_monthly_strategies');
    }
};
