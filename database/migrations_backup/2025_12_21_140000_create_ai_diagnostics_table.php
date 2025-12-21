<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('previous_diagnostic_id')->nullable()->constrained('ai_diagnostics')->nullOnDelete();

            // Type and trigger
            $table->enum('diagnostic_type', ['onboarding', 'weekly', 'monthly', 'quarterly', 'ad_hoc'])->default('onboarding');
            $table->enum('triggered_by', ['system', 'user', 'schedule'])->default('user');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('processing_step', 50)->nullable();

            // Data analyzed
            $table->date('data_period_start')->nullable();
            $table->date('data_period_end')->nullable();
            $table->json('data_sources_used')->nullable();
            $table->integer('data_points_analyzed')->default(0);

            // Scores (0-100)
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->unsignedTinyInteger('overall_health_score')->nullable();
            $table->unsignedTinyInteger('marketing_score')->nullable();
            $table->unsignedTinyInteger('sales_score')->nullable();
            $table->unsignedTinyInteger('content_score')->nullable();
            $table->unsignedTinyInteger('funnel_score')->nullable();

            // SWOT Analysis
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('opportunities')->nullable();
            $table->json('threats')->nullable();
            $table->json('swot_analysis')->nullable();

            // Recommendations
            $table->json('recommendations')->nullable();
            $table->json('critical_actions')->nullable();
            $table->json('high_priority_actions')->nullable();
            $table->json('medium_priority_actions')->nullable();
            $table->json('low_priority_actions')->nullable();

            // AI Generated
            $table->text('executive_summary')->nullable();
            $table->text('ai_insights')->nullable();
            $table->json('detailed_analysis')->nullable();
            $table->string('ai_model_used', 50)->nullable();
            $table->integer('ai_input_tokens')->default(0);
            $table->integer('ai_output_tokens')->default(0);
            $table->integer('ai_tokens_used')->default(0);
            $table->decimal('ai_cost', 10, 4)->default(0);

            // Raw data snapshot (for debugging/audit)
            $table->json('input_data_snapshot')->nullable();
            $table->json('kpi_snapshot')->nullable();
            $table->json('benchmark_comparison')->nullable();
            $table->json('benchmark_summary')->nullable();
            $table->json('trend_data')->nullable();

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'diagnostic_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_diagnostics');
    }
};
