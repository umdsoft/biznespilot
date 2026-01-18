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
        Schema::table('meta_campaigns', function (Blueprint $table) {
            // Aggregated Insights (calculated from daily insights)
            if (! Schema::hasColumn('meta_campaigns', 'total_spend')) {
                $table->decimal('total_spend', 15, 2)->default(0)->after('budget_remaining');
            }
            if (! Schema::hasColumn('meta_campaigns', 'total_impressions')) {
                $table->bigInteger('total_impressions')->default(0)->after('total_spend');
            }
            if (! Schema::hasColumn('meta_campaigns', 'total_reach')) {
                $table->bigInteger('total_reach')->default(0)->after('total_impressions');
            }
            if (! Schema::hasColumn('meta_campaigns', 'total_clicks')) {
                $table->bigInteger('total_clicks')->default(0)->after('total_reach');
            }
            if (! Schema::hasColumn('meta_campaigns', 'total_conversions')) {
                $table->integer('total_conversions')->default(0)->after('total_clicks');
            }
            if (! Schema::hasColumn('meta_campaigns', 'avg_cpc')) {
                $table->decimal('avg_cpc', 10, 4)->default(0)->after('total_conversions');
            }
            if (! Schema::hasColumn('meta_campaigns', 'avg_cpm')) {
                $table->decimal('avg_cpm', 10, 4)->default(0)->after('avg_cpc');
            }
            if (! Schema::hasColumn('meta_campaigns', 'avg_ctr')) {
                $table->decimal('avg_ctr', 10, 4)->default(0)->after('avg_cpm');
            }

            // Sync Info
            if (! Schema::hasColumn('meta_campaigns', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('metadata');
            }
            if (! Schema::hasColumn('meta_campaigns', 'sync_status')) {
                $table->string('sync_status', 20)->default('pending')->after('last_synced_at');
            }

            // Created time from Meta API
            if (! Schema::hasColumn('meta_campaigns', 'created_time')) {
                $table->timestamp('created_time')->nullable()->after('stop_time');
            }

            // Indexes
            $table->index('total_spend');
            $table->index('effective_status');
            $table->index('objective');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'total_spend', 'total_impressions', 'total_reach', 'total_clicks',
                'total_conversions', 'avg_cpc', 'avg_cpm', 'avg_ctr',
                'last_synced_at', 'sync_status', 'created_time',
            ]);
        });
    }
};
