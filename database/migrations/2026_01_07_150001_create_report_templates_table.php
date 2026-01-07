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
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('business_id')->nullable()->constrained()->cascadeOnDelete();

            // Template info
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['system', 'custom'])->default('custom');
            $table->enum('category', ['health', 'marketing', 'sales', 'financial', 'comprehensive'])->default('comprehensive');

            // Template structure
            $table->json('sections')->comment('Array of section configurations');
            $table->json('metrics')->comment('Array of metric codes to include');
            $table->json('kpis')->nullable()->comment('KPI configurations');
            $table->json('charts')->nullable()->comment('Chart configurations');
            $table->json('tables')->nullable()->comment('Table configurations');

            // Display settings
            $table->string('language', 5)->default('uz');
            $table->json('styles')->nullable()->comment('Custom styling options');
            $table->string('logo_path')->nullable();
            $table->string('header_text')->nullable();
            $table->string('footer_text')->nullable();

            // Insight configuration
            $table->boolean('auto_insights')->default(true);
            $table->json('insight_rules')->nullable()->comment('Custom insight generation rules');
            $table->unsignedTinyInteger('max_insights')->default(5);

            // Comparison settings
            $table->boolean('include_previous_period')->default(true);
            $table->boolean('include_benchmarks')->default(true);
            $table->boolean('include_targets')->default(true);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('usage_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'is_active']);
            $table->index(['type', 'category']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
