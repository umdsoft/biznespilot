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
        Schema::create('kpi_weekly_summaries', function (Blueprint $table) {
            $table->id();

            // Business & KPI Reference (UUID for business_id)
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('kpi_code', 100)->index();
            $table->foreign('kpi_code')->references('kpi_code')->on('kpi_templates')
                ->onDelete('cascade')->onUpdate('cascade');

            // Week Identification
            $table->date('week_start_date')->index()->comment('Monday of the week');
            $table->date('week_end_date')->index()->comment('Sunday of the week');
            $table->integer('week_number')->comment('Week number in the year (1-53)');
            $table->integer('year')->index();
            $table->string('week_label', 50)->comment('Human readable: "Week 1, 2025" or "06-12 Jan 2025"');

            // Aggregated Values
            $table->decimal('planned_value', 15, 2)->comment('Weekly target');
            $table->decimal('actual_value', 15, 2)->comment('Sum or average of daily actuals (depends on KPI)');
            $table->string('unit', 50);
            $table->enum('aggregation_method', ['sum', 'average', 'min', 'max', 'last'])->default('sum')
                ->comment('How daily values are aggregated to weekly');

            // Performance Calculation
            $table->decimal('achievement_percentage', 7, 2)->comment('(actual / planned) * 100');
            $table->decimal('variance', 15, 2);
            $table->decimal('variance_percentage', 7, 2);

            // Status
            $table->enum('status', ['green', 'yellow', 'red', 'grey'])->default('grey')->index();
            $table->boolean('target_met')->default(false)->comment('Did we meet or exceed the target');

            // Daily Breakdown (JSON)
            $table->json('daily_breakdown')->comment('{
                "2025-01-06": {"plan": 5, "actual": 4, "achievement": 80, "status": "red"},
                "2025-01-07": {"plan": 6, "actual": 7, "achievement": 117, "status": "green"},
                ...
            }');

            // Statistical Analysis
            $table->decimal('daily_average', 15, 2)->nullable();
            $table->decimal('daily_median', 15, 2)->nullable();
            $table->decimal('daily_min', 15, 2)->nullable();
            $table->decimal('daily_max', 15, 2)->nullable();
            $table->decimal('daily_std_deviation', 15, 2)->nullable()
                ->comment('Standard deviation - consistency measure');

            // Trend Analysis
            $table->enum('trend', ['improving', 'stable', 'declining', 'volatile', 'unknown'])->default('unknown');
            $table->decimal('trend_percentage', 7, 2)->nullable()
                ->comment('% change from previous week');
            $table->text('trend_notes')->nullable();

            // Best & Worst Days
            $table->date('best_day_date')->nullable();
            $table->decimal('best_day_value', 15, 2)->nullable();
            $table->date('worst_day_date')->nullable();
            $table->decimal('worst_day_value', 15, 2)->nullable();

            // Days Performance Distribution
            $table->integer('green_days_count')->default(0);
            $table->integer('yellow_days_count')->default(0);
            $table->integer('red_days_count')->default(0);
            $table->integer('no_data_days_count')->default(0);
            $table->decimal('days_on_target_percentage', 5, 2)->nullable()
                ->comment('(green_days / total_days) * 100');

            // Week Completion
            $table->integer('total_days')->default(7);
            $table->integer('completed_days')->default(0)
                ->comment('Days with actual data');
            $table->boolean('is_week_complete')->default(false)
                ->comment('All 7 days have data');
            $table->decimal('completion_percentage', 5, 2)->default(0)
                ->comment('(completed_days / total_days) * 100');

            // Financial Impact
            $table->decimal('total_financial_impact', 15, 2)->nullable();
            $table->decimal('average_daily_impact', 15, 2)->nullable();

            // Comparison with Previous Week
            $table->foreignId('previous_week_id')->nullable()->constrained('kpi_weekly_summaries')
                ->onDelete('set null');
            $table->decimal('vs_previous_week_change', 7, 2)->nullable()
                ->comment('% change from previous week');
            $table->enum('vs_previous_week_status', ['better', 'same', 'worse', 'unknown'])->default('unknown');

            // Related Monthly Summary (No foreign key to avoid circular dependency)
            $table->unsignedBigInteger('monthly_summary_id')->nullable()->index();

            // Insights & Recommendations
            $table->text('performance_summary')->nullable()
                ->comment('Auto-generated summary of the week');
            $table->json('insights')->nullable()->comment('AI-generated insights array');
            $table->json('recommendations')->nullable()->comment('Actionable recommendations');

            // Flags
            $table->boolean('has_anomalies')->default(false);
            $table->integer('anomaly_days_count')->default(0);
            $table->boolean('is_partial_week')->default(false)
                ->comment('Week not fully elapsed (e.g., current week)');

            // Data Quality
            $table->decimal('data_quality_score', 5, 2)->nullable()
                ->comment('0-100 score based on completeness and verification');
            $table->integer('verified_days_count')->default(0);

            // Audit
            $table->timestamp('calculated_at')->nullable()->comment('When this summary was calculated');
            $table->foreignId('calculated_by_job_id')->nullable()->comment('Background job that created this');
            $table->timestamps();
            $table->softDeletes();

            // Unique Constraint
            $table->unique(['business_id', 'kpi_code', 'week_start_date'], 'unique_weekly_kpi');

            // Performance Indexes
            $table->index(['business_id', 'week_start_date']);
            $table->index(['kpi_code', 'week_start_date']);
            $table->index(['year', 'week_number']);
            $table->index(['status', 'is_week_complete']);
            $table->index(['trend', 'achievement_percentage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_weekly_summaries');
    }
};
