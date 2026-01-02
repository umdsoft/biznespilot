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
        Schema::table('kpi_plans', function (Blueprint $table) {
            // Source breakdown for leads
            $table->integer('leads_digital')->default(0)->after('total_leads');
            $table->integer('leads_offline')->default(0)->after('leads_digital');
            $table->integer('leads_referral')->default(0)->after('leads_offline');

            // Source breakdown for ad costs
            $table->decimal('ad_spend_digital', 15, 2)->default(0)->after('ad_costs');
            $table->decimal('ad_spend_offline', 15, 2)->default(0)->after('ad_spend_digital');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_plans', function (Blueprint $table) {
            $table->dropColumn([
                'leads_digital',
                'leads_offline',
                'leads_referral',
                'ad_spend_digital',
                'ad_spend_offline',
            ]);
        });
    }
};
