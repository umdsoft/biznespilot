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
        Schema::create('kpi_monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unsignedBigInteger('kpi_plan_id')->nullable();
            $table->foreign('kpi_plan_id')->references('id')->on('kpi_plans')->onDelete('set null');
            $table->integer('year');
            $table->integer('month');
            $table->integer('days_with_data')->default(0);
            $table->integer('total_days')->default(30);

            // LIDLAR
            $table->integer('leads_total')->default(0);
            $table->integer('leads_digital')->default(0);
            $table->integer('leads_offline')->default(0);
            $table->integer('leads_referral')->default(0);
            $table->integer('leads_organic')->default(0);

            // XARAJATLAR
            $table->decimal('spend_total', 15, 2)->default(0);
            $table->decimal('spend_digital', 15, 2)->default(0);
            $table->decimal('spend_offline', 15, 2)->default(0);

            // SOTUVLAR
            $table->integer('sales_total')->default(0);
            $table->integer('sales_new')->default(0);
            $table->integer('sales_repeat')->default(0);

            // DAROMAD
            $table->decimal('revenue_total', 15, 2)->default(0);
            $table->decimal('revenue_new', 15, 2)->default(0);
            $table->decimal('revenue_repeat', 15, 2)->default(0);

            // HISOBLANGAN METRIKALAR
            $table->decimal('avg_check', 15, 2)->nullable();
            $table->decimal('conversion_rate', 5, 2)->nullable();
            $table->decimal('cpl', 15, 2)->nullable();
            $table->decimal('cac', 15, 2)->nullable();
            $table->decimal('clv', 15, 2)->nullable();
            $table->decimal('ltv_cac_ratio', 10, 2)->nullable();
            $table->decimal('roi', 10, 2)->nullable();
            $table->decimal('roas', 10, 2)->nullable();
            $table->decimal('gross_margin', 15, 2)->nullable();
            $table->decimal('gross_margin_percent', 5, 2)->nullable();

            // REJA BILAN TAQQOSLASH
            $table->integer('plan_leads')->nullable();
            $table->integer('plan_sales')->nullable();
            $table->decimal('plan_revenue', 15, 2)->nullable();
            $table->decimal('plan_spend', 15, 2)->nullable();
            $table->decimal('leads_achievement', 5, 2)->nullable();
            $table->decimal('sales_achievement', 5, 2)->nullable();
            $table->decimal('revenue_achievement', 5, 2)->nullable();
            $table->decimal('spend_achievement', 5, 2)->nullable();

            // STATUS
            $table->enum('status', ['in_progress', 'completed', 'verified'])->default('in_progress');
            $table->uuid('verified_by')->nullable();
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(['business_id', 'year', 'month']);

            // Indexes
            $table->index(['business_id', 'year']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_monthly_summaries');
    }
};
