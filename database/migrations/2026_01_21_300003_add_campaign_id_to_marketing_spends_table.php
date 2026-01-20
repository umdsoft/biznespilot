<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marketing Spend jadvaliga campaign_id qo'shish (ROI hisoblash uchun)
     */
    public function up(): void
    {
        Schema::table('marketing_spends', function (Blueprint $table) {
            $table->uuid('campaign_id')
                ->nullable()
                ->after('channel_id');

            // Foreign key
            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->nullOnDelete();

            // Index
            $table->index(['business_id', 'campaign_id'], 'marketing_spends_business_campaign_idx');
            $table->index(['business_id', 'channel_id', 'campaign_id'], 'marketing_spends_channel_campaign_idx');
        });
    }

    /**
     * Rollback
     */
    public function down(): void
    {
        Schema::table('marketing_spends', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropIndex('marketing_spends_business_campaign_idx');
            $table->dropIndex('marketing_spends_channel_campaign_idx');
            $table->dropColumn('campaign_id');
        });
    }
};
