<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dream_buyers', function (Blueprint $table) {
            // Demographics
            $table->integer('age_min')->nullable()->after('is_primary');
            $table->integer('age_max')->nullable()->after('age_min');
            $table->enum('gender', ['male', 'female', 'both'])->default('both')->after('age_max');
            $table->json('location_cities')->nullable()->after('gender');
            $table->bigInteger('income_range_min')->nullable()->after('location_cities');
            $table->bigInteger('income_range_max')->nullable()->after('income_range_min');
            $table->string('occupation')->nullable()->after('income_range_max');
            $table->enum('education_level', ['school', 'college', 'bachelor', 'master', 'phd'])->nullable()->after('occupation');
            $table->enum('family_status', ['single', 'married', 'married_with_kids'])->nullable()->after('education_level');

            // Behavioral
            $table->json('buying_triggers')->nullable()->after('happiness_triggers');
            $table->json('decision_factors')->nullable()->after('buying_triggers');
            $table->json('objection_patterns')->nullable()->after('decision_factors');
            $table->json('preferred_channels')->nullable()->after('objection_patterns');
            $table->json('content_preferences')->nullable()->after('preferred_channels');
            $table->enum('price_sensitivity', ['low', 'medium', 'high'])->nullable()->after('content_preferences');
            $table->integer('decision_time_days')->nullable()->after('price_sensitivity');

            // AI enrichment
            $table->timestamp('ai_enriched_at')->nullable()->after('decision_time_days');
            $table->json('ai_insights')->nullable()->after('ai_enriched_at');
        });
    }

    public function down(): void
    {
        Schema::table('dream_buyers', function (Blueprint $table) {
            $table->dropColumn([
                'age_min', 'age_max', 'gender', 'location_cities',
                'income_range_min', 'income_range_max', 'occupation',
                'education_level', 'family_status', 'buying_triggers',
                'decision_factors', 'objection_patterns', 'preferred_channels',
                'content_preferences', 'price_sensitivity', 'decision_time_days',
                'ai_enriched_at', 'ai_insights'
            ]);
        });
    }
};
