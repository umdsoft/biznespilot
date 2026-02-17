<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Annual Strategies
        Schema::create('annual_strategies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->integer('year');
            $table->string('title');
            $table->text('vision')->nullable();
            $table->json('objectives')->nullable();
            $table->json('key_results')->nullable();
            $table->decimal('annual_revenue_target', 14, 2)->nullable();
            $table->decimal('annual_budget', 14, 2)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'year']);
        });

        // Quarterly Plans
        Schema::create('quarterly_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('annual_strategy_id')->nullable();
            $table->integer('year');
            $table->integer('quarter');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('goals')->nullable();
            $table->json('initiatives')->nullable();
            $table->decimal('budget', 14, 2)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('annual_strategy_id')->references('id')->on('annual_strategies')->onDelete('set null');
            $table->unique(['business_id', 'year', 'quarter']);
        });

        // Monthly Plans
        Schema::create('monthly_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('quarterly_plan_id')->nullable();
            $table->integer('year');
            $table->integer('month');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('tasks')->nullable();
            $table->json('campaigns')->nullable();
            $table->decimal('budget', 14, 2)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('quarterly_plan_id')->references('id')->on('quarterly_plans')->onDelete('set null');
            $table->unique(['business_id', 'year', 'month']);
        });

        // Weekly Plans (konsolidatsiya: ADD migratsiya birlashtirilib)
        Schema::create('weekly_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uuid')->nullable();
            $table->uuid('business_id');
            $table->uuid('monthly_plan_id')->nullable();
            $table->integer('year')->nullable();
            $table->integer('week_number')->nullable();
            $table->integer('month')->nullable();
            $table->integer('week_of_month')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('title')->nullable();
            $table->date('week_start');
            $table->date('week_end');
            $table->json('priorities')->nullable();
            $table->text('notes')->nullable();
            $table->json('goals')->nullable();
            $table->json('tasks')->nullable();
            $table->json('content_schedule')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('weekly_focus')->nullable();
            $table->json('monday')->nullable();
            $table->json('tuesday')->nullable();
            $table->json('wednesday')->nullable();
            $table->json('thursday')->nullable();
            $table->json('friday')->nullable();
            $table->json('saturday')->nullable();
            $table->json('sunday')->nullable();
            $table->integer('total_tasks')->default(0);
            $table->integer('completed_tasks')->default(0);
            $table->json('content_items')->nullable();
            $table->integer('posts_planned')->default(0);
            $table->integer('posts_published')->default(0);
            $table->decimal('revenue_target', 14, 2)->nullable();
            $table->decimal('spend_budget', 14, 2)->nullable();
            $table->integer('lead_target')->nullable();
            $table->integer('engagement_target')->nullable();
            $table->json('marketing_activities')->nullable();
            $table->json('sales_activities')->nullable();
            $table->json('meetings')->nullable();
            $table->json('ai_suggestions')->nullable();
            $table->json('ai_content_ideas')->nullable();
            $table->json('actual_results')->nullable();
            $table->integer('completion_percent')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('monthly_plan_id')->references('id')->on('monthly_plans')->onDelete('set null');
            $table->unique(['business_id', 'week_start']);
            $table->index(['business_id', 'year', 'week_number'], 'weekly_plans_business_year_week_index');
        });

        // Content Calendar
        Schema::create('content_calendar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('weekly_plan_id')->nullable();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('content_type', 50);
            $table->string('platform', 50);
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->string('status', 20)->default('planned');
            $table->json('media')->nullable();
            $table->json('hashtags')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('weekly_plan_id')->references('id')->on('weekly_plans')->onDelete('set null');
            $table->index(['business_id', 'scheduled_date']);
            $table->index('status');
        });

        // Budget Allocations (konsolidatsiya: ADD migratsiya birlashtirilib)
        Schema::create('budget_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uuid')->nullable();
            $table->uuid('business_id');
            $table->string('period_type', 20)->nullable();
            $table->uuid('annual_strategy_id')->nullable();
            $table->uuid('quarterly_plan_id')->nullable();
            $table->uuid('monthly_plan_id')->nullable();
            $table->uuid('weekly_plan_id')->nullable();
            $table->string('category', 50);
            $table->string('subcategory', 50)->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('campaign')->nullable();
            $table->decimal('planned_budget', 14, 2)->default(0);
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->decimal('spent_amount', 14, 2)->default(0);
            $table->decimal('allocation_percent', 5, 2)->nullable();
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->decimal('expected_roi', 8, 2)->nullable();
            $table->decimal('actual_roi', 8, 2)->nullable();
            $table->integer('expected_leads')->default(0);
            $table->integer('actual_leads')->default(0);
            $table->decimal('expected_revenue', 14, 2)->default(0);
            $table->decimal('actual_revenue', 14, 2)->default(0);
            $table->decimal('cost_per_lead', 14, 2)->nullable();
            $table->decimal('cost_per_acquisition', 14, 2)->nullable();
            $table->string('period', 20);
            $table->integer('year');
            $table->integer('period_number');
            $table->integer('quarter')->nullable();
            $table->integer('month')->nullable();
            $table->integer('week')->nullable();
            $table->string('status', 20)->default('planned');
            $table->boolean('overspend_alert')->default(false);
            $table->decimal('overspend_threshold_percent', 5, 2)->default(100);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('history')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'period', 'year']);
            $table->index('period_type');
            $table->index(['business_id', 'period_type', 'year'], 'budget_allocations_business_period_year_index');
            $table->index(['business_id', 'period_type', 'year', 'month'], 'budget_allocations_business_period_year_month_index');
        });

        // Strategy Templates
        Schema::create('strategy_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('industry_id')->nullable();
            $table->string('name');
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->json('template_data');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('industry_id')->references('id')->on('industries')->onDelete('set null');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_templates');
        Schema::dropIfExists('budget_allocations');
        Schema::dropIfExists('content_calendar');
        Schema::dropIfExists('weekly_plans');
        Schema::dropIfExists('monthly_plans');
        Schema::dropIfExists('quarterly_plans');
        Schema::dropIfExists('annual_strategies');
    }
};
