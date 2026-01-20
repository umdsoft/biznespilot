<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            // Add missing columns that KpiTarget Model and KPITargetService expect
            if (!Schema::hasColumn('kpi_targets', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (!Schema::hasColumn('kpi_targets', 'period_type')) {
                $table->string('period_type', 20)->nullable()->after('uuid');
            }
            if (!Schema::hasColumn('kpi_targets', 'annual_strategy_id')) {
                $table->uuid('annual_strategy_id')->nullable()->after('business_id');
            }
            if (!Schema::hasColumn('kpi_targets', 'quarterly_plan_id')) {
                $table->uuid('quarterly_plan_id')->nullable()->after('annual_strategy_id');
            }
            if (!Schema::hasColumn('kpi_targets', 'monthly_plan_id')) {
                $table->uuid('monthly_plan_id')->nullable()->after('quarterly_plan_id');
            }
            if (!Schema::hasColumn('kpi_targets', 'weekly_plan_id')) {
                $table->uuid('weekly_plan_id')->nullable()->after('monthly_plan_id');
            }
            if (!Schema::hasColumn('kpi_targets', 'quarter')) {
                $table->integer('quarter')->nullable()->after('year');
            }
            if (!Schema::hasColumn('kpi_targets', 'month')) {
                $table->integer('month')->nullable()->after('quarter');
            }
            if (!Schema::hasColumn('kpi_targets', 'week')) {
                $table->integer('week')->nullable()->after('month');
            }
            if (!Schema::hasColumn('kpi_targets', 'kpi_name')) {
                $table->string('kpi_name')->nullable()->after('week');
            }
            if (!Schema::hasColumn('kpi_targets', 'kpi_key')) {
                $table->string('kpi_key', 50)->nullable()->after('kpi_name');
            }
            if (!Schema::hasColumn('kpi_targets', 'category')) {
                $table->string('category', 50)->nullable()->after('kpi_key');
            }
            if (!Schema::hasColumn('kpi_targets', 'minimum_value')) {
                $table->decimal('minimum_value', 14, 4)->nullable()->after('target_value');
            }
            if (!Schema::hasColumn('kpi_targets', 'stretch_value')) {
                $table->decimal('stretch_value', 14, 4)->nullable()->after('minimum_value');
            }
            if (!Schema::hasColumn('kpi_targets', 'unit')) {
                $table->string('unit', 20)->nullable()->after('stretch_value');
            }
            if (!Schema::hasColumn('kpi_targets', 'current_value')) {
                $table->decimal('current_value', 14, 4)->nullable()->after('unit');
            }
            if (!Schema::hasColumn('kpi_targets', 'previous_value')) {
                $table->decimal('previous_value', 14, 4)->nullable()->after('current_value');
            }
            if (!Schema::hasColumn('kpi_targets', 'last_updated_at')) {
                $table->timestamp('last_updated_at')->nullable()->after('previous_value');
            }
            if (!Schema::hasColumn('kpi_targets', 'progress_percent')) {
                $table->decimal('progress_percent', 6, 2)->default(0)->after('last_updated_at');
            }
            if (!Schema::hasColumn('kpi_targets', 'status')) {
                $table->string('status', 20)->default('not_started')->after('progress_percent');
            }
            if (!Schema::hasColumn('kpi_targets', 'trend')) {
                $table->string('trend', 20)->nullable()->after('status');
            }
            if (!Schema::hasColumn('kpi_targets', 'change_percent')) {
                $table->decimal('change_percent', 8, 2)->nullable()->after('trend');
            }
            if (!Schema::hasColumn('kpi_targets', 'enable_alerts')) {
                $table->boolean('enable_alerts')->default(false)->after('change_percent');
            }
            if (!Schema::hasColumn('kpi_targets', 'alert_threshold_percent')) {
                $table->decimal('alert_threshold_percent', 5, 2)->default(80)->after('enable_alerts');
            }
            if (!Schema::hasColumn('kpi_targets', 'alert_triggered')) {
                $table->boolean('alert_triggered')->default(false)->after('alert_threshold_percent');
            }
            if (!Schema::hasColumn('kpi_targets', 'last_alert_at')) {
                $table->timestamp('last_alert_at')->nullable()->after('alert_triggered');
            }
            if (!Schema::hasColumn('kpi_targets', 'data_source')) {
                $table->string('data_source', 50)->nullable()->after('last_alert_at');
            }
            if (!Schema::hasColumn('kpi_targets', 'calculation_method')) {
                $table->string('calculation_method', 50)->nullable()->after('data_source');
            }
            if (!Schema::hasColumn('kpi_targets', 'calculation_formula')) {
                $table->json('calculation_formula')->nullable()->after('calculation_method');
            }
            if (!Schema::hasColumn('kpi_targets', 'description')) {
                $table->text('description')->nullable()->after('calculation_formula');
            }
            if (!Schema::hasColumn('kpi_targets', 'notes')) {
                $table->text('notes')->nullable()->after('description');
            }
            if (!Schema::hasColumn('kpi_targets', 'priority')) {
                $table->integer('priority')->default(0)->after('notes');
            }

            // Add indexes
            $table->index('period_type');
            $table->index(['business_id', 'period_type', 'year'], 'kpi_targets_business_period_year_index');
            $table->index(['business_id', 'period_type', 'year', 'month'], 'kpi_targets_business_period_year_month_index');
        });

        // Migrate existing data: set period_type based on period column
        DB::table('kpi_targets')->whereNull('period_type')->update([
            'period_type' => DB::raw("`period`"),
            'month' => DB::raw("CASE WHEN `period` = 'monthly' THEN `period_number` ELSE NULL END"),
            'quarter' => DB::raw("CASE WHEN `period` = 'quarterly' THEN `period_number` ELSE NULL END"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropIndex('kpi_targets_business_period_year_month_index');
            $table->dropIndex('kpi_targets_business_period_year_index');
            $table->dropIndex(['period_type']);

            $columns = [
                'uuid', 'period_type', 'annual_strategy_id', 'quarterly_plan_id',
                'monthly_plan_id', 'weekly_plan_id', 'quarter', 'month', 'week',
                'kpi_name', 'kpi_key', 'category', 'minimum_value', 'stretch_value',
                'unit', 'current_value', 'previous_value', 'last_updated_at',
                'progress_percent', 'status', 'trend', 'change_percent',
                'enable_alerts', 'alert_threshold_percent', 'alert_triggered',
                'last_alert_at', 'data_source', 'calculation_method',
                'calculation_formula', 'description', 'notes', 'priority'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('kpi_targets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
