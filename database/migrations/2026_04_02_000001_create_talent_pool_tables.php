<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_pool_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('job_application_id')->nullable();
            $table->string('candidate_name');
            $table->string('candidate_email')->nullable();
            $table->string('candidate_phone', 50)->nullable();
            $table->string('resume_path')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->string('current_company')->nullable();
            $table->json('skills')->nullable();
            $table->json('tags')->nullable();
            $table->string('employee_type', 20)->nullable(); // thinker, doer, mixed
            $table->integer('rating')->nullable(); // 1-5
            $table->string('status', 20)->default('available'); // available, contacted, not_interested, hired, archived
            $table->string('source', 30)->nullable(); // application, referral, linkedin, manual
            $table->uuid('source_vacancy_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('assessment_summary')->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->string('preferred_position')->nullable();
            $table->string('preferred_department')->nullable();
            $table->uuid('added_by')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'rating']);
        });

        Schema::create('talent_pool_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('talent_pool_candidate_id');
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->text('content');
            $table->string('type', 20)->default('note'); // note, status_change, interview_feedback, contact_log
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('talent_pool_candidate_id')->references('id')->on('talent_pool_candidates')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['talent_pool_candidate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_pool_notes');
        Schema::dropIfExists('talent_pool_candidates');
    }
};
