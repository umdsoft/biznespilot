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
        Schema::table('weekly_analytics', function (Blueprint $table) {
            // Extended analytics stats
            $table->json('regional_stats')->nullable()->after('trend_stats');
            $table->json('qualification_stats')->nullable()->after('regional_stats');
            $table->json('call_stats')->nullable()->after('qualification_stats');
            $table->json('task_stats')->nullable()->after('call_stats');
            $table->json('pipeline_stats')->nullable()->after('task_stats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_analytics', function (Blueprint $table) {
            $table->dropColumn([
                'regional_stats',
                'qualification_stats',
                'call_stats',
                'task_stats',
                'pipeline_stats',
            ]);
        });
    }
};
