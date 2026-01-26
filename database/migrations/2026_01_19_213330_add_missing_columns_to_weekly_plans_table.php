<?php

use Carbon\Carbon;
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
        Schema::table('weekly_plans', function (Blueprint $table) {
            // Add missing columns that Model expects
            if (!Schema::hasColumn('weekly_plans', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (!Schema::hasColumn('weekly_plans', 'year')) {
                $table->integer('year')->nullable()->after('monthly_plan_id');
            }
            if (!Schema::hasColumn('weekly_plans', 'week_number')) {
                $table->integer('week_number')->nullable()->after('year');
            }
            if (!Schema::hasColumn('weekly_plans', 'month')) {
                $table->integer('month')->nullable()->after('week_number');
            }
            if (!Schema::hasColumn('weekly_plans', 'week_of_month')) {
                $table->integer('week_of_month')->nullable()->after('month');
            }
            if (!Schema::hasColumn('weekly_plans', 'start_date')) {
                $table->date('start_date')->nullable()->after('week_of_month');
            }
            if (!Schema::hasColumn('weekly_plans', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('weekly_plans', 'title')) {
                $table->string('title')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('weekly_plans', 'weekly_focus')) {
                $table->string('weekly_focus')->nullable()->after('status');
            }
            if (!Schema::hasColumn('weekly_plans', 'notes')) {
                $table->text('notes')->nullable()->after('priorities');
            }
            if (!Schema::hasColumn('weekly_plans', 'goals')) {
                $table->json('goals')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('weekly_plans', 'monday')) {
                $table->json('monday')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'tuesday')) {
                $table->json('tuesday')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'wednesday')) {
                $table->json('wednesday')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'thursday')) {
                $table->json('thursday')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'friday')) {
                $table->json('friday')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'saturday')) {
                $table->json('saturday')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'sunday')) {
                $table->json('sunday')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'total_tasks')) {
                $table->integer('total_tasks')->default(0);
            }
            if (!Schema::hasColumn('weekly_plans', 'completed_tasks')) {
                $table->integer('completed_tasks')->default(0);
            }
            if (!Schema::hasColumn('weekly_plans', 'content_items')) {
                $table->json('content_items')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'posts_planned')) {
                $table->integer('posts_planned')->default(0);
            }
            if (!Schema::hasColumn('weekly_plans', 'posts_published')) {
                $table->integer('posts_published')->default(0);
            }
            if (!Schema::hasColumn('weekly_plans', 'revenue_target')) {
                $table->decimal('revenue_target', 14, 2)->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'spend_budget')) {
                $table->decimal('spend_budget', 14, 2)->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'lead_target')) {
                $table->integer('lead_target')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'engagement_target')) {
                $table->integer('engagement_target')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'marketing_activities')) {
                $table->json('marketing_activities')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'sales_activities')) {
                $table->json('sales_activities')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'meetings')) {
                $table->json('meetings')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'ai_suggestions')) {
                $table->json('ai_suggestions')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'ai_content_ideas')) {
                $table->json('ai_content_ideas')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'actual_results')) {
                $table->json('actual_results')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'completion_percent')) {
                $table->integer('completion_percent')->default(0);
            }
            if (!Schema::hasColumn('weekly_plans', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('weekly_plans', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }

            // Add index for year and week_number for faster queries
            $table->index(['business_id', 'year', 'week_number'], 'weekly_plans_business_year_week_index');
        });

        // Migrate existing data: populate year and week_number from week_start
        // Using PHP Carbon instead of raw SQL for SQLite/MySQL compatibility
        $plans = DB::table('weekly_plans')
            ->whereNull('year')
            ->whereNotNull('week_start')
            ->get(['id', 'week_start']);

        foreach ($plans as $plan) {
            if ($plan->week_start) {
                $date = Carbon::parse($plan->week_start);
                DB::table('weekly_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'year' => $date->year,
                        'week_number' => $date->weekOfYear,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_plans', function (Blueprint $table) {
            $table->dropIndex('weekly_plans_business_year_week_index');

            $columns = [
                'uuid', 'year', 'week_number', 'month', 'week_of_month',
                'weekly_focus', 'notes', 'goals', 'monday', 'tuesday',
                'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
                'total_tasks', 'completed_tasks', 'content_items',
                'posts_planned', 'posts_published', 'revenue_target',
                'spend_budget', 'lead_target', 'engagement_target',
                'marketing_activities', 'sales_activities', 'meetings',
                'ai_suggestions', 'ai_content_ideas', 'actual_results',
                'completion_percent', 'approved_at', 'completed_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('weekly_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
