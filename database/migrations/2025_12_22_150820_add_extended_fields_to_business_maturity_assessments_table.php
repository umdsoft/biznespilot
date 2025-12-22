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
        Schema::table('business_maturity_assessments', function (Blueprint $table) {
            // Infrastructure - boolean fields
            $table->boolean('has_website')->default(false)->after('main_challenges');
            $table->boolean('has_crm')->default(false)->after('has_website');
            $table->boolean('uses_analytics')->default(false)->after('has_crm');
            $table->boolean('has_automation')->default(false)->after('uses_analytics');
            $table->json('current_tools')->nullable()->after('has_automation');

            // Processes - boolean fields
            $table->boolean('has_documented_processes')->default(false)->after('current_tools');
            $table->boolean('has_sales_process')->default(false)->after('has_documented_processes');
            $table->boolean('has_support_process')->default(false)->after('has_sales_process');
            $table->boolean('has_marketing_process')->default(false)->after('has_support_process');

            // Marketing channels and settings
            $table->json('marketing_channels')->nullable()->after('has_marketing_process');
            $table->boolean('has_marketing_budget')->default(false)->after('marketing_channels');
            $table->boolean('tracks_marketing_metrics')->default(false)->after('has_marketing_budget');
            $table->boolean('has_dedicated_marketing')->default(false)->after('tracks_marketing_metrics');

            // Goals
            $table->json('primary_goals')->nullable()->after('has_dedicated_marketing');
            $table->string('growth_target', 255)->nullable()->after('primary_goals');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_maturity_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'has_website',
                'has_crm',
                'uses_analytics',
                'has_automation',
                'current_tools',
                'has_documented_processes',
                'has_sales_process',
                'has_support_process',
                'has_marketing_process',
                'marketing_channels',
                'has_marketing_budget',
                'tracks_marketing_metrics',
                'has_dedicated_marketing',
                'primary_goals',
                'growth_target',
            ]);
        });
    }
};
