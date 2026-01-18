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
        // Skip for SQLite (used in tests) - these are performance optimizations
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Helper function to check if index exists (MySQL only)
        $indexExists = function ($table, $indexName) {
            $result = Schema::getConnection()->select(
                "SHOW INDEX FROM {$table} WHERE Key_name = ?",
                [$indexName]
            );

            return count($result) > 0;
        };

        // Optimize kpi_daily_actuals table
        if (! $indexExists('kpi_daily_actuals', 'idx_source_date')) {
            Schema::table('kpi_daily_actuals', fn ($t) => $t->index(['data_source', 'date'], 'idx_source_date'));
        }
        if (! $indexExists('kpi_daily_actuals', 'idx_status_business_date')) {
            Schema::table('kpi_daily_actuals', fn ($t) => $t->index(['status', 'business_id', 'date'], 'idx_status_business_date'));
        }
        if (! $indexExists('kpi_daily_actuals', 'idx_verified_date')) {
            Schema::table('kpi_daily_actuals', fn ($t) => $t->index(['is_verified', 'date'], 'idx_verified_date'));
        }
        if (! $indexExists('kpi_daily_actuals', 'idx_anomaly_business_date')) {
            Schema::table('kpi_daily_actuals', fn ($t) => $t->index(['is_anomaly', 'business_id', 'date'], 'idx_anomaly_business_date'));
        }

        // Optimize business_kpi_configurations
        if (! $indexExists('business_kpi_configurations', 'idx_status_business')) {
            Schema::table('business_kpi_configurations', fn ($t) => $t->index(['status', 'business_id'], 'idx_status_business'));
        }

        // Optimize kpi_weekly_summaries
        if (! $indexExists('kpi_weekly_summaries', 'idx_business_kpi_year')) {
            Schema::table('kpi_weekly_summaries', fn ($t) => $t->index(['business_id', 'kpi_code', 'year'], 'idx_business_kpi_year'));
        }

        // Optimize kpi_monthly_summaries
        if (! $indexExists('kpi_monthly_summaries', 'idx_business_kpi_year_month')) {
            Schema::table('kpi_monthly_summaries', fn ($t) => $t->index(['business_id', 'kpi_code', 'year', 'month_number'], 'idx_business_kpi_year_month'));
        }

        // Optimize businesses table
        if (Schema::hasColumn('businesses', 'status') && ! $indexExists('businesses', 'idx_business_status')) {
            Schema::table('businesses', fn ($t) => $t->index(['status'], 'idx_business_status'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for SQLite
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('kpi_daily_actuals', function (Blueprint $table) {
            $table->dropIndex('idx_source_date');
            $table->dropIndex('idx_status_business_date');
            $table->dropIndex('idx_verified_date');
            $table->dropIndex('idx_anomaly_business_date');
        });

        Schema::table('business_kpi_configurations', function (Blueprint $table) {
            $table->dropIndex('idx_status_business');
        });

        Schema::table('kpi_weekly_summaries', function (Blueprint $table) {
            $table->dropIndex('idx_business_kpi_year');
        });

        Schema::table('kpi_monthly_summaries', function (Blueprint $table) {
            $table->dropIndex('idx_business_kpi_year_month');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex('idx_business_status');
            if (Schema::hasColumn('businesses', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
