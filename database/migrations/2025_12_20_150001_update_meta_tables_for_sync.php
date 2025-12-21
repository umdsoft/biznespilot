<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update meta_ad_sets table - add ad_account_id and meta_campaign_id
        Schema::table('meta_ad_sets', function (Blueprint $table) {
            // Make campaign_id nullable first
            $table->foreignId('ad_account_id')->nullable()->after('id')->constrained('meta_ad_accounts')->cascadeOnDelete();
            $table->string('meta_campaign_id')->nullable()->after('campaign_id');
            $table->string('bid_strategy')->nullable()->after('bid_amount');
        });

        // Update meta_ads table - add more fields
        Schema::table('meta_ads', function (Blueprint $table) {
            $table->foreignId('ad_account_id')->nullable()->after('id')->constrained('meta_ad_accounts')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->after('ad_set_id')->constrained('meta_campaigns')->nullOnDelete();
            $table->string('meta_adset_id')->nullable()->after('campaign_id');
            $table->string('meta_campaign_id')->nullable()->after('meta_adset_id');
            $table->json('creative_data')->nullable()->after('creative_id');
        });

        // Add unique index for insights to prevent duplicates
        Schema::table('meta_insights', function (Blueprint $table) {
            // Drop existing index if it exists and create a new composite one
            $table->unique(
                ['ad_account_id', 'object_type', 'object_id', 'date', 'age_range', 'gender', 'publisher_platform', 'platform_position'],
                'meta_insights_unique_composite'
            );
        });
    }

    public function down(): void
    {
        Schema::table('meta_ad_sets', function (Blueprint $table) {
            $table->dropForeign(['ad_account_id']);
            $table->dropColumn(['ad_account_id', 'meta_campaign_id', 'bid_strategy']);
        });

        Schema::table('meta_ads', function (Blueprint $table) {
            $table->dropForeign(['ad_account_id']);
            $table->dropForeign(['campaign_id']);
            $table->dropColumn(['ad_account_id', 'campaign_id', 'meta_adset_id', 'meta_campaign_id', 'creative_data']);
        });

        Schema::table('meta_insights', function (Blueprint $table) {
            $table->dropUnique('meta_insights_unique_composite');
        });
    }
};
