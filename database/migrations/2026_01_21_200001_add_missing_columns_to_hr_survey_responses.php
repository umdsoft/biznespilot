<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_survey_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_survey_responses', 'time_spent_seconds')) {
                $table->integer('time_spent_seconds')->nullable()->after('answers');
            }
            if (!Schema::hasColumn('hr_survey_responses', 'is_complete')) {
                $table->boolean('is_complete')->default(false)->after('time_spent_seconds');
            }
            if (!Schema::hasColumn('hr_survey_responses', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('is_complete');
            }
            if (!Schema::hasColumn('hr_survey_responses', 'metadata')) {
                $table->json('metadata')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_survey_responses', function (Blueprint $table) {
            $table->dropColumn(['time_spent_seconds', 'is_complete', 'completed_at', 'metadata']);
        });
    }
};
