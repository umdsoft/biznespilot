<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_targets', function (Blueprint $table) {
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

            // KPI details
            $table->string('kpi_name');
            $table->string('kpi_key'); // Unique identifier like revenue, leads, ctr
            $table->enum('category', [
                'revenue', 'marketing', 'sales', 'content', 'customer', 'operational',
            ]);

            // Target values
            $table->decimal('target_value', 15, 4);
            $table->decimal('minimum_value', 15, 4)->nullable(); // Alert if below this
            $table->decimal('stretch_value', 15, 4)->nullable(); // Bonus target
            $table->string('unit')->nullable(); // %, sum, count, etc.

            // Current/actual values (updated regularly)
            $table->decimal('current_value', 15, 4)->nullable();
            $table->decimal('previous_value', 15, 4)->nullable();
            $table->timestamp('last_updated_at')->nullable();

            // Progress tracking
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->enum('status', [
                'not_started', 'on_track', 'at_risk', 'behind', 'achieved', 'exceeded',
            ])->default('not_started');

            // Trend
            $table->enum('trend', ['up', 'down', 'stable', 'unknown'])->default('unknown');
            $table->decimal('change_percent', 8, 2)->nullable();

            // Alert thresholds
            $table->boolean('enable_alerts')->default(true);
            $table->decimal('alert_threshold_percent', 5, 2)->default(80); // Alert if below 80% of target
            $table->boolean('alert_triggered')->default(false);
            $table->timestamp('last_alert_at')->nullable();

            // Source and calculation
            $table->string('data_source')->nullable(); // Which integration/table
            $table->string('calculation_method')->nullable(); // sum, average, count, etc.
            $table->json('calculation_formula')->nullable();

            // Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->integer('priority')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'period_type', 'year']);
            $table->index(['business_id', 'kpi_key']);
            $table->index(['business_id', 'category']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
    }
};
