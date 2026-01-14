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
        Schema::table('businesses', function (Blueprint $table) {
            // Add industry_code column for KPI configuration
            // This will be auto-populated from category/industry fields
            $table->string('industry_code', 50)->nullable()->after('category');
            $table->index('industry_code');

            // Add industry_detected_at for tracking when industry was auto-detected
            $table->timestamp('industry_detected_at')->nullable()->after('industry_code');
        });

        // Auto-populate industry_code from existing category data
        // Skip complex CASE statement for SQLite (used in tests)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                UPDATE businesses
                SET industry_code = CASE
                    -- E-commerce variants
                    WHEN LOWER(category) LIKE '%ecommerce%' OR LOWER(category) LIKE '%e-commerce%' OR LOWER(category) LIKE '%online%' THEN 'ecommerce'

                    -- Restaurant variants
                    WHEN LOWER(category) LIKE '%restaurant%' OR LOWER(category) LIKE '%restoran%' OR LOWER(category) LIKE '%cafe%' THEN 'restaurant'

                    -- Retail variants
                    WHEN LOWER(category) LIKE '%retail%' OR LOWER(category) LIKE '%shop%' OR LOWER(category) LIKE '%store%' THEN 'retail'

                    -- Service variants
                    WHEN LOWER(category) LIKE '%service%' OR LOWER(category) LIKE '%consulting%' THEN 'service'

                    -- SaaS variants
                    WHEN LOWER(category) LIKE '%saas%' OR LOWER(category) LIKE '%software%' OR LOWER(category) LIKE '%app%' THEN 'saas'

                    -- Beauty variants
                    WHEN LOWER(category) LIKE '%beauty%' OR LOWER(category) LIKE '%salon%' THEN 'beauty'

                    -- Fitness variants
                    WHEN LOWER(category) LIKE '%fitness%' OR LOWER(category) LIKE '%gym%' OR LOWER(category) LIKE '%sport%' THEN 'fitness'

                    ELSE 'default'
                END,
                industry_detected_at = NOW()
                WHERE industry_code IS NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['industry_code', 'industry_detected_at']);
        });
    }
};
