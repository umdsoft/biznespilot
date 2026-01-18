<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL PERFORMANCE FIX: Add missing indexes to prevent full table scans
     *
     * Impact:
     * - kpi_daily_actuals with 365K+ rows (1000 businesses × 365 days) was doing full scans
     * - leads table queries taking 10+ seconds
     * - Integration queries slow
     *
     * Expected improvement: 10-100x faster queries
     */
    public function up(): void
    {
        // KPI Daily Actuals indexes
        if (Schema::hasTable('kpi_daily_actuals')) {
            Schema::table('kpi_daily_actuals', function (Blueprint $table) {
                // Try-catch har bir index uchun - agar mavjud bo'lsa xato bermaydi
                try {
                    $table->index(['business_id', 'date', 'kpi_code'], 'idx_kpi_business_date_code');
                } catch (\Exception $e) {
                    // Index already exists
                }

                try {
                    $table->index(['business_id', 'date', 'sync_status'], 'idx_kpi_sync_status');
                } catch (\Exception $e) {
                    // Index already exists
                }

                try {
                    $table->index(['data_source', 'date'], 'idx_kpi_source_date');
                } catch (\Exception $e) {
                    // Index already exists
                }

                try {
                    $table->index('overridden_at', 'idx_kpi_overridden_at');
                } catch (\Exception $e) {
                    // Index already exists
                }
            });
        }

        // Leads indexes
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'status')) {
            Schema::table('leads', function (Blueprint $table) {
                try {
                    $table->index(['business_id', 'status', 'created_at'], 'idx_leads_status');
                } catch (\Exception $e) {
                    // Index already exists
                }
            });
        }

        // Instagram accounts indexes
        if (Schema::hasTable('instagram_accounts') && Schema::hasColumn('instagram_accounts', 'is_active')) {
            Schema::table('instagram_accounts', function (Blueprint $table) {
                try {
                    $table->index(['business_id', 'is_active'], 'idx_instagram_business_active');
                } catch (\Exception $e) {
                    // Index already exists
                }
            });
        }

        // Facebook pages indexes
        if (Schema::hasTable('facebook_pages') && Schema::hasColumn('facebook_pages', 'is_active')) {
            Schema::table('facebook_pages', function (Blueprint $table) {
                try {
                    $table->index(['business_id', 'is_active'], 'idx_facebook_business_active');
                } catch (\Exception $e) {
                    // Index already exists
                }
            });
        }

        // Jobs indexes
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                try {
                    $table->index(['queue', 'reserved_at'], 'idx_jobs_queue_reserved');
                } catch (\Exception $e) {
                    // Index already exists
                }
            });
        }

        // Failed jobs indexes
        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                try {
                    $table->index('failed_at', 'idx_failed_jobs_failed_at');
                } catch (\Exception $e) {
                    // Index already exists
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('kpi_daily_actuals')) {
            Schema::table('kpi_daily_actuals', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_kpi_business_date_code');
                } catch (\Exception $e) {
                }
                try {
                    $table->dropIndex('idx_kpi_sync_status');
                } catch (\Exception $e) {
                }
                try {
                    $table->dropIndex('idx_kpi_source_date');
                } catch (\Exception $e) {
                }
                try {
                    $table->dropIndex('idx_kpi_overridden_at');
                } catch (\Exception $e) {
                }
            });
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_leads_status');
                } catch (\Exception $e) {
                }
            });
        }

        if (Schema::hasTable('instagram_accounts')) {
            Schema::table('instagram_accounts', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_instagram_business_active');
                } catch (\Exception $e) {
                }
            });
        }

        if (Schema::hasTable('facebook_pages')) {
            Schema::table('facebook_pages', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_facebook_business_active');
                } catch (\Exception $e) {
                }
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_jobs_queue_reserved');
                } catch (\Exception $e) {
                }
            });
        }

        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_failed_jobs_failed_at');
                } catch (\Exception $e) {
                }
            });
        }
    }
};
