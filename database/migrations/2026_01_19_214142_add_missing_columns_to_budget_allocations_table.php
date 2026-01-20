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
        Schema::table('budget_allocations', function (Blueprint $table) {
            // Add missing columns that BudgetAllocation Model expects
            if (!Schema::hasColumn('budget_allocations', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (!Schema::hasColumn('budget_allocations', 'period_type')) {
                $table->string('period_type', 20)->nullable()->after('uuid');
            }
            if (!Schema::hasColumn('budget_allocations', 'weekly_plan_id')) {
                $table->uuid('weekly_plan_id')->nullable()->after('monthly_plan_id');
            }
            if (!Schema::hasColumn('budget_allocations', 'quarter')) {
                $table->integer('quarter')->nullable()->after('year');
            }
            if (!Schema::hasColumn('budget_allocations', 'month')) {
                $table->integer('month')->nullable()->after('quarter');
            }
            if (!Schema::hasColumn('budget_allocations', 'week')) {
                $table->integer('week')->nullable()->after('month');
            }
            if (!Schema::hasColumn('budget_allocations', 'channel')) {
                $table->string('channel', 50)->nullable()->after('subcategory');
            }
            if (!Schema::hasColumn('budget_allocations', 'campaign')) {
                $table->string('campaign')->nullable()->after('channel');
            }
            if (!Schema::hasColumn('budget_allocations', 'planned_budget')) {
                $table->decimal('planned_budget', 14, 2)->default(0)->after('campaign');
            }
            if (!Schema::hasColumn('budget_allocations', 'allocation_percent')) {
                $table->decimal('allocation_percent', 5, 2)->nullable()->after('spent_amount');
            }
            if (!Schema::hasColumn('budget_allocations', 'remaining_amount')) {
                $table->decimal('remaining_amount', 14, 2)->default(0)->after('allocation_percent');
            }
            if (!Schema::hasColumn('budget_allocations', 'expected_roi')) {
                $table->decimal('expected_roi', 8, 2)->nullable()->after('remaining_amount');
            }
            if (!Schema::hasColumn('budget_allocations', 'actual_roi')) {
                $table->decimal('actual_roi', 8, 2)->nullable()->after('expected_roi');
            }
            if (!Schema::hasColumn('budget_allocations', 'expected_leads')) {
                $table->integer('expected_leads')->default(0)->after('actual_roi');
            }
            if (!Schema::hasColumn('budget_allocations', 'actual_leads')) {
                $table->integer('actual_leads')->default(0)->after('expected_leads');
            }
            if (!Schema::hasColumn('budget_allocations', 'expected_revenue')) {
                $table->decimal('expected_revenue', 14, 2)->default(0)->after('actual_leads');
            }
            if (!Schema::hasColumn('budget_allocations', 'actual_revenue')) {
                $table->decimal('actual_revenue', 14, 2)->default(0)->after('expected_revenue');
            }
            if (!Schema::hasColumn('budget_allocations', 'cost_per_lead')) {
                $table->decimal('cost_per_lead', 14, 2)->nullable()->after('actual_revenue');
            }
            if (!Schema::hasColumn('budget_allocations', 'cost_per_acquisition')) {
                $table->decimal('cost_per_acquisition', 14, 2)->nullable()->after('cost_per_lead');
            }
            if (!Schema::hasColumn('budget_allocations', 'status')) {
                $table->string('status', 20)->default('planned')->after('cost_per_acquisition');
            }
            if (!Schema::hasColumn('budget_allocations', 'overspend_alert')) {
                $table->boolean('overspend_alert')->default(false)->after('status');
            }
            if (!Schema::hasColumn('budget_allocations', 'overspend_threshold_percent')) {
                $table->decimal('overspend_threshold_percent', 5, 2)->default(100)->after('overspend_alert');
            }
            if (!Schema::hasColumn('budget_allocations', 'description')) {
                $table->text('description')->nullable()->after('overspend_threshold_percent');
            }
            if (!Schema::hasColumn('budget_allocations', 'notes')) {
                $table->text('notes')->nullable()->after('description');
            }
            if (!Schema::hasColumn('budget_allocations', 'history')) {
                $table->json('history')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('budget_allocations', 'approved_by')) {
                $table->uuid('approved_by')->nullable()->after('history');
            }
            if (!Schema::hasColumn('budget_allocations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            // Add indexes
            $table->index('period_type');
            $table->index(['business_id', 'period_type', 'year'], 'budget_allocations_business_period_year_index');
            $table->index(['business_id', 'period_type', 'year', 'month'], 'budget_allocations_business_period_year_month_index');
        });

        // Migrate existing data: set period_type based on period column
        DB::table('budget_allocations')->whereNull('period_type')->update([
            'period_type' => DB::raw("`period`"),
            'month' => DB::raw("CASE WHEN `period` = 'monthly' THEN `period_number` ELSE NULL END"),
            'quarter' => DB::raw("CASE WHEN `period` = 'quarterly' THEN `period_number` ELSE NULL END"),
            'planned_budget' => DB::raw("`allocated_amount`"),
            'remaining_amount' => DB::raw("`allocated_amount` - `spent_amount`"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->dropIndex('budget_allocations_business_period_year_month_index');
            $table->dropIndex('budget_allocations_business_period_year_index');
            $table->dropIndex(['period_type']);

            $columns = [
                'uuid', 'period_type', 'weekly_plan_id', 'quarter', 'month', 'week',
                'channel', 'campaign', 'planned_budget', 'allocation_percent',
                'remaining_amount', 'expected_roi', 'actual_roi', 'expected_leads',
                'actual_leads', 'expected_revenue', 'actual_revenue', 'cost_per_lead',
                'cost_per_acquisition', 'status', 'overspend_alert',
                'overspend_threshold_percent', 'description', 'notes', 'history',
                'approved_by', 'approved_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('budget_allocations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
