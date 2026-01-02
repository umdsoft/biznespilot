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
        Schema::create('kpi_templates', function (Blueprint $table) {
            $table->id();

            // KPI Classification
            $table->enum('category', [
                'marketing',
                'sales',
                'advertising',
                'operational',
                'retention',
                'financial'
            ])->index();

            // KPI Identification
            $table->string('kpi_code', 100)->unique()->comment('Unique identifier: booking_inquiry_volume');
            $table->string('kpi_name', 200)->comment('Display name in English');
            $table->string('kpi_name_uz', 200)->comment('Display name in Uzbek');
            $table->string('kpi_name_ru', 200)->nullable()->comment('Display name in Russian');

            // KPI Description
            $table->text('description')->nullable()->comment('What this KPI measures');
            $table->text('description_uz')->nullable()->comment('Description in Uzbek');
            $table->text('measurement_method')->nullable()->comment('How to measure this KPI');
            $table->text('formula')->nullable()->comment('Calculation formula if applicable');

            // KPI Behavior
            $table->enum('good_direction', ['higher', 'lower'])->default('higher')
                ->comment('higher = bigger is better, lower = smaller is better');
            $table->enum('default_frequency', ['daily', 'weekly', 'monthly', 'quarterly'])->default('daily');
            $table->string('default_unit', 50)->default('number')->comment('dona, %, UZS, minutes, etc.');

            // Applicability
            $table->boolean('is_universal')->default(false)->index()
                ->comment('TRUE = applicable to all businesses');
            $table->json('applicable_industries')->nullable()
                ->comment('Array of industry codes: ["food_beverage", "fashion_retail"]');
            $table->json('applicable_subcategories')->nullable()
                ->comment('Array of subcategory codes');

            // Priority & Importance
            $table->enum('priority_level', ['critical', 'high', 'medium', 'low'])->default('medium')->index();
            $table->decimal('default_weight', 3, 1)->default(1.0)
                ->comment('Weight for overall score calculation (0.5 to 3.0)');

            // Business Maturity Requirements
            $table->enum('min_business_age', ['new', 'growing', 'established', 'any'])->default('any')
                ->comment('Minimum business maturity to use this KPI');
            $table->json('excluded_for_maturity')->nullable()
                ->comment('Array of maturity levels where this KPI should not be used');

            // Display & Formatting
            $table->string('icon', 50)->nullable()->comment('Emoji or icon identifier');
            $table->string('color_code', 20)->nullable()->comment('Hex color for UI display');
            $table->integer('display_order')->default(0)->comment('Sort order in UI');

            // Thresholds (Optional - can be overridden by industry benchmarks)
            $table->decimal('default_green_threshold', 5, 2)->nullable()->comment('Default % for green status');
            $table->decimal('default_yellow_threshold', 5, 2)->nullable()->comment('Default % for yellow status');

            // Meta Information
            $table->text('tips')->nullable()->comment('Tips for improving this KPI');
            $table->text('tips_uz')->nullable();
            $table->string('help_url')->nullable()->comment('Link to documentation');

            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->text('deprecation_note')->nullable()->comment('Why this KPI is deprecated if inactive');

            // Audit
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['category', 'is_active']);
            $table->index(['is_universal', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_templates');
    }
};
