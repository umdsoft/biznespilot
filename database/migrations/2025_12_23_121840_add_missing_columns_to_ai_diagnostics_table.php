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
        Schema::table('ai_diagnostics', function (Blueprint $table) {
            // UUID for public-facing identifier
            $table->uuid('uuid')->unique()->nullable()->after('id');

            // References
            $table->foreignUuid('previous_diagnostic_id')->nullable()->after('version');
            $table->string('triggered_by')->nullable()->after('diagnostic_type');

            // Processing
            $table->string('processing_step')->nullable()->after('status');

            // Data period
            $table->date('data_period_start')->nullable();
            $table->date('data_period_end')->nullable();
            $table->json('data_sources_used')->nullable();
            $table->integer('data_points_analyzed')->nullable();

            // Scores
            $table->integer('overall_health_score')->nullable()->after('overall_score');
            $table->integer('marketing_score')->nullable();
            $table->integer('sales_score')->nullable();
            $table->integer('content_score')->nullable();
            $table->integer('funnel_score')->nullable();

            // Timestamps
            $table->timestamp('started_at')->nullable();

            // Analysis data
            $table->json('swot_analysis')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->text('ai_insights')->nullable();
            $table->json('benchmark_summary')->nullable();
            $table->json('trend_data')->nullable();

            // Error handling
            $table->text('error_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_diagnostics', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'previous_diagnostic_id',
                'triggered_by',
                'processing_step',
                'data_period_start',
                'data_period_end',
                'data_sources_used',
                'data_points_analyzed',
                'overall_health_score',
                'marketing_score',
                'sales_score',
                'content_score',
                'funnel_score',
                'started_at',
                'swot_analysis',
                'strengths',
                'weaknesses',
                'ai_insights',
                'benchmark_summary',
                'trend_data',
                'error_message',
            ]);
        });
    }
};
