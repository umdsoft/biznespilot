<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marketing KPI Snapshots - kunlik/haftalik/oylik KPI hisoblari
     */
    public function up(): void
    {
        Schema::create('marketing_kpi_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');

            $table->date('date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);

            // Optional filters (null = overall)
            $table->uuid('channel_id')->nullable();
            $table->uuid('campaign_id')->nullable();

            // Lead metrics
            $table->unsignedInteger('leads_count')->default(0);
            $table->unsignedInteger('mql_count')->default(0);
            $table->unsignedInteger('sql_count')->default(0);
            $table->unsignedInteger('won_count')->default(0);
            $table->unsignedInteger('lost_count')->default(0);

            // Financial metrics
            $table->decimal('total_spend', 15, 2)->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);

            // Calculated KPIs
            $table->decimal('cpl', 12, 2)->default(0);      // Cost Per Lead
            $table->decimal('cpmql', 12, 2)->default(0);    // Cost Per MQL
            $table->decimal('cpsql', 12, 2)->default(0);    // Cost Per SQL
            $table->decimal('cac', 12, 2)->default(0);      // Customer Acquisition Cost
            $table->decimal('roas', 10, 4)->default(0);     // Return On Ad Spend
            $table->decimal('roi', 10, 4)->default(0);      // Return On Investment (%)

            // Conversion rates (%)
            $table->decimal('lead_to_mql_rate', 5, 2)->default(0);
            $table->decimal('mql_to_sql_rate', 5, 2)->default(0);
            $table->decimal('sql_to_won_rate', 5, 2)->default(0);
            $table->decimal('overall_conversion_rate', 5, 2)->default(0);

            $table->timestamps();

            // Foreign keys
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->cascadeOnDelete();

            $table->foreign('channel_id')
                ->references('id')
                ->on('marketing_channels')
                ->cascadeOnDelete();

            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->cascadeOnDelete();

            // Unique constraint - bir kun/period uchun bitta snapshot
            $table->unique(
                ['business_id', 'date', 'period_type', 'channel_id', 'campaign_id'],
                'marketing_kpi_unique'
            );

            // Indexes
            $table->index(['business_id', 'date', 'period_type'], 'marketing_kpi_date_period_idx');
            $table->index(['business_id', 'channel_id', 'date'], 'marketing_kpi_channel_date_idx');
            $table->index(['business_id', 'campaign_id', 'date'], 'marketing_kpi_campaign_date_idx');
        });
    }

    /**
     * Rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_kpi_snapshots');
    }
};
