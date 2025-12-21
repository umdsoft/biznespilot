<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_daily_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->date('snapshot_date');

            // Revenue KPIs
            $table->bigInteger('revenue_total')->default(0);
            $table->bigInteger('revenue_new')->default(0);
            $table->bigInteger('revenue_recurring')->default(0);
            $table->integer('orders_count')->default(0);
            $table->bigInteger('aov')->default(0); // Average order value

            // Lead KPIs
            $table->integer('leads_total')->default(0);
            $table->integer('leads_qualified')->default(0);
            $table->integer('leads_converted')->default(0);
            $table->integer('lead_response_time_avg')->default(0); // minutes

            // Marketing KPIs
            $table->integer('reach_total')->default(0);
            $table->integer('impressions_total')->default(0);
            $table->integer('engagement_total')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->integer('followers_total')->default(0);
            $table->integer('followers_change')->default(0);

            // Advertising KPIs
            $table->bigInteger('ad_spend')->default(0);
            $table->integer('ad_impressions')->default(0);
            $table->integer('ad_clicks')->default(0);
            $table->decimal('ad_ctr', 5, 2)->default(0);
            $table->bigInteger('ad_cpc')->default(0);
            $table->integer('ad_leads')->default(0);
            $table->bigInteger('ad_cpl')->default(0);
            $table->integer('ad_conversions')->default(0);
            $table->decimal('ad_roas', 5, 2)->default(0);

            // Sales KPIs
            $table->bigInteger('cac')->default(0);
            $table->bigInteger('clv')->default(0);
            $table->decimal('ltv_cac_ratio', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);

            // Content KPIs
            $table->integer('posts_count')->default(0);
            $table->decimal('posts_engagement_avg', 10, 2)->default(0);
            $table->string('best_post_id')->nullable();
            $table->integer('best_post_engagement')->default(0);

            // Funnel KPIs
            $table->integer('funnel_awareness')->default(0);
            $table->integer('funnel_interest')->default(0);
            $table->integer('funnel_consideration')->default(0);
            $table->integer('funnel_intent')->default(0);
            $table->integer('funnel_purchase')->default(0);

            // Health Score
            $table->integer('health_score')->default(0);
            $table->integer('marketing_score')->default(0);
            $table->integer('sales_score')->default(0);
            $table->integer('content_score')->default(0);

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_daily_snapshots');
    }
};
