<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business Systematization Tables
 * Based on Denis Shenukov's "Business Systematization" methodology
 *
 * Key Concepts:
 * - Plan/Fact tracking with Base (zero point)
 * - Two-parameter motivation (Fix + Bonus)
 * - Three-parameter motivation (Fix + Soft Salary + Bonus)
 * - Team vs Individual motivation
 * - Employee types: Thinker (Думатель) vs Doer (Делатель)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // PART 1: GOALS AND PLANNING (Maqsad va Rejalashtirish)
        // ============================================================

        // Company Goals - BSC (Balanced Scorecard) based
        Schema::create('company_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('perspective', ['finance', 'customers', 'processes', 'employees']); // BSC 4 perspectives
            $table->enum('goal_type', ['strategic', 'operational', 'financial']);
            $table->enum('period_type', ['yearly', 'quarterly', 'monthly']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('target_value', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('base_value', 15, 2)->default(0); // Zero point (nol nuqtasi)
            $table->string('unit')->nullable(); // %, som, pieces, etc.
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->uuid('parent_goal_id')->nullable(); // For goal decomposition
            $table->uuid('responsible_user_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->integer('weight')->default(100); // Weight in percentage for scoring
            $table->json('milestones')->nullable(); // Intermediate checkpoints
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('parent_goal_id')->references('id')->on('company_goals')->onDelete('set null');
            $table->index(['business_id', 'status', 'period_type']);
            $table->index(['business_id', 'perspective']);
        });

        // ============================================================
        // PART 2: SALES MODULE (Sotuv Bo'limi)
        // ============================================================

        // Sales Targets - Department and Individual Plans
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->enum('target_type', ['department', 'team', 'individual']);
            $table->uuid('department_id')->nullable();
            $table->uuid('user_id')->nullable(); // For individual targets
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly']);
            $table->date('period_start');
            $table->date('period_end');

            // Plan values
            $table->decimal('plan_revenue', 15, 2)->default(0); // Reja - daromad
            $table->decimal('plan_deals', 10, 0)->default(0); // Reja - shartnomalar soni
            $table->decimal('plan_new_clients', 10, 0)->default(0); // Yangi mijozlar
            $table->decimal('plan_margin_percent', 5, 2)->default(0); // Marja %

            // Base values (Zero point - nol nuqtasi)
            $table->decimal('base_revenue', 15, 2)->default(0);
            $table->decimal('base_deals', 10, 0)->default(0);
            $table->decimal('base_new_clients', 10, 0)->default(0);

            // Fact values (auto-calculated from sales)
            $table->decimal('fact_revenue', 15, 2)->default(0);
            $table->decimal('fact_deals', 10, 0)->default(0);
            $table->decimal('fact_new_clients', 10, 0)->default(0);
            $table->decimal('fact_margin_percent', 5, 2)->default(0);

            // KPI Calculation: (Fact - Base) / (Plan - Base)
            $table->decimal('kpi_score', 5, 4)->default(0);

            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'period_type', 'period_start']);
            $table->index(['business_id', 'user_id', 'period_start']);
        });

        // Sales Manager Daily Activity Tracking
        Schema::create('sales_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->uuid('sales_target_id')->nullable();
            $table->date('activity_date');

            // Activity metrics
            $table->integer('calls_made')->default(0);
            $table->integer('calls_answered')->default(0);
            $table->integer('meetings_scheduled')->default(0);
            $table->integer('meetings_held')->default(0);
            $table->integer('proposals_sent')->default(0);
            $table->integer('deals_closed')->default(0);
            $table->decimal('revenue_generated', 15, 2)->default(0);

            // Time tracking
            $table->integer('talk_time_minutes')->default(0);

            $table->json('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'user_id', 'activity_date']);
            $table->index(['business_id', 'activity_date']);
        });

        // Receivables (Debitorka) Tracking
        Schema::create('receivables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('deal_id')->nullable(); // Link to CRM deal
            $table->uuid('client_id')->nullable();
            $table->uuid('responsible_user_id');

            $table->string('invoice_number')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);

            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();

            $table->integer('overdue_days')->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'written_off'])->default('pending');

            $table->text('notes')->nullable();
            $table->json('payment_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'status', 'due_date']);
            $table->index(['business_id', 'responsible_user_id']);
        });

        // Sales Funnel Stages with Conversion Tracking
        Schema::create('sales_funnel_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('code')->nullable(); // lead, call, meeting, proposal, negotiation, deal, payment
            $table->integer('order')->default(0);
            $table->string('color')->default('#3B82F6');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_won_stage')->default(false);
            $table->boolean('is_lost_stage')->default(false);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_active', 'order']);
        });

        // Rejection Reasons (Rad etish sabablari)
        Schema::create('rejection_reasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('category')->nullable(); // price, competition, timing, quality, other
            $table->text('description')->nullable();
            $table->integer('count')->default(0); // Auto-incremented on use
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'category']);
        });

        // Lost Deals with Rejection Tracking
        Schema::create('lost_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('deal_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('rejection_reason_id')->nullable();
            $table->uuid('lost_to_competitor_id')->nullable();
            $table->uuid('responsible_user_id');

            $table->decimal('potential_value', 15, 2)->nullable();
            $table->uuid('lost_at_stage_id')->nullable();
            $table->date('lost_date');
            $table->text('notes')->nullable();
            $table->text('lessons_learned')->nullable();

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('rejection_reason_id')->references('id')->on('rejection_reasons')->onDelete('set null');
            $table->index(['business_id', 'lost_date']);
            $table->index(['business_id', 'rejection_reason_id']);
        });

        // ============================================================
        // PART 3: MOTIVATION SYSTEM (Motivatsiya Tizimi)
        // ============================================================

        // Motivation Schemes
        Schema::create('motivation_schemes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->text('description')->nullable();

            // Scheme type based on book
            $table->enum('scheme_type', [
                'two_parameter',    // Fix + Bonus
                'three_parameter',  // Fix + Soft Salary + Bonus
                'project_based',    // Project team bonus
                'key_tasks'         // Key tasks map based
            ])->default('two_parameter');

            // Motivation type
            $table->enum('motivation_type', ['individual', 'team', 'mixed'])->default('team');

            // Applicable to
            $table->enum('level', ['top_management', 'middle_management', 'specialists', 'workers'])->default('specialists');
            $table->uuid('department_id')->nullable();
            $table->uuid('position_id')->nullable();

            // Period
            $table->enum('bonus_period', ['monthly', 'quarterly', 'semi_annual', 'annual', 'project'])->default('monthly');

            // Validity
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_active']);
        });

        // Motivation Scheme Components (Fix, Soft Salary, Bonus rules)
        Schema::create('motivation_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('motivation_scheme_id');

            $table->enum('component_type', [
                'fixed_salary',     // Qattiq oklad
                'soft_salary',      // Yumshoq oklad (function-based)
                'bonus',            // Bonus (result-based)
                'penalty'           // Kamaytiruvchi koeffitsient
            ]);

            $table->string('name');
            $table->text('description')->nullable();

            // Amount settings
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->nullable();

            // Calculation settings
            $table->enum('calculation_type', [
                'fixed',            // Fixed amount
                'percentage',       // Percentage of something
                'formula',          // Custom formula
                'scale'             // Progressive/regressive scale
            ])->default('fixed');

            $table->string('percentage_of')->nullable(); // revenue, profit, plan_completion
            $table->decimal('percentage_value', 5, 2)->nullable();

            // For soft salary - function requirements
            $table->json('function_requirements')->nullable();
            // Example: [{"name": "CRM to'ldirilishi", "weight": 30, "type": "percentage"}, ...]

            // For bonus - KPI linkage
            $table->json('kpi_linkage')->nullable();
            // Example: {"metric": "sales_plan", "base": 6000000, "plan": 10000000}

            // For scale-based calculation
            $table->json('scale_table')->nullable();
            // Example: [{"min": 80, "max": 90, "coefficient": 0.7}, {"min": 90, "max": 100, "coefficient": 1.0}]

            $table->integer('weight')->default(100); // Weight in total motivation
            $table->integer('order')->default(0);

            $table->timestamps();

            $table->foreign('motivation_scheme_id')->references('id')->on('motivation_schemes')->onDelete('cascade');
        });

        // Employee Motivation Assignments
        Schema::create('employee_motivations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->uuid('motivation_scheme_id');

            // Personal adjustments
            $table->decimal('personal_fixed_salary', 15, 2)->nullable();
            $table->decimal('personal_soft_salary', 15, 2)->nullable();
            $table->json('personal_adjustments')->nullable();

            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('motivation_scheme_id')->references('id')->on('motivation_schemes')->onDelete('cascade');
            $table->unique(['business_id', 'user_id', 'effective_from']);
        });

        // Monthly/Period Motivation Calculations
        Schema::create('motivation_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->uuid('employee_motivation_id');

            $table->enum('period_type', ['monthly', 'quarterly', 'semi_annual', 'annual', 'project']);
            $table->date('period_start');
            $table->date('period_end');

            // Calculated amounts
            $table->decimal('fixed_salary', 15, 2)->default(0);
            $table->decimal('soft_salary_earned', 15, 2)->default(0);
            $table->decimal('soft_salary_max', 15, 2)->default(0);
            $table->decimal('bonus_earned', 15, 2)->default(0);
            $table->decimal('bonus_max', 15, 2)->default(0);
            $table->decimal('penalties', 15, 2)->default(0);
            $table->decimal('total_earned', 15, 2)->default(0);

            // KPI scores
            $table->decimal('kpi_score', 5, 4)->default(0); // (Fact-Base)/(Plan-Base)
            $table->decimal('soft_salary_completion', 5, 2)->default(0); // Percentage

            // Detailed breakdown
            $table->json('calculation_details')->nullable();
            // Stores all component calculations

            $table->enum('status', ['draft', 'calculated', 'approved', 'paid'])->default('draft');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'user_id', 'period_start']);
            $table->index(['business_id', 'status']);
        });

        // Key Tasks Map (Карта ключевых задач)
        Schema::create('key_task_maps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->uuid('motivation_scheme_id')->nullable();

            $table->string('title');
            $table->enum('period_type', ['monthly', 'quarterly']);
            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('total_bonus_fund', 15, 2)->default(0);
            $table->decimal('earned_bonus', 15, 2)->default(0);

            // Completion thresholds
            $table->integer('min_completion_percent')->default(80); // Below this = no bonus
            $table->integer('full_bonus_percent')->default(100);

            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'user_id', 'status']);
        });

        // Key Tasks (Individual tasks in the map)
        Schema::create('key_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('key_task_map_id');

            $table->string('title');
            $table->text('description')->nullable();
            $table->text('success_criteria')->nullable(); // How to measure completion

            $table->integer('weight')->default(10); // Weight in percentage
            $table->date('due_date');

            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->integer('completion_percent')->default(0);
            $table->timestamp('completed_at')->nullable();

            $table->text('completion_notes')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->foreign('key_task_map_id')->references('id')->on('key_task_maps')->onDelete('cascade');
        });

        // ============================================================
        // PART 4: HR ENHANCEMENTS
        // ============================================================

        // Enhanced Vacancy Card (Vakansiya kartasi)
        Schema::create('vacancy_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('department_id')->nullable();
            $table->uuid('position_id')->nullable();
            $table->uuid('reports_to_user_id')->nullable();

            $table->string('title');
            $table->text('purpose')->nullable(); // Why this position is needed

            // Position requirements
            $table->json('main_tasks')->nullable(); // Array of main responsibilities
            $table->json('kpi_requirements')->nullable(); // Expected KPIs
            $table->json('competencies')->nullable(); // Required skills/competencies

            // Employee type needed
            $table->enum('employee_type_needed', ['thinker', 'doer', 'mixed'])->default('doer');
            // Thinker = Думатель (independent, makes decisions)
            // Doer = Делатель (follows instructions, executes)

            // Motivation structure
            $table->decimal('salary_from', 15, 2)->nullable();
            $table->decimal('salary_to', 15, 2)->nullable();
            $table->uuid('motivation_scheme_id')->nullable();
            $table->text('motivation_description')->nullable();

            // Recruitment
            $table->integer('positions_count')->default(1);
            $table->integer('filled_count')->default(0);
            $table->date('needed_by')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['draft', 'open', 'in_progress', 'filled', 'cancelled'])->default('draft');

            // Trial period
            $table->integer('trial_period_days')->default(90);
            $table->json('trial_kpis')->nullable(); // KPIs for trial period

            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('motivation_scheme_id')->references('id')->on('motivation_schemes')->onDelete('set null');
            $table->index(['business_id', 'status']);
        });

        // Interview Protocol (Suhbat protokoli)
        Schema::create('interview_protocols', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('vacancy_card_id')->nullable();
            $table->uuid('candidate_id')->nullable(); // Link to candidate record

            $table->string('candidate_name');
            $table->string('candidate_phone')->nullable();
            $table->string('candidate_email')->nullable();

            $table->timestamp('interview_date');
            $table->uuid('interviewer_id');
            $table->enum('interview_type', ['screening', 'hr', 'technical', 'final', 'trial_task'])->default('hr');

            // "Biladi" (Knows) assessment
            $table->integer('knowledge_score')->default(0); // 1-5
            $table->json('knowledge_details')->nullable();
            // Example: [{"topic": "Sotish texnikasi", "score": 4, "notes": "..."}, ...]

            // "Uddalaydi" (Can do) assessment
            $table->integer('skills_score')->default(0); // 1-5
            $table->json('skills_details')->nullable();
            // Example: [{"skill": "Amaliy vazifa", "score": 3, "notes": "..."}, ...]

            // Employee type assessment
            $table->enum('assessed_employee_type', ['thinker', 'doer', 'mixed'])->nullable();
            $table->text('employee_type_notes')->nullable();

            // Candidate goals alignment
            $table->text('candidate_goals')->nullable(); // What candidate wants in 1 year
            $table->boolean('goals_aligned')->nullable(); // Aligned with business goals?

            // Overall assessment
            $table->integer('overall_score')->default(0); // 1-5
            $table->enum('recommendation', ['strong_hire', 'hire', 'maybe', 'no_hire'])->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('notes')->nullable();

            // Trial period suggestion
            $table->integer('suggested_trial_days')->nullable();
            $table->json('suggested_trial_kpis')->nullable();

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('vacancy_card_id')->references('id')->on('vacancy_cards')->onDelete('set null');
            $table->index(['business_id', 'interview_date']);
        });

        // Employee Classification (Xodim klassifikatsiyasi)
        Schema::create('employee_classifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');

            // Employee type from book
            $table->enum('employee_type', ['thinker', 'doer', 'mixed'])->default('doer');
            // Thinker: Independent, decision-maker, sees goal and finds way
            // Doer: Needs clear instructions, executes well

            // Is this person in the right position?
            $table->boolean('position_fit')->default(true);
            $table->text('position_fit_notes')->nullable();

            // "Star" employee flags (from book - dangerous dependencies)
            $table->boolean('is_star_employee')->default(false);
            $table->boolean('has_unique_knowledge')->default(false); // Only they know something
            $table->boolean('has_client_dependencies')->default(false); // Clients depend on them
            $table->boolean('blocks_new_employees')->default(false); // Fights with newcomers

            // Risk assessment
            $table->enum('departure_risk', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->decimal('replacement_difficulty', 3, 2)->default(1.0); // 1.0 = easy, 5.0 = very hard

            // Competency scores (1-5)
            $table->json('competency_scores')->nullable();
            // Example: {"sales": 4, "communication": 5, "analytics": 3, "leadership": 2}

            // Development
            $table->json('development_plan')->nullable();
            $table->text('career_path_notes')->nullable();
            $table->uuid('mentor_user_id')->nullable();

            $table->uuid('assessed_by')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'user_id']);
            $table->index(['business_id', 'employee_type']);
            $table->index(['business_id', 'is_star_employee']);
        });

        // Function Knowledge Registry (Who knows what - for star employee risk)
        Schema::create('function_knowledge_registry', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('function_name');
            $table->text('description')->nullable();
            $table->enum('criticality', ['low', 'medium', 'high', 'critical'])->default('medium');

            // Who knows this function
            $table->json('knowledgeable_users')->nullable(); // Array of user_ids

            // If only one person knows - this is a risk!
            $table->integer('knowledge_holders_count')->default(0);
            $table->boolean('is_at_risk')->default(false);

            // Documentation status
            $table->boolean('is_documented')->default(false);
            $table->string('documentation_link')->nullable();

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_at_risk']);
        });

        // ============================================================
        // PART 5: MARKETING MODULE
        // ============================================================

        // Marketing Channels
        if (!Schema::hasTable('marketing_channels')) {
            Schema::create('marketing_channels', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('business_id');
                $table->string('name');
                $table->string('code')->nullable(); // instagram, google_ads, telegram, etc.
                $table->enum('channel_type', ['paid', 'organic', 'referral', 'direct', 'other'])->default('paid');
                $table->boolean('is_active')->default(true);
                $table->string('color')->default('#3B82F6');
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
                $table->index(['business_id', 'is_active']);
            });
        }

        // Marketing Campaigns
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('channel_id')->nullable();

            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('campaign_type', ['awareness', 'leads', 'sales', 'retention', 'other'])->default('leads');

            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Budget
            $table->decimal('budget_planned', 15, 2)->default(0);
            $table->decimal('budget_spent', 15, 2)->default(0);

            // Results
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('leads_generated')->default(0);
            $table->integer('deals_closed')->default(0);
            $table->decimal('revenue_generated', 15, 2)->default(0);

            // Calculated metrics
            $table->decimal('cpl', 10, 2)->default(0); // Cost per lead
            $table->decimal('cpa', 10, 2)->default(0); // Cost per acquisition
            $table->decimal('roi', 10, 2)->default(0); // Return on investment %

            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->uuid('responsible_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('marketing_channels')->onDelete('set null');
            $table->index(['business_id', 'status']);
        });

        // Marketing Budget Tracking (Monthly)
        Schema::create('marketing_budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('channel_id')->nullable();

            $table->integer('year');
            $table->integer('month');

            $table->decimal('budget_limit', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('remaining', 15, 2)->default(0);

            $table->boolean('is_over_budget')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('marketing_channels')->onDelete('set null');
            $table->unique(['business_id', 'channel_id', 'year', 'month']);
        });

        // Marketing KPIs (linked to sales results per book)
        Schema::create('marketing_kpis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id')->nullable(); // Marketing team member

            $table->enum('period_type', ['monthly', 'quarterly']);
            $table->date('period_start');
            $table->date('period_end');

            // Marketing's own metrics
            $table->decimal('budget_limit', 15, 2)->default(0);
            $table->decimal('budget_used', 15, 2)->default(0);
            $table->boolean('budget_within_limit')->default(true);

            // Individual tasks completion
            $table->integer('tasks_total')->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->decimal('tasks_completion_percent', 5, 2)->default(0);

            // Linked to Sales (per book - marketing bonus depends on sales!)
            $table->uuid('linked_sales_target_id')->nullable();
            $table->decimal('sales_plan_completion', 5, 2)->default(0); // From sales target

            // Bonus calculation (70/30 rule from book)
            // 70% from sales plan completion
            // 30% from individual tasks
            $table->decimal('bonus_from_sales', 15, 2)->default(0);
            $table->decimal('bonus_from_tasks', 15, 2)->default(0);
            $table->decimal('total_bonus', 15, 2)->default(0);

            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'period_start']);
        });

        // Competitor Tracking
        Schema::create('competitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->enum('threat_level', ['low', 'medium', 'high'])->default('medium');
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // Competitor Activity Log
        Schema::create('competitor_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('competitor_id');

            $table->date('activity_date');
            $table->enum('activity_type', ['price_change', 'new_product', 'campaign', 'pr', 'partnership', 'other']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source_url')->nullable();

            $table->boolean('requires_response')->default(false);
            $table->text('recommended_action')->nullable();
            $table->enum('response_status', ['pending', 'planned', 'executed', 'ignored'])->nullable();

            $table->uuid('logged_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->index(['business_id', 'activity_date']);
        });

        // Customer Segments
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->text('description')->nullable();

            // Demographics
            $table->string('age_range')->nullable(); // e.g., "25-35"
            $table->string('income_level')->nullable(); // low, medium, high
            $table->string('location')->nullable();

            // Behavior
            $table->string('preferred_channel')->nullable();
            $table->string('purchase_motivation')->nullable(); // quality, price, status, convenience
            $table->decimal('average_order_value', 15, 2)->nullable();
            $table->integer('purchase_frequency')->nullable(); // times per year

            // Contribution
            $table->decimal('revenue_share_percent', 5, 2)->default(0);
            $table->integer('customer_count')->default(0);

            $table->string('color')->default('#3B82F6');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // Content Calendar
        Schema::create('content_calendar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('channel_id')->nullable();
            $table->uuid('campaign_id')->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('content_type', ['post', 'story', 'video', 'reels', 'blog', 'email', 'ad', 'other'])->default('post');

            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();

            $table->enum('status', ['idea', 'draft', 'review', 'approved', 'scheduled', 'published', 'cancelled'])->default('idea');

            $table->text('content_text')->nullable();
            $table->json('media_urls')->nullable();
            $table->string('post_url')->nullable(); // Link after publishing

            // Performance (after publishing)
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('clicks')->default(0);

            $table->uuid('assigned_to')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('marketing_channels')->onDelete('set null');
            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('set null');
            $table->index(['business_id', 'scheduled_date']);
            $table->index(['business_id', 'status']);
        });

        // ============================================================
        // PART 6: CROSS-DEPARTMENT INTEGRATION
        // ============================================================

        // Department Performance (Links all departments)
        Schema::create('department_performance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('department_id');

            $table->enum('period_type', ['monthly', 'quarterly']);
            $table->date('period_start');
            $table->date('period_end');

            // Department's own KPIs
            $table->decimal('own_kpi_score', 5, 4)->default(0);
            $table->json('own_kpi_details')->nullable();

            // Linked to company sales (from book - everyone depends on sales!)
            $table->decimal('sales_plan_completion', 5, 2)->default(0);

            // Bonus calculation based on book's model
            // Departments that don't directly affect sales still get bonus based on sales
            $table->decimal('own_kpi_weight', 5, 2)->default(70); // e.g., 70%
            $table->decimal('sales_link_weight', 5, 2)->default(30); // e.g., 30%

            $table->decimal('total_bonus_fund', 15, 2)->default(0);
            $table->decimal('earned_bonus', 15, 2)->default(0);

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'period_start']);
        });

        // Lead Flow Tracking (Marketing -> Sales)
        Schema::create('lead_flow_tracking', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('marketing_channel_id')->nullable();
            $table->uuid('marketing_campaign_id')->nullable();

            $table->date('tracking_date');

            // Leads generated by marketing
            $table->integer('leads_generated')->default(0);

            // Leads accepted by sales
            $table->integer('leads_accepted')->default(0);
            $table->integer('leads_rejected')->default(0);

            // Conversion by sales
            $table->integer('leads_converted')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);

            // Revenue from these leads
            $table->decimal('revenue_generated', 15, 2)->default(0);

            // Quality feedback from sales to marketing
            $table->decimal('lead_quality_score', 3, 2)->default(0); // 1-5
            $table->json('rejection_reasons_summary')->nullable();

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('marketing_channel_id')->references('id')->on('marketing_channels')->onDelete('set null');
            $table->index(['business_id', 'tracking_date']);
        });

        // Business Diagnostics (7 questions from book)
        Schema::create('business_diagnostics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('assessed_by');
            $table->date('assessment_date');

            // Question 1: Who is the owner in business?
            $table->enum('q1_owner_role', ['tired_employee', 'leader'])->nullable();

            // Question 2: Main competency?
            $table->enum('q2_main_competency', ['product', 'sales', 'management'])->nullable();

            // Question 3: Company type?
            $table->enum('q3_company_type', ['family_business', 'ptu', 'goal_instrument'])->nullable();
            // PTU = Professional Technical School (employees learning, not working)

            // Question 4: Management style?
            $table->enum('q4_management_style', ['plan_control', 'problem_solving'])->nullable();

            // Question 5: Who are TOPs?
            $table->enum('q5_tops_role', ['leaders', 'secretaries'])->nullable();

            // Question 6: Is there motivation?
            $table->enum('q6_motivation_exists', ['salary_only', 'salary_plus_bonus'])->nullable();

            // Question 7: Motivation type?
            $table->enum('q7_motivation_type', ['individual', 'team'])->nullable();

            // Overall score and recommendations
            $table->integer('evolution_level')->default(1); // 1-5 from book's pyramid
            $table->text('recommendations')->nullable();
            $table->json('action_plan')->nullable();

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'assessment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_diagnostics');
        Schema::dropIfExists('lead_flow_tracking');
        Schema::dropIfExists('department_performance');
        Schema::dropIfExists('content_calendar');
        Schema::dropIfExists('customer_segments');
        Schema::dropIfExists('competitor_activities');
        Schema::dropIfExists('competitors');
        Schema::dropIfExists('marketing_kpis');
        Schema::dropIfExists('marketing_budgets');
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('marketing_channels');
        Schema::dropIfExists('function_knowledge_registry');
        Schema::dropIfExists('employee_classifications');
        Schema::dropIfExists('interview_protocols');
        Schema::dropIfExists('vacancy_cards');
        Schema::dropIfExists('key_tasks');
        Schema::dropIfExists('key_task_maps');
        Schema::dropIfExists('motivation_calculations');
        Schema::dropIfExists('employee_motivations');
        Schema::dropIfExists('motivation_components');
        Schema::dropIfExists('motivation_schemes');
        Schema::dropIfExists('lost_deals');
        Schema::dropIfExists('rejection_reasons');
        Schema::dropIfExists('sales_funnel_stages');
        Schema::dropIfExists('receivables');
        Schema::dropIfExists('sales_activities');
        Schema::dropIfExists('sales_targets');
        Schema::dropIfExists('company_goals');
    }
};
