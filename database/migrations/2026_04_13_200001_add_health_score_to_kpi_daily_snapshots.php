<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('kpi_daily_snapshots', 'health_score')) return;

        Schema::table('kpi_daily_snapshots', function (Blueprint $table) {
            $table->integer('health_score')->nullable()->after('conversion_rate');
            $table->decimal('cac', 12, 2)->nullable()->after('health_score');
            $table->decimal('ad_roas', 8, 2)->nullable()->after('cac');
            $table->integer('leads_total')->default(0)->after('ad_roas');
            $table->decimal('revenue_total', 15, 2)->default(0)->after('leads_total');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_daily_snapshots', function (Blueprint $table) {
            $table->dropColumn(['health_score', 'cac', 'ad_roas', 'leads_total', 'revenue_total']);
        });
    }
};
