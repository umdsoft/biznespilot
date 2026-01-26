<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orders jadvaliga marketing attribution qo'shish.
 *
 * CRITICAL BUSINESS RULE: Har bir buyurtma marketing kanaliga bog'langan bo'lishi kerak.
 * "Black Box" - marketing ROI ni to'g'ri hisoblash uchun.
 *
 * Attribution zanjiri: Order -> Customer -> Lead
 */
return new class extends Migration
{
    public function up(): void
    {
        // === MARKETING ATTRIBUTION ===
        // Foreign keys (Customer -> Lead dan meros)
        if (!Schema::hasColumn('orders', 'campaign_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignUuid('campaign_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('campaigns')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('orders', 'marketing_channel_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignUuid('marketing_channel_id')
                    ->nullable()
                    ->after('campaign_id')
                    ->constrained('marketing_channels')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('orders', 'lead_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignUuid('lead_id')
                    ->nullable()
                    ->after('marketing_channel_id')
                    ->constrained('leads')
                    ->nullOnDelete()
                    ->comment('Original lead (Customer orqali)');
            });
        }

        // UTM Parameters (Lead dan meros)
        if (!Schema::hasColumn('orders', 'utm_source')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('utm_source', 100)->nullable()->after('lead_id');
            });
        }
        if (!Schema::hasColumn('orders', 'utm_medium')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('utm_medium', 100)->nullable()->after('utm_source');
            });
        }
        if (!Schema::hasColumn('orders', 'utm_campaign')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('utm_campaign', 255)->nullable()->after('utm_medium');
            });
        }
        if (!Schema::hasColumn('orders', 'utm_content')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('utm_content', 255)->nullable()->after('utm_campaign');
            });
        }
        if (!Schema::hasColumn('orders', 'utm_term')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('utm_term', 255)->nullable()->after('utm_content');
            });
        }

        // Attribution source type
        if (!Schema::hasColumn('orders', 'attribution_source_type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('attribution_source_type', 50)
                    ->nullable()
                    ->after('utm_term')
                    ->comment('digital, offline, organic, referral, direct');
            });
        }

        // Full attribution data (JSON)
        if (!Schema::hasColumn('orders', 'attribution_data')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->json('attribution_data')
                    ->nullable()
                    ->after('attribution_source_type')
                    ->comment('To\'liq attribution ma\'lumotlari');
            });
        }

        // Acquisition cost (Lead CAC dan)
        if (!Schema::hasColumn('orders', 'attributed_acquisition_cost')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('attributed_acquisition_cost', 15, 2)
                    ->default(0)
                    ->after('attribution_data')
                    ->comment('Bu buyurtmaga tegishli CAC');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['business_id', 'campaign_id', 'ordered_at']);
            $table->dropIndex(['business_id', 'marketing_channel_id', 'ordered_at']);
            $table->dropIndex(['business_id', 'utm_source', 'utm_medium']);

            // Drop foreign keys
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['marketing_channel_id']);
            $table->dropForeign(['lead_id']);

            // Drop columns
            $table->dropColumn([
                'campaign_id',
                'marketing_channel_id',
                'lead_id',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'utm_term',
                'attribution_source_type',
                'attribution_data',
                'attributed_acquisition_cost',
            ]);
        });
    }
};
