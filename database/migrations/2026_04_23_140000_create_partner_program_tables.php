<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner Program — "Win-Win Recurring Commission" Model A.
 *
 * Commission stavkasi:
 *  - Yil 1 (birinchi 12 oy): tier bo'yicha 10/12/15/20%
 *  - Yil 2+ (lifetime): 5/6/7/10%
 *
 * Jadvallar:
 *  1. partners               — partner profil (user_id bilan bog'lanadi)
 *  2. partner_referrals      — partner→business mapping (kim kimni olib keldi)
 *  3. partner_commissions    — har bir to'lovdan olingan commission (audit trail)
 *  4. partner_payouts        — aggregate to'lov batchlari
 *  5. partner_clicks         — tracking link analytics
 *  6. partner_tier_rules     — admin konfiguratsiya
 *  +  businesses.referral_partner_id (attribution)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. PARTNER TIER RULES ===========================================
        Schema::create('partner_tier_rules', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tier', 20)->unique(); // bronze, silver, gold, platinum
            $t->string('name', 100);
            $t->string('icon', 10)->nullable();
            // Year-1 rate (initial 12 months after referral first payment)
            $t->decimal('year_one_rate', 5, 4)->default(0.10);   // 0.1000 = 10%
            // Lifetime rate (after year 1, indefinite while referral active)
            $t->decimal('lifetime_rate', 5, 4)->default(0.05);   // 0.0500 = 5%
            $t->unsignedInteger('min_active_referrals')->default(0);
            $t->decimal('min_monthly_volume_uzs', 15, 2)->default(0);
            $t->json('perks')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        // 2. PARTNERS =====================================================
        Schema::create('partners', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Unique public-facing referral code (/refer/{code})
            $t->string('code', 32)->unique();

            $t->string('status', 20)->default('pending');
            // pending | active | suspended | terminated

            $t->string('tier', 20)->default('bronze');
            // bronze | silver | gold | platinum (FK-lite to partner_tier_rules.tier)

            // Custom override rates — nullable means tier rates apply
            $t->decimal('custom_year_one_rate', 5, 4)->nullable();
            $t->decimal('custom_lifetime_rate', 5, 4)->nullable();

            // Partner type — helps segmentation + UI
            $t->string('partner_type', 20)->default('individual');
            // individual | agency | influencer | integrator

            // Contact / payout info
            $t->string('full_name', 150);
            $t->string('phone', 30)->nullable();
            $t->string('telegram_id', 50)->nullable();
            $t->string('company_name', 150)->nullable();
            $t->string('inn_stir', 30)->nullable();

            // Payout details (encrypted at app layer ideally)
            $t->string('bank_name', 100)->nullable();
            $t->string('bank_account', 50)->nullable();
            $t->string('preferred_payout_method', 30)->default('bank_transfer');
            // bank_transfer | humo | uzcard | payme | click | cash

            // Agreement
            $t->timestamp('agreement_signed_at')->nullable();
            $t->string('agreement_version', 10)->nullable();

            // Cached metrics (updated by observer / scheduled job)
            $t->unsignedInteger('referrals_count_cached')->default(0);
            $t->unsignedInteger('active_referrals_count_cached')->default(0);
            $t->decimal('lifetime_earned_cached', 15, 2)->default(0);
            $t->decimal('available_balance_cached', 15, 2)->default(0);

            // Notes / admin only
            $t->text('admin_notes')->nullable();

            $t->timestamps();
            $t->softDeletes();

            $t->index(['status', 'tier']);
            $t->index('partner_type');
            // user_id is FK — Laravel creates partners_user_id_foreign index
        });

        // 3. PARTNER REFERRALS ============================================
        Schema::create('partner_referrals', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('partner_id')->constrained('partners')->cascadeOnDelete();
            $t->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();

            $t->string('referred_via', 20)->default('link');
            // link | code | manual | telegram_bot | direct

            $t->string('ref_code_snapshot', 32)->nullable();
            $t->string('utm_source', 100)->nullable();
            $t->string('utm_medium', 100)->nullable();
            $t->string('utm_campaign', 100)->nullable();

            // Lifecycle timestamps
            $t->timestamp('attributed_at')->nullable();        // first confirmed paid sub
            $t->timestamp('first_payment_at')->nullable();
            $t->timestamp('year_one_ends_at')->nullable();     // first_payment + 12m
            $t->timestamp('churned_at')->nullable();

            $t->string('status', 20)->default('pending');
            // pending | attributed | active | churned | cancelled | disputed

            $t->decimal('lifetime_commission_earned', 15, 2)->default(0);

            $t->timestamps();

            $t->unique(['partner_id', 'business_id']);
            $t->index(['partner_id', 'status']);
            $t->index(['business_id', 'status']);
        });

        // 4. PARTNER PAYOUTS ==============================================
        // (Must be created BEFORE partner_commissions because commissions FK
        //  to payouts via payout_id)
        Schema::create('partner_payouts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('partner_id')->constrained('partners')->cascadeOnDelete();

            $t->decimal('total_amount', 15, 2);
            $t->unsignedInteger('commissions_count')->default(0);

            $t->string('status', 20)->default('pending');
            // pending | approved | processing | paid | failed | cancelled

            $t->string('payout_method', 30);
            $t->string('payout_reference', 100)->nullable(); // bank txn id
            $t->json('payout_details')->nullable();          // snapshot of bank info at time of request

            $t->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->timestamp('paid_at')->nullable();

            $t->text('failure_reason')->nullable();
            $t->text('note')->nullable();

            $t->timestamps();

            $t->index(['partner_id', 'status']);
            $t->index(['status', 'created_at']);
        });

        // 5. PARTNER COMMISSIONS ==========================================
        Schema::create('partner_commissions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('partner_id')->constrained('partners')->cascadeOnDelete();
            $t->foreignUuid('referral_id')->constrained('partner_referrals')->cascadeOnDelete();
            $t->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignUuid('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            // billing_transactions uses bigint PK, not UUID — use foreignId
            $t->foreignId('billing_transaction_id')->nullable()->constrained('billing_transactions')->nullOnDelete();

            $t->decimal('gross_amount', 15, 2);      // Plan to'liq narxi (e.g. 299000)
            $t->decimal('rate_applied', 5, 4);       // 0.1000 or 0.0500
            $t->decimal('commission_amount', 15, 2); // gross * rate

            $t->string('rate_type', 20)->default('year_one');
            // year_one | lifetime

            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();

            $t->string('status', 20)->default('pending');
            // pending | available | paid | reversed | clawback

            $t->timestamp('available_at');           // billing_txn + 30 days
            $t->timestamp('paid_at')->nullable();

            $t->foreignUuid('payout_id')->nullable()->constrained('partner_payouts')->nullOnDelete();

            $t->string('clawback_reason', 200)->nullable();
            $t->text('admin_note')->nullable();

            $t->timestamps();

            $t->index(['partner_id', 'status']);
            $t->index(['status', 'available_at']);   // for daily promotion cron
            $t->index('business_id');
            $t->index('billing_transaction_id');
        });

        // 6. PARTNER CLICKS (analytics) ==================================
        Schema::create('partner_clicks', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('partner_id')->constrained('partners')->cascadeOnDelete();
            $t->string('code', 32);
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 500)->nullable();
            $t->string('utm_source', 100)->nullable();
            $t->string('utm_medium', 100)->nullable();
            $t->string('utm_campaign', 100)->nullable();
            $t->string('landing_page', 500)->nullable();
            $t->string('referrer', 500)->nullable();
            $t->foreignUuid('converted_business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['partner_id', 'created_at']);
            $t->index('code');
        });

        // 7. BUSINESSES.REFERRAL_PARTNER_ID ===============================
        if (! Schema::hasColumn('businesses', 'referral_partner_id')) {
            Schema::table('businesses', function (Blueprint $t) {
                $t->foreignUuid('referral_partner_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('partners')
                    ->nullOnDelete();
                $t->string('referral_code_used', 32)->nullable()->after('referral_partner_id');
                $t->index('referral_partner_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('businesses', 'referral_partner_id')) {
            Schema::table('businesses', function (Blueprint $t) {
                $t->dropForeign(['referral_partner_id']);
                $t->dropColumn(['referral_partner_id', 'referral_code_used']);
            });
        }

        Schema::dropIfExists('partner_clicks');
        Schema::dropIfExists('partner_commissions');
        Schema::dropIfExists('partner_payouts');
        Schema::dropIfExists('partner_referrals');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('partner_tier_rules');
    }
};
