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

        // Weekly Plans
        Schema::create('weekly_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('monthly_plan_id')->nullable();
            $table->date('week_start');
            $table->date('week_end');
            $table->json('priorities')->nullable();
            $table->json('tasks')->nullable();
            $table->json('content_schedule')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('monthly_plan_id')->references('id')->on('monthly_plans')->onDelete('set null');
            $table->unique(['business_id', 'week_start']);
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

        // Budget Allocations
        Schema::create('budget_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('annual_strategy_id')->nullable();
            $table->uuid('quarterly_plan_id')->nullable();
            $table->uuid('monthly_plan_id')->nullable();
            $table->string('category', 50);
            $table->string('subcategory', 50)->nullable();
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->decimal('spent_amount', 14, 2)->default(0);
            $table->string('period', 20);
            $table->integer('year');
            $table->integer('period_number');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'period', 'year']);
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
