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
        Schema::create('kpi_monthly_summaries', function (Blueprint $table) {
            $table->id();

            // Business & KPI Reference (UUID for business_id)
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('kpi_code', 100)->index();
            $table->foreign('kpi_code')->references('kpi_code')->on('kpi_templates')
                ->onDelete('cascade')->onUpdate('cascade');

            // Month Identification
            $table->date('month')->index()->comment('First day of the month: YYYY-MM-01');
            $table->integer('year')->index();
            $table->integer('month_number')->comment('1-12');
            $table->string('month_name', 20)->comment('January, February, etc.');
            $table->string('month_name_uz', 20)->comment('Yanvar, Fevral, etc.');

            // Aggregated Values
            $table->decimal('planned_value', 15, 2)->comment('Monthly target');
            $table->decimal('actual_value', 15, 2)->comment('Sum or average of weekly/daily actuals');
            $table->string('unit', 50);
            $table->enum('aggregation_method', ['sum', 'average', 'min', 'max', 'last'])->default('sum');

            // Performance Calculation
            $table->decimal('achievement_percentage', 7, 2);
            $table->decimal('variance', 15, 2);
            $table->decimal('variance_percentage', 7, 2);

            // Status
            $table->enum('status', ['green', 'yellow', 'red', 'grey'])->default('grey')->index();
            $table->boolean('target_met')->default(false);

            // Weekly Breakdown (JSON)
            $table->json('weekly_breakdown')->comment('{
                "week_1": {"start": "2025-01-06", "plan": 35, "actual": 32, "achievement": 91, "status": "yellow"},
                "week_2": {"start": "2025-01-13", "plan": 36, "actual": 38, "achievement": 106, "status": "green"},
                ...
            }');

            // Statistical Analysis
            $table->decimal('weekly_average', 15, 2)->nullable();
            $table->decimal('weekly_median', 15, 2)->nullable();
            $table->decimal('weekly_min', 15, 2)->nullable();
            $table->decimal('weekly_max', 15, 2)->nullable();
            $table->decimal('weekly_std_deviation', 15, 2)->nullable();

            $table->decimal('daily_average', 15, 2)->nullable();
            $table->decimal('daily_min', 15, 2)->nullable();
            $table->decimal('daily_max', 15, 2)->nullable();

            // Trend Analysis
            $table->enum('trend', ['improving', 'stable', 'declining', 'volatile', 'unknown'])->default('unknown');
            $table->decimal('trend_percentage', 7, 2)->nullable()
                ->comment('% change from previous month');
            $table->text('trend_notes')->nullable();
            $table->json('weekly_trend_pattern')->nullable()
                ->comment('Week-over-week trend within the month');

            // Best & Worst Performance
            $table->date('best_week_start')->nullable();
            $table->decimal('best_week_value', 15, 2)->nullable();
            $table->date('worst_week_start')->nullable();
            $table->decimal('worst_week_value', 15, 2)->nullable();

            $table->date('best_day_date')->nullable();
            $table->decimal('best_day_value', 15, 2)->nullable();
            $table->date('worst_day_date')->nullable();
            $table->decimal('worst_day_value', 15, 2)->nullable();

            // Week Distribution
            $table->integer('total_weeks')->default(0)->comment('Usually 4-5 weeks');
            $table->integer('completed_weeks')->default(0);
            $table->integer('green_weeks_count')->default(0);
            $table->integer('yellow_weeks_count')->default(0);
            $table->integer('red_weeks_count')->default(0);
            $table->decimal('weeks_on_target_percentage', 5, 2)->nullable();

            // Day Distribution
            $table->integer('total_days')->default(0)->comment('28-31 days depending on month');
            $table->integer('completed_days')->default(0);
            $table->integer('green_days_count')->default(0);
            $table->integer('yellow_days_count')->default(0);
            $table->integer('red_days_count')->default(0);
            $table->decimal('days_on_target_percentage', 5, 2)->nullable();

            // Month Completion
            $table->boolean('is_month_complete')->default(false);
            $table->decimal('completion_percentage', 5, 2)->default(0);

            // Financial Impact
            $table->decimal('total_financial_impact', 15, 2)->nullable();
            $table->decimal('average_weekly_impact', 15, 2)->nullable();
            $table->decimal('average_daily_impact', 15, 2)->nullable();

            // Comparison with Previous Month
            $table->foreignId('previous_month_id')->nullable()->constrained('kpi_monthly_summaries')
                ->onDelete('set null');
            $table->decimal('vs_previous_month_change', 7, 2)->nullable();
            $table->enum('vs_previous_month_status', ['better', 'same', 'worse', 'unknown'])->default('unknown');

            // Year-over-Year Comparison (if data exists)
            $table->foreignId('same_month_last_year_id')->nullable()->constrained('kpi_monthly_summaries')
                ->onDelete('set null');
            $table->decimal('vs_last_year_change', 7, 2)->nullable();
            $table->enum('vs_last_year_status', ['better', 'same', 'worse', 'unknown'])->default('unknown');

            // Quarterly Context
            $table->integer('quarter')->comment('1-4');
            $table->decimal('quarter_progress_percentage', 5, 2)->nullable()
                ->comment('Progress toward quarterly target');

            // Seasonality
            $table->decimal('seasonality_index', 5, 2)->nullable()
                ->comment('1.0 = average, >1.0 = above average season, <1.0 = below');
            $table->boolean('is_peak_season')->default(false);
            $table->boolean('is_low_season')->default(false);

            // Insights & Analysis
            $table->text('performance_summary')->nullable();
            $table->json('insights')->nullable()->comment('AI-generated insights');
            $table->json('recommendations')->nullable();
            $table->json('key_learnings')->nullable()
                ->comment('What worked and what didnt this month');

            // Achievements & Failures
            $table->json('top_achievements')->nullable()
                ->comment('Top 3-5 wins of the month');
            $table->json('top_failures')->nullable()
                ->comment('Top 3-5 gaps/failures with root causes');

            // Goals for Next Month
            $table->json('next_month_focus_areas')->nullable();
            $table->decimal('recommended_next_month_target', 15, 2)->nullable()
                ->comment('AI-recommended target for next month');

            // Flags
            $table->boolean('has_anomalies')->default(false);
            $table->integer('anomaly_days_count')->default(0);
            $table->boolean('is_partial_month')->default(false)
                ->comment('Month not fully elapsed (e.g., current month)');

            // Data Quality
            $table->decimal('data_quality_score', 5, 2)->nullable();
            $table->integer('verified_days_count')->default(0);
            $table->decimal('verified_data_percentage', 5, 2)->nullable();

            // Reporting
            $table->boolean('pdf_report_generated')->default(false);
            $table->string('pdf_report_path')->nullable();
            $table->timestamp('report_generated_at')->nullable();

            // Audit
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('calculated_by_job_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique Constraint
            $table->unique(['business_id', 'kpi_code', 'month'], 'unique_monthly_kpi');

            // Performance Indexes
            $table->index(['business_id', 'month']);
            $table->index(['kpi_code', 'month']);
            $table->index(['year', 'month_number']);
            $table->index(['status', 'is_month_complete']);
            $table->index(['trend', 'achievement_percentage']);
            $table->index(['quarter', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_monthly_summaries');
    }
};
