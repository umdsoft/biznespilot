<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marketing Attribution - Sales jadvaliga attribution ma'lumotlarini qo'shish
     * Note: lead_id, campaign_id, marketing_channel_id allaqachon mavjud
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Faqat mavjud bo'lmagan ustunlarni qo'shish
            if (!Schema::hasColumn('sales', 'attribution_data')) {
                $table->json('attribution_data')->nullable()->after('marketing_channel_id');
            }

            if (!Schema::hasColumn('sales', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('sale_date');
            }

            if (!Schema::hasColumn('sales', 'closed_by')) {
                $table->uuid('closed_by')->nullable()->after('closed_at');

                $table->foreign('closed_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            // Indexlar (faqat mavjud bo'lmaganlarni va column mavjud bo'lganda)
            $indexes = Schema::getIndexListing('sales');

            if (Schema::hasColumn('sales', 'lead_id') && !in_array('sales_business_lead_idx', $indexes)) {
                $table->index(['business_id', 'lead_id'], 'sales_business_lead_idx');
            }
            if (Schema::hasColumn('sales', 'campaign_id') && !in_array('sales_business_campaign_idx', $indexes)) {
                $table->index(['business_id', 'campaign_id'], 'sales_business_campaign_idx');
            }
            if (Schema::hasColumn('sales', 'marketing_channel_id') && !in_array('sales_business_channel_idx', $indexes)) {
                $table->index(['business_id', 'marketing_channel_id'], 'sales_business_channel_idx');
            }
            if (Schema::hasColumn('sales', 'closed_at') && !in_array('sales_business_closed_idx', $indexes)) {
                $table->index(['business_id', 'closed_at'], 'sales_business_closed_idx');
            }
        });
    }

    /**
     * Rollback
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Foreign key drop
            if (Schema::hasColumn('sales', 'closed_by')) {
                $table->dropForeign(['closed_by']);
            }

            // Indexes drop
            $indexes = Schema::getIndexListing('sales');
            if (in_array('sales_business_lead_idx', $indexes)) {
                $table->dropIndex('sales_business_lead_idx');
            }
            if (in_array('sales_business_campaign_idx', $indexes)) {
                $table->dropIndex('sales_business_campaign_idx');
            }
            if (in_array('sales_business_channel_idx', $indexes)) {
                $table->dropIndex('sales_business_channel_idx');
            }
            if (in_array('sales_business_closed_idx', $indexes)) {
                $table->dropIndex('sales_business_closed_idx');
            }

            // Faqat bu migration qo'shgan ustunlarni olib tashlash
            $columns = [];
            if (Schema::hasColumn('sales', 'attribution_data')) {
                $columns[] = 'attribution_data';
            }
            if (Schema::hasColumn('sales', 'closed_at')) {
                $columns[] = 'closed_at';
            }
            if (Schema::hasColumn('sales', 'closed_by')) {
                $columns[] = 'closed_by';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
