<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step Definitions
        Schema::create('step_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->integer('phase')->default(1);
            $table->string('category', 50); // profile, integration, framework
            $table->string('name_uz');
            $table->string('name_en');
            $table->text('description_uz')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_required')->default(true);
            $table->json('depends_on')->nullable();
            $table->json('required_fields')->nullable();
            $table->json('completion_rules')->nullable();
            $table->json('config')->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('estimated_time_minutes')->default(5);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('phase');
            $table->index('category');
            $table->index('sort_order');
            $table->index('is_active');
        });

        // Onboarding Progress
        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->unique();

            // Current state
            $table->integer('current_phase')->default(1);
            $table->string('current_step', 50)->nullable();

            // Phase 1: Data Input
            $table->string('phase_1_status', 20)->default('not_started'); // not_started, in_progress, completed
            $table->integer('phase_1_completion_percent')->default(0);
            $table->timestamp('phase_1_completed_at')->nullable();

            // Phase 2: AI Diagnostic
            $table->string('phase_2_status', 20)->default('locked'); // locked, ready, processing, completed
            $table->timestamp('phase_2_started_at')->nullable();
            $table->timestamp('phase_2_completed_at')->nullable();

            // Phase 3: Strategy
            $table->string('phase_3_status', 20)->default('locked'); // locked, in_progress, completed
            $table->timestamp('phase_3_completed_at')->nullable();

            // Phase 4: Launch
            $table->string('phase_4_status', 20)->default('locked'); // locked, ready, launched
            $table->timestamp('launched_at')->nullable();

            // Overall
            $table->integer('overall_completion_percent')->default(0);
            $table->timestamp('onboarding_completed_at')->nullable();

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // Onboarding Steps (completed steps per business)
        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('step_definition_id');
            $table->boolean('is_completed')->default(false);
            $table->integer('completion_percent')->default(0);
            $table->json('validation_errors')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('step_definition_id')->references('id')->on('step_definitions')->onDelete('cascade');
            $table->unique(['business_id', 'step_definition_id']);
        });

        // Business Maturity Assessments
        Schema::create('business_maturity_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->unique();

            // Revenue & Budget
            $table->string('monthly_revenue_range', 50)->nullable();
            $table->string('monthly_marketing_budget_range', 50)->nullable();

            // Main challenges
            $table->json('main_challenges')->nullable();

            // Scores
            $table->integer('overall_score')->default(0);
            $table->string('maturity_level', 50)->nullable();
            $table->json('category_scores')->nullable();
            $table->json('answers')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // Business Problems
        Schema::create('business_problems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('category', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity', 20)->default('medium');
            $table->string('status', 20)->default('identified');
            $table->json('potential_solutions')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('category');
        });

        // Market Research
        Schema::create('market_research', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('type', 50);
            $table->string('title');
            $table->text('content')->nullable();
            $table->json('data')->nullable();
            $table->string('source')->nullable();
            $table->date('research_date')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('type');
        });

        // Marketing Hypotheses
        Schema::create('marketing_hypotheses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('title');
            $table->text('hypothesis');
            $table->text('expected_outcome')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('test_results')->nullable();
            $table->timestamp('tested_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_hypotheses');
        Schema::dropIfExists('market_research');
        Schema::dropIfExists('business_problems');
        Schema::dropIfExists('business_maturity_assessments');
        Schema::dropIfExists('onboarding_steps');
        Schema::dropIfExists('onboarding_progress');
        Schema::dropIfExists('step_definitions');
    }
};
