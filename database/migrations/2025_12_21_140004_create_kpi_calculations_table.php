<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('diagnostic_id')->nullable()->constrained('ai_diagnostics')->onDelete('set null');

            $table->date('calculation_date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly'])->default('monthly');
            $table->date('period_start');
            $table->date('period_end');

            // Marketing KPIs
            $table->bigInteger('total_reach')->default(0);
            $table->bigInteger('total_impressions')->default(0);
            $table->bigInteger('total_engagement')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->decimal('follower_growth_rate', 5, 2)->default(0);
            $table->integer('content_posts_count')->default(0);
            $table->decimal('avg_engagement_per_post', 10, 2)->default(0);

            // Advertising KPIs
            $table->bigInteger('total_ad_spend')->default(0);
            $table->integer('total_clicks')->default(0);
            $table->bigInteger('total_impressions_ads')->default(0);
            $table->decimal('cpc', 15, 2)->default(0);
            $table->decimal('cpm', 15, 2)->default(0);
            $table->decimal('ctr', 5, 2)->default(0);
            $table->integer('leads_from_ads')->default(0);
            $table->decimal('cpl', 15, 2)->default(0);
            $table->decimal('roas', 5, 2)->default(0);

            // Sales KPIs
            $table->integer('total_leads')->default(0);
            $table->integer('qualified_leads')->default(0);
            $table->integer('new_customers')->default(0);
            $table->bigInteger('total_revenue')->default(0);
            $table->decimal('cac', 15, 2)->default(0);
            $table->decimal('clv', 15, 2)->default(0);
            $table->decimal('ltv_cac_ratio', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->bigInteger('avg_deal_size')->default(0);
            $table->integer('sales_cycle_days')->default(0);

            // Funnel KPIs
            $table->integer('funnel_awareness')->default(0);
            $table->integer('funnel_interest')->default(0);
            $table->integer('funnel_consideration')->default(0);
            $table->integer('funnel_intent')->default(0);
            $table->integer('funnel_purchase')->default(0);
            $table->decimal('funnel_conversion_rate', 5, 2)->default(0);

            // Retention KPIs
            $table->integer('active_customers')->default(0);
            $table->integer('churned_customers')->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);
            $table->decimal('repeat_purchase_rate', 5, 2)->default(0);
            $table->integer('nps_score')->nullable();

            // Benchmark comparison snapshot
            $table->json('benchmark_comparison')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'calculation_date']);
            $table->index(['business_id', 'period_type']);
            $table->unique(['business_id', 'calculation_date', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_calculations');
    }
};
