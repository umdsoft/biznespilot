<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip for SQLite (used in tests) - foreign key handling is different
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Clean up orphaned records before adding constraints
        $this->cleanupOrphanedRecords();

        // KPI Daily Actuals
        if (!$this->foreignKeyExists('kpi_daily_actuals', 'kpi_daily_actuals_business_id_foreign')) {
            Schema::table('kpi_daily_actuals', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }

        // Leads
        if (Schema::hasTable('leads') && !$this->foreignKeyExists('leads', 'leads_business_id_foreign')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }

        // Instagram Accounts
        if (Schema::hasTable('instagram_accounts') && !$this->foreignKeyExists('instagram_accounts', 'instagram_accounts_business_id_foreign')) {
            Schema::table('instagram_accounts', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }

        // Facebook Pages
        if (Schema::hasTable('facebook_pages') && !$this->foreignKeyExists('facebook_pages', 'facebook_pages_business_id_foreign')) {
            Schema::table('facebook_pages', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }

        // Sales Metrics
        if (Schema::hasTable('sales_metrics') && !$this->foreignKeyExists('sales_metrics', 'sales_metrics_business_id_foreign')) {
            Schema::table('sales_metrics', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }

        // Marketing Metrics
        if (Schema::hasTable('marketing_metrics') && !$this->foreignKeyExists('marketing_metrics', 'marketing_metrics_business_id_foreign')) {
            Schema::table('marketing_metrics', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }

        // KPI Configurations
        if (Schema::hasTable('kpi_configurations') && !$this->foreignKeyExists('kpi_configurations', 'kpi_configurations_business_id_foreign')) {
            Schema::table('kpi_configurations', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }

        // Competitors
        if (Schema::hasTable('competitors') && !$this->foreignKeyExists('competitors', 'competitors_business_id_foreign')) {
            Schema::table('competitors', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }

        // Dream Buyers
        if (Schema::hasTable('dream_buyers') && !$this->foreignKeyExists('dream_buyers', 'dream_buyers_business_id_foreign')) {
            Schema::table('dream_buyers', function (Blueprint $table) {
                $table->foreign('business_id')
                    ->references('id')->on('businesses')
                    ->onDelete('cascade');
            });
        }
    }

    protected function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        $result = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$dbName, $table, $foreignKey]
        );

        return !empty($result);
    }

    protected function cleanupOrphanedRecords(): void
    {
        $validBusinessIds = DB::table('businesses')->pluck('id')->toArray();

        if (empty($validBusinessIds)) {
            return;
        }

        $tablesToClean = [
            'kpi_daily_actuals', 'leads', 'instagram_accounts', 'facebook_pages',
            'sales_metrics', 'marketing_metrics', 'kpi_configurations', 'competitors', 'dream_buyers',
        ];

        foreach ($tablesToClean as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereNotIn('business_id', $validBusinessIds)->delete();
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $tables = ['kpi_daily_actuals', 'leads', 'instagram_accounts', 'facebook_pages',
            'sales_metrics', 'marketing_metrics', 'kpi_configurations', 'competitors', 'dream_buyers'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::table($table, fn ($t) => $t->dropForeign(['business_id']));
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist
                }
            }
        }
    }
};
