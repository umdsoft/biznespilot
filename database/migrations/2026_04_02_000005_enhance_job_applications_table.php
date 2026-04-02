<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('pipeline_stage', 30)->default('new')->after('status');
            $table->uuid('vacancy_card_id')->nullable()->after('job_posting_id');
            $table->boolean('added_to_talent_pool')->default(false)->after('notes');
            $table->dateTime('interview_scheduled_at')->nullable()->after('added_to_talent_pool');
            $table->uuid('current_interviewer_id')->nullable()->after('interview_scheduled_at');
            $table->integer('interview_round')->default(0)->after('current_interviewer_id');
            $table->json('scorecard')->nullable()->after('interview_round');

            $table->index('pipeline_stage');
            $table->index(['business_id', 'pipeline_stage']);
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex(['pipeline_stage']);
            $table->dropIndex(['business_id', 'pipeline_stage']);
            $table->dropColumn([
                'pipeline_stage', 'vacancy_card_id', 'added_to_talent_pool',
                'interview_scheduled_at', 'current_interviewer_id', 'interview_round', 'scorecard',
            ]);
        });
    }
};
