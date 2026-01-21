<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR Advanced Tables Migration
 *
 * Bu migratsiya professional HR tizimi uchun kerakli jadvallarni yaratadi:
 * - HR Alerts (ogohlantirishlar)
 * - Employee Engagements (jalb etish ko'rsatkichlari)
 * - Flight Risks (ketish xavfi)
 * - Turnover Records (aylanma statistikasi)
 * - Employee Onboarding Plans (onboarding rejalar)
 * - Employee Onboarding Tasks (onboarding vazifalar)
 * - Offboarding Checklists (offboarding checklistlar)
 */
return new class extends Migration
{
    public function up(): void
    {
        // HR Alerts Table - Ogohlantirishlar
        Schema::create('hr_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id')->nullable(); // Kimga tegishli (null = barcha HR)
            $table->uuid('related_user_id')->nullable(); // Kim haqida
            $table->string('type'); // flight_risk_high, engagement_dropped, leave_rejected, etc.
            $table->string('title');
            $table->text('message');
            $table->string('priority')->default('medium'); // urgent, high, medium, low
            $table->string('status')->default('new'); // new, seen, acknowledged, resolved
            $table->boolean('is_celebration')->default(false); // Ijobiy xabarmi
            $table->json('data')->nullable(); // Qo'shimcha ma'lumotlar
            $table->json('recommended_actions')->nullable(); // Tavsiya qilingan harakatlar
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('related_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'priority']);
            $table->index(['business_id', 'user_id', 'status']);
        });

        // Employee Engagements Table - Jalb etish ko'rsatkichlari (Gallup Q12 asosida)
        Schema::create('employee_engagements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->string('period'); // 2024-01, 2024-Q1, etc.

            // Gallup Q12 ball komponentlari (har biri 0-100)
            $table->decimal('work_satisfaction', 5, 2)->default(0); // Ish qoniqishi
            $table->decimal('team_collaboration', 5, 2)->default(0); // Jamoa hamkorligi
            $table->decimal('growth_opportunities', 5, 2)->default(0); // O'sish imkoniyatlari
            $table->decimal('recognition_frequency', 5, 2)->default(0); // Tan olish chastotasi
            $table->decimal('manager_support', 5, 2)->default(0); // Manager qo'llab-quvvatlashi
            $table->decimal('work_life_balance', 5, 2)->default(0); // Ish-hayot balansi
            $table->decimal('purpose_clarity', 5, 2)->default(0); // Maqsad aniqligi
            $table->decimal('resources_adequacy', 5, 2)->default(0); // Resurslar yetarliligi

            // Umumiy ball va level
            $table->decimal('overall_score', 5, 2)->default(0); // Umumiy engagement ball
            $table->string('engagement_level')->default('neutral'); // highly_engaged, engaged, neutral, disengaged, highly_disengaged

            // Trend ma'lumotlari
            $table->decimal('previous_score', 5, 2)->nullable();
            $table->decimal('score_change', 5, 2)->default(0);
            $table->string('trend')->default('stable'); // improving, stable, declining

            // Tarix va tahlil
            $table->json('score_history')->nullable(); // [{date, score, source}]
            $table->json('q12_responses')->nullable(); // Oxirgi Q12 javoblari
            $table->timestamp('last_survey_at')->nullable();
            $table->timestamp('last_boosted_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['business_id', 'user_id', 'period']);
            $table->index(['business_id', 'engagement_level']);
            $table->index(['business_id', 'overall_score']);
        });

        // Flight Risks Table - Ketish xavfi
        Schema::create('flight_risks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');

            // Risk ball va daraja
            $table->decimal('risk_score', 5, 2)->default(0); // 0-100
            $table->string('risk_level')->default('low'); // low, moderate, high, critical

            // Risk faktorlari (har biri 0-100)
            $table->json('risk_factors')->nullable(); // [{factor, score, weight}]
            $table->decimal('engagement_factor', 5, 2)->default(0);
            $table->decimal('tenure_factor', 5, 2)->default(0);
            $table->decimal('compensation_factor', 5, 2)->default(0);
            $table->decimal('growth_factor', 5, 2)->default(0);
            $table->decimal('workload_factor', 5, 2)->default(0);
            $table->decimal('recognition_factor', 5, 2)->default(0);

            // Harakatlar
            $table->json('recommended_actions')->nullable();
            $table->json('actions_taken')->nullable();
            $table->boolean('stay_interview_scheduled')->default(false);
            $table->date('stay_interview_date')->nullable();
            $table->text('stay_interview_notes')->nullable();

            // Tarix
            $table->string('previous_level')->nullable();
            $table->timestamp('level_changed_at')->nullable();
            $table->json('level_history')->nullable(); // [{date, level, score, reason}]
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['business_id', 'user_id']);
            $table->index(['business_id', 'risk_level']);
            $table->index(['business_id', 'risk_score']);
        });

        // Turnover Records Table - Aylanma statistikasi
        Schema::create('turnover_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');

            // Asosiy ma'lumotlar
            $table->string('termination_type'); // voluntary, involuntary, retirement, contract_end
            $table->string('termination_reason')->nullable(); // better_opportunity, relocation, compensation, etc.
            $table->text('termination_reason_details')->nullable();
            $table->date('hire_date');
            $table->date('termination_date');
            $table->integer('tenure_months')->default(0);

            // Lavozim ma'lumotlari
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->uuid('manager_id')->nullable();

            // Exit interview
            $table->boolean('exit_interview_completed')->default(false);
            $table->json('exit_interview_data')->nullable();
            $table->decimal('exit_satisfaction_score', 5, 2)->nullable(); // Ishdan qoniqish balli
            $table->boolean('would_recommend_employer')->nullable();
            $table->boolean('would_return')->nullable();

            // Tahlil uchun
            $table->boolean('was_high_performer')->default(false);
            $table->boolean('was_flight_risk')->default(false);
            $table->decimal('last_engagement_score', 5, 2)->nullable();
            $table->string('last_flight_risk_level')->nullable();
            $table->boolean('is_regrettable')->default(false); // Afsuslanarli yo'qotishmi

            // Almashtirish
            $table->boolean('replacement_needed')->default(true);
            $table->boolean('replacement_hired')->default(false);
            $table->uuid('replacement_user_id')->nullable();
            $table->integer('days_to_fill')->nullable();

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('replacement_user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['business_id', 'termination_date']);
            $table->index(['business_id', 'termination_type']);
            $table->index(['business_id', 'is_regrettable']);
        });

        // Employee Onboarding Plans Table - 30-60-90 kun rejalar
        Schema::create('employee_onboarding_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id'); // Yangi hodim
            $table->uuid('mentor_id')->nullable(); // Mentor
            $table->uuid('manager_id')->nullable(); // Manager
            $table->uuid('hr_contact_id')->nullable(); // HR mas'ul

            // Reja parametrlari
            $table->date('start_date');
            $table->date('expected_end_date'); // 90 kun keyin
            $table->string('status')->default('active'); // active, completed, extended, cancelled
            $table->integer('progress')->default(0); // 0-100

            // Bosqich ballari
            $table->integer('day_30_score')->nullable(); // 0-100
            $table->integer('day_60_score')->nullable();
            $table->integer('day_90_score')->nullable();
            $table->boolean('day_30_completed')->default(false);
            $table->boolean('day_60_completed')->default(false);
            $table->boolean('day_90_completed')->default(false);
            $table->timestamp('day_30_completed_at')->nullable();
            $table->timestamp('day_60_completed_at')->nullable();
            $table->timestamp('day_90_completed_at')->nullable();

            // Feedback
            $table->json('mentor_feedback')->nullable();
            $table->json('manager_feedback')->nullable();
            $table->json('employee_feedback')->nullable();
            $table->decimal('overall_satisfaction', 5, 2)->nullable();

            // Yakuniy natija
            $table->boolean('probation_passed')->nullable();
            $table->text('final_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('mentor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('hr_contact_id')->references('id')->on('users')->onDelete('set null');

            $table->unique(['business_id', 'user_id']);
            $table->index(['business_id', 'status']);
        });

        // Employee Onboarding Tasks Table - Onboarding vazifalari
        Schema::create('employee_onboarding_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->uuid('business_id');

            // Vazifa ma'lumotlari
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category'); // documentation, training, meeting, equipment, access, culture
            $table->string('phase'); // day_1, week_1, day_30, day_60, day_90
            $table->integer('day_number')->default(1); // Qaysi kunda bajarilishi kerak
            $table->integer('order')->default(0); // Tartib

            // Mas'ul
            $table->uuid('assigned_to_id')->nullable(); // Kim bajaradi (hodim, mentor, HR, IT)
            $table->string('assigned_role')->nullable(); // employee, mentor, manager, hr, it

            // Holat
            $table->string('status')->default('pending'); // pending, in_progress, completed, skipped
            $table->boolean('is_required')->default(true);
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('completed_by')->nullable();
            $table->text('completion_notes')->nullable();

            // Resurslar
            $table->json('resources')->nullable(); // [{type, url, title}]
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('employee_onboarding_plans')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['plan_id', 'phase']);
            $table->index(['plan_id', 'status']);
        });

        // Offboarding Checklists Table - Offboarding checklistlar
        Schema::create('offboarding_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id'); // Ketayotgan hodim
            $table->uuid('hr_contact_id')->nullable();

            // Asosiy ma'lumotlar
            $table->string('termination_reason')->nullable();
            $table->date('last_working_day');
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->decimal('progress', 5, 2)->default(0);

            // Checklist elementlari
            $table->json('checklist_items')->nullable(); // [{category, title, completed, completed_at}]

            // Asosiy bosqichlar
            $table->boolean('knowledge_transfer_completed')->default(false);
            $table->boolean('assets_returned')->default(false);
            $table->boolean('access_revoked')->default(false);
            $table->boolean('final_payment_processed')->default(false);

            // Exit interview
            $table->timestamp('exit_interview_date')->nullable();
            $table->json('exit_interview_data')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('hr_contact_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'last_working_day']);
        });

        // HR Surveys Table - So'rovnomalar (Q12, Pulse, eNPS)
        Schema::create('hr_surveys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('type'); // q12, pulse, enps, exit, custom
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('questions'); // [{id, text, type, options}]
            $table->string('status')->default('draft'); // draft, active, closed
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->integer('response_count')->default(0);
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['business_id', 'type']);
            $table->index(['business_id', 'status']);
        });

        // HR Survey Responses Table - So'rovnoma javoblari
        Schema::create('hr_survey_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('survey_id');
            $table->uuid('business_id');
            $table->uuid('user_id')->nullable(); // null = anonim
            $table->json('answers'); // [{question_id, answer, score}]
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->integer('enps_score')->nullable(); // -100 to 100 for eNPS
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('survey_id')->references('id')->on('hr_surveys')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['survey_id', 'user_id']);
        });

        // HR Recognitions Table - Tan olishlar (Kudos, Awards)
        Schema::create('hr_recognitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('from_user_id');
            $table->uuid('to_user_id');
            $table->string('type'); // kudos, spotlight, award, badge
            $table->string('category')->nullable(); // teamwork, innovation, customer_service, leadership
            $table->string('title');
            $table->text('message')->nullable();
            $table->integer('points')->default(0);
            $table->boolean('is_public')->default(true);
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['business_id', 'to_user_id']);
            $table->index(['business_id', 'type']);
        });

        // Employee Health Scores Table - Xodim sog'ligi yig'ma ball
        Schema::create('employee_health_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->date('date');

            // Komponent ballari (har biri 0-100)
            $table->decimal('engagement_score', 5, 2)->default(0);
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->decimal('attendance_score', 5, 2)->default(0);
            $table->decimal('development_score', 5, 2)->default(0);
            $table->decimal('satisfaction_score', 5, 2)->default(0);

            // Umumiy ball
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->string('health_status')->default('good'); // excellent, good, average, poor, critical

            // Tahlil
            $table->json('score_breakdown')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['business_id', 'user_id', 'date']);
            $table->index(['business_id', 'health_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_health_scores');
        Schema::dropIfExists('hr_recognitions');
        Schema::dropIfExists('hr_survey_responses');
        Schema::dropIfExists('hr_surveys');
        Schema::dropIfExists('offboarding_checklists');
        Schema::dropIfExists('employee_onboarding_tasks');
        Schema::dropIfExists('employee_onboarding_plans');
        Schema::dropIfExists('turnover_records');
        Schema::dropIfExists('flight_risks');
        Schema::dropIfExists('employee_engagements');
        Schema::dropIfExists('hr_alerts');
    }
};
