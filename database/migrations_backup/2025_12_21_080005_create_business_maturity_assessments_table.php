<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_maturity_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();

            // Infrastructure checkmarks
            $table->boolean('has_website')->default(false);
            $table->boolean('has_instagram')->default(false);
            $table->boolean('has_telegram')->default(false);
            $table->boolean('has_crm')->default(false);
            $table->boolean('has_paid_ads')->default(false);
            $table->boolean('has_email_marketing')->default(false);
            $table->boolean('has_analytics')->default(false);
            $table->boolean('has_defined_target_audience')->default(false);
            $table->boolean('has_documented_process')->default(false);

            // Revenue ranges
            $table->enum('monthly_revenue_range', [
                'none', 'under_10m', '10m_50m', '50m_200m', '200m_500m', '500m_1b', 'over_1b'
            ])->default('none');

            // Marketing budget ranges
            $table->enum('monthly_marketing_budget_range', [
                'none', 'under_1m', '1m_5m', '5m_20m', '20m_50m', 'over_50m'
            ])->default('none');

            // Team sizes
            $table->integer('team_marketing_size')->default(0);
            $table->integer('team_sales_size')->default(0);

            // Challenges & Goals
            $table->json('main_challenges')->nullable();
            $table->json('main_goals')->nullable();

            // Calculated maturity
            $table->integer('maturity_score')->default(0);
            $table->enum('maturity_level', ['beginner', 'developing', 'established', 'advanced'])->default('beginner');
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->index('maturity_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_maturity_assessments');
    }
};
