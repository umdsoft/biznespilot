<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_assessment_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('hr_survey_id');
            $table->uuid('job_application_id')->nullable();
            $table->uuid('talent_pool_candidate_id')->nullable();
            $table->string('candidate_email')->nullable();
            $table->string('candidate_name');
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending'); // pending, started, completed, expired
            $table->uuid('response_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('hr_survey_id')->references('id')->on('hr_surveys')->onDelete('cascade');
            $table->index(['business_id', 'status']);
        });

        // HR Surveys jadvaliga assessment ustunlari
        Schema::table('hr_surveys', function (Blueprint $table) {
            $table->boolean('is_candidate_assessment')->default(false)->after('settings');
            $table->string('assessment_category', 30)->nullable()->after('is_candidate_assessment');
        });
    }

    public function down(): void
    {
        Schema::table('hr_surveys', function (Blueprint $table) {
            $table->dropColumn(['is_candidate_assessment', 'assessment_category']);
        });
        Schema::dropIfExists('candidate_assessment_links');
    }
};
