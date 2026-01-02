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
        Schema::create('kpi_industry_benchmarks', function (Blueprint $table) {
            $table->id();

            // KPI and Industry Information
            $table->string('kpi_code', 100)->index();
            $table->string('industry_code', 50)->index();
            $table->string('sub_category', 100)->nullable()->index();

            // Business Context
            $table->enum('business_size', ['micro', 'small', 'medium', 'large'])->default('micro');
            $table->enum('business_maturity', ['startup', 'growth', 'established', 'mature'])->default('startup');

            // Benchmark Values
            $table->decimal('benchmark_value', 15, 2);
            $table->decimal('min_acceptable', 15, 2)->nullable()->comment('Red/Yellow threshold');
            $table->decimal('target_value', 15, 2)->nullable()->comment('Yellow/Green threshold');
            $table->decimal('excellence_value', 15, 2)->nullable()->comment('Excellence threshold');

            // Percentile Benchmarks
            $table->decimal('p25_value', 15, 2)->nullable()->comment('25th percentile (bottom quarter)');
            $table->decimal('p50_value', 15, 2)->nullable()->comment('50th percentile (median)');
            $table->decimal('p75_value', 15, 2)->nullable()->comment('75th percentile (top quarter)');
            $table->decimal('p90_value', 15, 2)->nullable()->comment('90th percentile (top 10%)');

            // Statistical Data
            $table->decimal('std_deviation', 15, 2)->nullable()->comment('Standard deviation');
            $table->integer('sample_size')->nullable()->comment('Number of businesses in benchmark');

            // Seasonality Adjustments
            $table->json('seasonality_factors')->nullable()->comment('Monthly adjustment factors [1-12]');

            // Metadata
            $table->string('data_source', 100)->default('internal')->comment('Source of benchmark data');
            $table->date('benchmark_period_start')->nullable();
            $table->date('benchmark_period_end')->nullable();
            $table->date('last_updated')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for efficient lookups
            $table->index(['kpi_code', 'industry_code', 'business_size', 'business_maturity'], 'kpi_benchmark_lookup_idx');
            $table->index(['industry_code', 'sub_category'], 'industry_category_idx');

            // Foreign key constraint
            $table->foreign('kpi_code')->references('kpi_code')->on('kpi_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_industry_benchmarks');
    }
};
