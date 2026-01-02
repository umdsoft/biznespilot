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
        // Use raw SQL to create indexes if not exists
        $connection = Schema::getConnection();

        // Optimize kpi_daily_actuals table
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_source_date ON kpi_daily_actuals (data_source, date)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_status_business_date ON kpi_daily_actuals (status, business_id, date)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_verified_date ON kpi_daily_actuals (is_verified, date)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_anomaly_business_date ON kpi_daily_actuals (is_anomaly, business_id, date)');

        // Optimize business_kpi_configurations
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_status_business ON business_kpi_configurations (status, business_id)');

        // Optimize kpi_weekly_summaries
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_business_kpi_year ON kpi_weekly_summaries (business_id, kpi_code, year)');

        // Optimize kpi_monthly_summaries
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_business_kpi_year_month ON kpi_monthly_summaries (business_id, kpi_code, year, month_number)');

        // Optimize businesses table
        if (Schema::hasColumn('businesses', 'status')) {
            $connection->statement('CREATE INDEX IF NOT EXISTS idx_business_status ON businesses (status)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
