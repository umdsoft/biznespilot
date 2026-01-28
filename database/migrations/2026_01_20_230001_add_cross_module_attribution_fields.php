<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-Module Attribution Migration
 *
 * Bu migration Marketing, Sotuv va Moliya modullarini
 * avtomatik integratsiya qilish uchun kerakli fieldlarni qo'shadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Lead uchun acquisition cost
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'acquisition_cost')) {
                $table->decimal('acquisition_cost', 12, 2)->nullable()->after('estimated_value')
                    ->comment('Bu lid uchun sarflangan marketing xarajati');
            }
            if (!Schema::hasColumn('leads', 'acquisition_source_type')) {
                $table->string('acquisition_source_type')->nullable()->after('acquisition_cost')
                    ->comment('digital, offline, referral, organic');
            }
        });

        // 2. Sale uchun attribution cost
        Schema::table('sales', function (Blueprint $table) {
            // First add acquisition_cost if it doesn't exist
            if (!Schema::hasColumn('sales', 'acquisition_cost')) {
                $table->decimal('acquisition_cost', 12, 2)->nullable()->after('profit')
                    ->comment('Bu sotuv uchun sarflangan marketing xarajati');
            }
            if (!Schema::hasColumn('sales', 'attribution_source_type')) {
                $table->string('attribution_source_type')->nullable()->after('acquisition_cost')
                    ->comment('digital, offline, referral, organic');
            }
            if (!Schema::hasColumn('sales', 'attributed_spend')) {
                $table->decimal('attributed_spend', 12, 2)->nullable()->after('attribution_source_type')
                    ->comment('Marketing spend attributed to this sale');
            }
        });

        // 3. Customer uchun acquisition va churn tracking
        Schema::table('customers', function (Blueprint $table) {
            // Acquisition source tracking
            if (!Schema::hasColumn('customers', 'first_acquisition_source')) {
                $table->string('first_acquisition_source')->nullable()->after('lead_id')
                    ->comment('Birinchi kanaldan kelgan manba nomi');
            }
            if (!Schema::hasColumn('customers', 'first_acquisition_source_type')) {
                $table->string('first_acquisition_source_type')->nullable()->after('first_acquisition_source')
                    ->comment('digital, offline, referral, organic');
            }
            if (!Schema::hasColumn('customers', 'first_acquisition_channel_id')) {
                $table->foreignUuid('first_acquisition_channel_id')->nullable()->after('first_acquisition_source_type')
                    ->constrained('marketing_channels')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'first_campaign_id')) {
                $table->foreignUuid('first_campaign_id')->nullable()->after('first_acquisition_channel_id')
                    ->constrained('campaigns')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'total_acquisition_cost')) {
                $table->decimal('total_acquisition_cost', 12, 2)->default(0)->after('first_campaign_id')
                    ->comment('Umumiy sarflangan marketing xarajati');
            }

            // Churn tracking
            if (!Schema::hasColumn('customers', 'churn_risk_score')) {
                $table->decimal('churn_risk_score', 5, 2)->nullable()->after('total_acquisition_cost')
                    ->comment('0-100 oraligida churn xavfi');
            }
            if (!Schema::hasColumn('customers', 'churn_risk_level')) {
                $table->string('churn_risk_level')->nullable()->after('churn_risk_score')
                    ->comment('low, medium, high, critical');
            }
            if (!Schema::hasColumn('customers', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('churn_risk_level')
                    ->comment('Oxirgi faollik vaqti');
            }
            if (!Schema::hasColumn('customers', 'churned_at')) {
                $table->timestamp('churned_at')->nullable()->after('last_activity_at')
                    ->comment('Churn bolgan sana');
            }
            if (!Schema::hasColumn('customers', 'churn_reason')) {
                $table->string('churn_reason')->nullable()->after('churned_at')
                    ->comment('Churn sababi');
            }

            // CLV tracking
            if (!Schema::hasColumn('customers', 'lifetime_value')) {
                $table->decimal('lifetime_value', 12, 2)->default(0)->after('total_spent')
                    ->comment('Predicted lifetime value');
            }
            if (!Schema::hasColumn('customers', 'days_since_last_purchase')) {
                $table->integer('days_since_last_purchase')->nullable()->after('lifetime_value');
            }
            if (!Schema::hasColumn('customers', 'purchase_frequency_days')) {
                $table->integer('purchase_frequency_days')->nullable()->after('days_since_last_purchase')
                    ->comment('Ortacha xarid orasidagi kun');
            }
        });

        // 4. KpiDailyEntry uchun revenue by source
        Schema::table('kpi_daily_entries', function (Blueprint $table) {
            // Revenue by source type
            if (!Schema::hasColumn('kpi_daily_entries', 'revenue_digital')) {
                $table->decimal('revenue_digital', 12, 2)->default(0)->after('revenue_repeat')
                    ->comment('Digital marketing orqali kelgan daromad');
            }
            if (!Schema::hasColumn('kpi_daily_entries', 'revenue_offline')) {
                $table->decimal('revenue_offline', 12, 2)->default(0)->after('revenue_digital')
                    ->comment('Offline marketing orqali kelgan daromad');
            }
            if (!Schema::hasColumn('kpi_daily_entries', 'revenue_referral')) {
                $table->decimal('revenue_referral', 12, 2)->default(0)->after('revenue_offline')
                    ->comment('Referral orqali kelgan daromad');
            }
            if (!Schema::hasColumn('kpi_daily_entries', 'revenue_organic')) {
                $table->decimal('revenue_organic', 12, 2)->default(0)->after('revenue_referral')
                    ->comment('Organic orqali kelgan daromad');
            }

            // ROAS tracking
            if (!Schema::hasColumn('kpi_daily_entries', 'roas_digital')) {
                $table->decimal('roas_digital', 8, 4)->nullable()->after('cac')
                    ->comment('Digital marketing ROAS');
            }
            if (!Schema::hasColumn('kpi_daily_entries', 'roas_offline')) {
                $table->decimal('roas_offline', 8, 4)->nullable()->after('roas_digital')
                    ->comment('Offline marketing ROAS');
            }
            if (!Schema::hasColumn('kpi_daily_entries', 'roas_total')) {
                $table->decimal('roas_total', 8, 4)->nullable()->after('roas_offline')
                    ->comment('Umumiy ROAS');
            }

            // ROI tracking
            if (!Schema::hasColumn('kpi_daily_entries', 'roi_total')) {
                $table->decimal('roi_total', 8, 4)->nullable()->after('roas_total')
                    ->comment('Umumiy ROI (profit/spend)');
            }

            // Profit tracking
            if (!Schema::hasColumn('kpi_daily_entries', 'profit_total')) {
                $table->decimal('profit_total', 12, 2)->default(0)->after('roi_total')
                    ->comment('Umumiy foyda');
            }
        });

        // 5. Meta/Google conversion reconciliation table
        if (!Schema::hasTable('conversion_reconciliations')) {
        Schema::create('conversion_reconciliations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->date('reconciliation_date');

            // Platform data
            $table->string('platform')->comment('meta, google, tiktok');
            $table->string('platform_campaign_id')->nullable();
            $table->string('platform_adset_id')->nullable();

            // Platform reported
            $table->integer('platform_conversions')->default(0)
                ->comment('Platform tomonidan report qilingan konversiyalar');
            $table->decimal('platform_conversion_value', 12, 2)->default(0)
                ->comment('Platform tomonidan report qilingan qiymat');

            // Actual data
            $table->integer('actual_conversions')->default(0)
                ->comment('Haqiqiy sotuv bazasidagi konversiyalar');
            $table->decimal('actual_conversion_value', 12, 2)->default(0)
                ->comment('Haqiqiy sotuv qiymati');

            // Discrepancy
            $table->integer('conversion_discrepancy')->default(0)
                ->comment('Farq: platform - actual');
            $table->decimal('value_discrepancy', 12, 2)->default(0)
                ->comment('Qiymat farqi');
            $table->decimal('discrepancy_percent', 8, 2)->default(0)
                ->comment('Farq foizi');

            // Status
            $table->string('status')->default('pending')
                ->comment('pending, matched, discrepancy, investigated');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['business_id', 'reconciliation_date', 'platform', 'platform_campaign_id'], 'reconciliation_unique');
            $table->index(['business_id', 'reconciliation_date']);
            $table->index(['business_id', 'status']);
        });
        }
    }

    public function down(): void
    {
        // Remove from leads
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['acquisition_cost', 'acquisition_source_type']);
        });

        // Remove from sales
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['acquisition_cost', 'attribution_source_type', 'attributed_spend']);
        });

        // Remove from customers
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['first_acquisition_channel_id']);
            $table->dropForeign(['first_campaign_id']);
            $table->dropColumn([
                'first_acquisition_source',
                'first_acquisition_source_type',
                'first_acquisition_channel_id',
                'first_campaign_id',
                'total_acquisition_cost',
                'churn_risk_score',
                'churn_risk_level',
                'last_activity_at',
                'churned_at',
                'churn_reason',
                'lifetime_value',
                'days_since_last_purchase',
                'purchase_frequency_days',
            ]);
        });

        // Remove from kpi_daily_entries
        Schema::table('kpi_daily_entries', function (Blueprint $table) {
            $table->dropColumn([
                'revenue_digital',
                'revenue_offline',
                'revenue_referral',
                'revenue_organic',
                'roas_digital',
                'roas_offline',
                'roas_total',
                'roi_total',
                'profit_total',
            ]);
        });

        Schema::dropIfExists('conversion_reconciliations');
    }
};
