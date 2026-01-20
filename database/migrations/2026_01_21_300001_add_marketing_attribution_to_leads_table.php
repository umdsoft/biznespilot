<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marketing Attribution - Lead jadvaliga campaign, channel, UTM va qualification fieldlarini qo'shish
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Campaign va Channel attribution
            $table->uuid('campaign_id')
                ->nullable()
                ->after('source_id');

            $table->uuid('marketing_channel_id')
                ->nullable()
                ->after('campaign_id');

            // UTM parametrlar (attribution uchun)
            $table->string('utm_source', 100)->nullable()->after('marketing_channel_id');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 255)->nullable()->after('utm_medium');
            $table->string('utm_content', 255)->nullable()->after('utm_campaign');
            $table->string('utm_term', 255)->nullable()->after('utm_content');

            // Lead qualification
            $table->enum('qualification_status', ['new', 'mql', 'sql', 'disqualified'])
                ->default('new')
                ->after('status');
            $table->timestamp('qualified_at')->nullable()->after('qualification_status');
            $table->uuid('qualified_by')->nullable()->after('qualified_at');

            // First touch / Last touch timestamps
            $table->timestamp('first_touch_at')->nullable()->after('qualified_by');
            $table->string('first_touch_source', 100)->nullable()->after('first_touch_at');

            // Foreign keys
            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->nullOnDelete();

            $table->foreign('marketing_channel_id')
                ->references('id')
                ->on('marketing_channels')
                ->nullOnDelete();

            $table->foreign('qualified_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Indexlar
            $table->index(['business_id', 'campaign_id'], 'leads_business_campaign_idx');
            $table->index(['business_id', 'marketing_channel_id'], 'leads_business_channel_idx');
            $table->index(['business_id', 'qualification_status'], 'leads_business_qualification_idx');
            $table->index(['business_id', 'utm_source', 'utm_medium'], 'leads_business_utm_idx');
        });
    }

    /**
     * Rollback
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Foreign keys drop
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['marketing_channel_id']);
            $table->dropForeign(['qualified_by']);

            // Indexes drop
            $table->dropIndex('leads_business_campaign_idx');
            $table->dropIndex('leads_business_channel_idx');
            $table->dropIndex('leads_business_qualification_idx');
            $table->dropIndex('leads_business_utm_idx');

            // Columns drop
            $table->dropColumn([
                'campaign_id',
                'marketing_channel_id',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'utm_term',
                'qualification_status',
                'qualified_at',
                'qualified_by',
                'first_touch_at',
                'first_touch_source',
            ]);
        });
    }
};
