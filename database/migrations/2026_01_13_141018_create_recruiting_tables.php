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
        // Job Postings Table
        Schema::create('job_postings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('job_description_id')->nullable();
            $table->string('title');
            $table->string('department');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->default('full_time');
            $table->integer('openings')->default(1);
            $table->string('status')->default('open'); // open, closed, filled, cancelled
            $table->date('posted_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->uuid('posted_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('job_description_id')->references('id')->on('job_descriptions')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
        });

        // Job Applications Table
        Schema::create('job_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('job_posting_id');
            $table->string('candidate_name');
            $table->string('candidate_email');
            $table->string('candidate_phone');
            $table->text('resume_path')->nullable();
            $table->text('cover_letter')->nullable();
            $table->text('linkedin_url')->nullable();
            $table->text('portfolio_url')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->string('current_company')->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->string('status')->default('new'); // new, screening, interviewing, offer, hired, rejected
            $table->text('notes')->nullable();
            $table->integer('rating')->nullable(); // 1-5
            $table->uuid('assigned_to')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('job_posting_id')->references('id')->on('job_postings')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });

        // Interviews Table
        Schema::create('interviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('job_application_id');
            $table->string('interview_type'); // phone, video, in_person, technical
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->uuid('interviewer_id')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled, no_show
            $table->text('feedback')->nullable();
            $table->integer('rating')->nullable(); // 1-5
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('job_application_id')->references('id')->on('job_applications')->onDelete('cascade');
            $table->foreign('interviewer_id')->references('id')->on('users')->onDelete('set null');
        });

        // Candidate Evaluations Table
        Schema::create('candidate_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('job_application_id');
            $table->uuid('evaluator_id');
            $table->string('evaluation_type'); // technical, cultural_fit, communication, overall
            $table->integer('technical_skills')->nullable(); // 1-5
            $table->integer('communication_skills')->nullable(); // 1-5
            $table->integer('problem_solving')->nullable(); // 1-5
            $table->integer('cultural_fit')->nullable(); // 1-5
            $table->integer('overall_rating')->nullable(); // 1-5
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('comments')->nullable();
            $table->string('recommendation')->nullable(); // hire, maybe, reject
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('job_application_id')->references('id')->on('job_applications')->onDelete('cascade');
            $table->foreign('evaluator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_evaluations');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
    }
};
