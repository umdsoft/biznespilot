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
        Schema::table('ai_insights', function (Blueprint $table) {
            // Add new columns for FAZA 4
            $table->string('insight_type')->nullable()->after('type'); // opportunity, warning, recommendation, trend, anomaly, celebration
            $table->string('category')->nullable()->after('insight_type'); // content, advertising, pricing, competitor, retention, growth, chatbot, funnel
            $table->string('title_uz')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_uz');
            $table->text('description_uz')->nullable()->after('content');
            $table->text('description_en')->nullable()->after('description_uz');
            $table->text('action_uz')->nullable()->after('description_en');
            $table->text('action_en')->nullable()->after('action_uz');
            $table->json('data_points')->nullable()->after('data');
            $table->string('metric_affected')->nullable()->after('data_points');
            $table->string('expected_impact')->nullable()->after('metric_affected');
            $table->decimal('confidence_score', 3, 2)->default(0.8)->after('expected_impact');
            $table->string('ai_model')->nullable()->after('confidence_score');
            $table->text('ai_prompt_context')->nullable()->after('ai_model');
            $table->string('status')->default('new')->after('is_actionable'); // new, viewed, acted, dismissed, expired
            $table->timestamp('viewed_at')->nullable()->after('read_at');
            $table->timestamp('acted_at')->nullable()->after('viewed_at');
            $table->text('action_result')->nullable()->after('action_taken');
            $table->timestamp('expires_at')->nullable()->after('action_result');
            $table->boolean('is_active')->default(true)->after('expires_at');

            // Add indexes
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'insight_type']);
            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_insights', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['business_id', 'insight_type']);
            $table->dropIndex(['business_id', 'is_active']);

            $table->dropColumn([
                'insight_type',
                'category',
                'title_uz',
                'title_en',
                'description_uz',
                'description_en',
                'action_uz',
                'action_en',
                'data_points',
                'metric_affected',
                'expected_impact',
                'confidence_score',
                'ai_model',
                'ai_prompt_context',
                'status',
                'viewed_at',
                'acted_at',
                'action_result',
                'expires_at',
                'is_active',
            ]);
        });
    }
};
