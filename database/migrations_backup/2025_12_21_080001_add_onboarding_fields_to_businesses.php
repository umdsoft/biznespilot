<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Industry relation
            $table->foreignId('industry_id')->nullable()->after('industry')->constrained('industries')->nullOnDelete();
            $table->foreignId('sub_industry_id')->nullable()->after('industry_id')->constrained('industries')->nullOnDelete();

            // Business type & model
            $table->enum('business_type', ['product', 'service', 'hybrid'])->nullable()->after('sub_industry_id');
            $table->enum('business_model', ['b2b', 'b2c', 'b2b2c'])->nullable()->after('business_type');
            $table->enum('business_stage', ['idea', 'startup', 'growth', 'mature'])->nullable()->after('business_model');

            // Team & dates
            $table->date('founding_date')->nullable()->after('business_stage');
            $table->integer('team_size')->nullable()->after('founding_date');

            // Maturity
            $table->integer('maturity_score')->default(0)->after('team_size');
            $table->enum('maturity_level', ['beginner', 'developing', 'established', 'advanced'])->default('beginner')->after('maturity_score');

            // Onboarding status
            $table->boolean('is_onboarding_completed')->default(false)->after('maturity_level');
            $table->timestamp('onboarding_completed_at')->nullable()->after('is_onboarding_completed');
            $table->timestamp('launched_at')->nullable()->after('onboarding_completed_at');

            // Indexes
            $table->index('industry_id');
            $table->index('business_type');
            $table->index('business_stage');
            $table->index('is_onboarding_completed');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['industry_id']);
            $table->dropForeign(['sub_industry_id']);
            $table->dropColumn([
                'industry_id',
                'sub_industry_id',
                'business_type',
                'business_model',
                'business_stage',
                'founding_date',
                'team_size',
                'maturity_score',
                'maturity_level',
                'is_onboarding_completed',
                'onboarding_completed_at',
                'launched_at',
            ]);
        });
    }
};
