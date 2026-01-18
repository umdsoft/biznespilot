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
        Schema::table('businesses', function (Blueprint $table) {
            // Add new fields for AI training data
            if (! Schema::hasColumn('businesses', 'category')) {
                $table->string('category', 100)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('businesses', 'region')) {
                $table->string('region', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('businesses', 'employee_count')) {
                $table->string('employee_count', 50)->nullable()->after('team_size');
            }
            if (! Schema::hasColumn('businesses', 'monthly_revenue')) {
                $table->string('monthly_revenue', 50)->nullable()->after('employee_count');
            }
            if (! Schema::hasColumn('businesses', 'target_audience')) {
                $table->text('target_audience')->nullable()->after('monthly_revenue');
            }
            if (! Schema::hasColumn('businesses', 'main_goals')) {
                $table->json('main_goals')->nullable()->after('target_audience');
            }
            if (! Schema::hasColumn('businesses', 'onboarding_status')) {
                $table->string('onboarding_status', 50)->default('pending')->after('is_onboarding_completed');
            }
            if (! Schema::hasColumn('businesses', 'onboarding_current_step')) {
                $table->string('onboarding_current_step', 50)->nullable()->after('onboarding_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'region',
                'employee_count',
                'monthly_revenue',
                'target_audience',
                'main_goals',
                'onboarding_status',
                'onboarding_current_step',
            ]);
        });
    }
};
