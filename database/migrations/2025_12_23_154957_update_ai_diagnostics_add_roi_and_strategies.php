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
        Schema::table('ai_diagnostics', function (Blueprint $table) {
            // Add new columns for ROI calculations, cause-effect matrix, and quick strategies
            $table->json('roi_calculations')->nullable()->after('funnel_analysis');
            $table->json('cause_effect_matrix')->nullable()->after('roi_calculations');
            $table->json('quick_strategies')->nullable()->after('cause_effect_matrix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_diagnostics', function (Blueprint $table) {
            $table->dropColumn(['roi_calculations', 'cause_effect_matrix', 'quick_strategies']);
        });
    }
};
