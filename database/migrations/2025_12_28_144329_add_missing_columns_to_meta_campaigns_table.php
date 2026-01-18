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
            // Rename campaign_id to meta_campaign_id if exists
            if (Schema::hasColumn('meta_campaigns', 'campaign_id') && ! Schema::hasColumn('meta_campaigns', 'meta_campaign_id')) {
                $table->renameColumn('campaign_id', 'meta_campaign_id');
            }
        });

        Schema::table('meta_campaigns', function (Blueprint $table) {
            // Add missing columns
            if (! Schema::hasColumn('meta_campaigns', 'business_id')) {
                $table->uuid('business_id')->nullable()->after('ad_account_id');
            }
            if (! Schema::hasColumn('meta_campaigns', 'effective_status')) {
                $table->string('effective_status')->nullable()->after('status');
            }
            if (! Schema::hasColumn('meta_campaigns', 'budget_remaining')) {
                $table->decimal('budget_remaining', 15, 2)->nullable()->after('lifetime_budget');
            }
            if (! Schema::hasColumn('meta_campaigns', 'metadata')) {
                $table->json('metadata')->nullable()->after('stop_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_campaigns', function (Blueprint $table) {
            $table->dropColumn(['business_id', 'effective_status', 'budget_remaining', 'metadata']);
        });
    }
};
