<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE FIX: Add missing indexes to meta_insights table
     *
     * These columns are used in GROUP BY queries but were missing indexes:
     * - campaign_id: Used for campaign-level aggregation
     * - age_range: Used for demographics breakdown
     * - gender: Used for demographics breakdown
     * - publisher_platform: Used for platform breakdown
     * - platform_position: Used for placement breakdown
     */
    public function up(): void
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('meta_insights')) {
            return;
        }

        Schema::table('meta_insights', function (Blueprint $table) {
            // Campaign aggregation queries
            if (!$this->indexExists('meta_insights', 'meta_insights_campaign_id_index')) {
                $table->index('campaign_id', 'meta_insights_campaign_id_index');
            }
        });

        Schema::table('meta_insights', function (Blueprint $table) {
            // Demographics queries - age_range
            if (!$this->indexExists('meta_insights', 'meta_insights_age_range_index')) {
                $table->index('age_range', 'meta_insights_age_range_index');
            }
        });

        Schema::table('meta_insights', function (Blueprint $table) {
            // Demographics queries - gender
            if (!$this->indexExists('meta_insights', 'meta_insights_gender_index')) {
                $table->index('gender', 'meta_insights_gender_index');
            }
        });

        Schema::table('meta_insights', function (Blueprint $table) {
            // Placement queries - publisher_platform
            if (!$this->indexExists('meta_insights', 'meta_insights_publisher_platform_index')) {
                $table->index('publisher_platform', 'meta_insights_publisher_platform_index');
            }
        });

        Schema::table('meta_insights', function (Blueprint $table) {
            // Placement queries - platform_position
            if (!$this->indexExists('meta_insights', 'meta_insights_platform_position_index')) {
                $table->index('platform_position', 'meta_insights_platform_position_index');
            }
        });

        Schema::table('meta_insights', function (Blueprint $table) {
            // Composite index for common query pattern
            if (!$this->indexExists('meta_insights', 'meta_insights_account_campaign_date_index')) {
                $table->index(['ad_account_id', 'campaign_id', 'date_start'], 'meta_insights_account_campaign_date_index');
            }
        });
    }

    /**
     * Check if index exists using raw query (Laravel 11 compatible)
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $dbName = config('database.connections.mysql.database');

        $result = DB::select("
            SELECT COUNT(*) as cnt
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$dbName, $table, $indexName]);

        return ($result[0]->cnt ?? 0) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('meta_insights')) {
            return;
        }

        Schema::table('meta_insights', function (Blueprint $table) {
            try { $table->dropIndex('meta_insights_campaign_id_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('meta_insights_age_range_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('meta_insights_gender_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('meta_insights_publisher_platform_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('meta_insights_platform_position_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('meta_insights_account_campaign_date_index'); } catch (\Exception $e) {}
        });
    }
};
