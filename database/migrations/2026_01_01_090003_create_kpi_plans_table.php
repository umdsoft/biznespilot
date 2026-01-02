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
        Schema::create('kpi_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('working_days')->default(30);

            // User input (faqat shu 2 ta qo'lda kiritiladi)
            $table->integer('new_sales');
            $table->decimal('avg_check', 15, 2);

            // Calculated metrics (hammasi avtomatik hisoblanadi)
            $table->integer('repeat_sales')->default(0);
            $table->integer('total_customers')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('ad_costs', 15, 2)->default(0);
            $table->decimal('gross_margin', 15, 2)->default(0);
            $table->decimal('gross_margin_percent', 5, 2)->default(0);
            $table->decimal('roi', 10, 2)->default(0);
            $table->decimal('roas', 10, 2)->default(0);
            $table->decimal('cac', 15, 2)->default(0);
            $table->decimal('clv', 15, 2)->default(0);
            $table->decimal('ltv_cac_ratio', 10, 2)->default(0);
            $table->integer('total_leads')->default(0);
            $table->decimal('lead_cost', 15, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('ctr', 5, 2)->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);

            // Breakdown (kunlik va haftalik)
            $table->json('daily_breakdown')->nullable();
            $table->json('weekly_breakdown')->nullable();

            $table->string('calculation_method')->default('industry_benchmark');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();

            // Unique constraint per business per month
            $table->unique(['business_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_plans');
    }
};
