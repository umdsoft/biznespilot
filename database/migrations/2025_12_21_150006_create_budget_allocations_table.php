<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');

            // Link to strategy level
            $table->enum('period_type', ['annual', 'quarterly', 'monthly', 'weekly']);
            $table->foreignId('annual_strategy_id')->nullable()->constrained('annual_strategies')->nullOnDelete();
            $table->foreignId('quarterly_plan_id')->nullable()->constrained('quarterly_plans')->nullOnDelete();
            $table->foreignId('monthly_plan_id')->nullable()->constrained('monthly_plans')->nullOnDelete();
            $table->foreignId('weekly_plan_id')->nullable()->constrained('weekly_plans')->nullOnDelete();

            // Period
            $table->year('year');
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedTinyInteger('week')->nullable();

            // Budget category
            $table->enum('category', [
                'marketing', 'advertising', 'content', 'tools',
                'team', 'events', 'pr', 'other'
            ]);
            $table->string('subcategory')->nullable();

            // Channel (if applicable)
            $table->string('channel')->nullable(); // instagram, telegram, google, etc.
            $table->string('campaign')->nullable();

            // Budget amounts
            $table->decimal('planned_budget', 15, 2);
            $table->decimal('allocated_budget', 15, 2)->nullable();
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->nullable();

            // Percentage allocation
            $table->decimal('allocation_percent', 5, 2)->nullable();

            // Performance
            $table->decimal('expected_roi', 8, 2)->nullable();
            $table->decimal('actual_roi', 8, 2)->nullable();
            $table->integer('expected_leads')->nullable();
            $table->integer('actual_leads')->default(0);
            $table->decimal('expected_revenue', 15, 2)->nullable();
            $table->decimal('actual_revenue', 15, 2)->default(0);
            $table->decimal('cost_per_lead', 10, 2)->nullable();
            $table->decimal('cost_per_acquisition', 10, 2)->nullable();

            // Status
            $table->enum('status', [
                'planned', 'approved', 'active', 'paused', 'completed', 'cancelled'
            ])->default('planned');

            // Alerts
            $table->boolean('overspend_alert')->default(false);
            $table->decimal('overspend_threshold_percent', 5, 2)->default(100);

            // Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('history')->nullable(); // Track changes

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'period_type', 'year']);
            $table->index(['business_id', 'category']);
            $table->index(['business_id', 'channel']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_allocations');
    }
};
