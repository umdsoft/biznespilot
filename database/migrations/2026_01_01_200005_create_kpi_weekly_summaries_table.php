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
        Schema::create('kpi_weekly_summaries', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->integer('year');
            $table->integer('week_number'); // ISO week 1-53
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_with_data')->default(0);

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
            $table->decimal('roi', 10, 2)->nullable();
            $table->decimal('roas', 10, 2)->nullable();

            // REJA BILAN TAQQOSLASH
            $table->integer('plan_leads')->nullable();
            $table->integer('plan_sales')->nullable();
            $table->decimal('plan_revenue', 15, 2)->nullable();
            $table->decimal('plan_spend', 15, 2)->nullable();
            $table->decimal('leads_achievement', 5, 2)->nullable();
            $table->decimal('sales_achievement', 5, 2)->nullable();
            $table->decimal('revenue_achievement', 5, 2)->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(['business_id', 'year', 'week_number']);

            // Indexes
            $table->index(['business_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_weekly_summaries');
    }
};
