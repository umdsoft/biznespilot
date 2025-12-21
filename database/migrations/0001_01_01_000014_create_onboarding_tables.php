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
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('order');
            $table->index('is_active');
        });

        // Onboarding Progress
        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->unique();
            $table->string('current_step', 50)->nullable();
            $table->integer('completed_steps_count')->default(0);
            $table->integer('total_steps_count')->default(0);
            $table->integer('progress_percentage')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('step_data')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // Onboarding Steps (completed steps per business)
        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('step_definition_id');
            $table->string('status', 20)->default('pending'); // pending, in_progress, completed, skipped
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
