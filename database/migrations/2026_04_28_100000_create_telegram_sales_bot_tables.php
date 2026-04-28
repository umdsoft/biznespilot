<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 0. step_type enum'ga ai_consultant qo'shish ─────────────────
        if (Schema::hasColumn('telegram_funnel_steps', 'step_type')) {
            DB::statement("
                ALTER TABLE telegram_funnel_steps
                MODIFY COLUMN step_type ENUM(
                    'message', 'input', 'condition', 'action', 'delay',
                    'subscribe_check', 'quiz', 'ab_test', 'tag',
                    'trigger_keyword', 'start', 'ai_consultant'
                ) DEFAULT 'message'
            ");
        }

        // ─── 0b. waiting_for enum'ga ai_consultant_message qo'shish ─────
        if (Schema::hasColumn('telegram_user_states', 'waiting_for')) {
            DB::statement("
                ALTER TABLE telegram_user_states
                MODIFY COLUMN waiting_for ENUM(
                    'none', 'text', 'number', 'phone', 'email', 'photo',
                    'document', 'location', 'contact', 'subscribe_check',
                    'quiz_answer', 'ai_consultant_message'
                ) DEFAULT 'none'
            ");
        }

        // ─── 1. telegram_users — kengaytirish (mijoz portreti) ──────────
        Schema::table('telegram_users', function (Blueprint $table) {
            // AI tomonidan to'plangan ehtiyoj/profil ma'lumoti
            // Marketing mutaxassislari ko'radi: lid kartochkasida
            $table->json('customer_profile')->nullable()->after('custom_data')
                ->comment('AI tomonidan to\'plangan: interests, preferences, demographics, needs');

            // Sotib olishlar statistikasi
            $table->decimal('lifetime_value', 14, 2)->default(0)->after('customer_profile')
                ->comment('Jami xarajat so\'mda');
            $table->unsignedInteger('purchase_count')->default(0)->after('lifetime_value');
            $table->timestamp('last_purchase_at')->nullable()->after('purchase_count');

            // Re-engagement signallari
            $table->unsignedTinyInteger('dormant_score')->default(0)->after('last_purchase_at')
                ->comment('0-100, qaytish ehtimoli (yuqoriroq = ko\'proq xavf)');
            $table->timestamp('last_recommended_at')->nullable()->after('dormant_score');

            $table->index(['business_id', 'last_purchase_at'], 'tu_business_last_purchase_idx');
            $table->index(['business_id', 'dormant_score'], 'tu_business_dormant_idx');
        });

        // ─── 2. customer_need_profiles — har conversation uchun ──────────
        // ConversationEngine'ning AI dialog davomida to'playotgan ehtiyojlar.
        // Bitta TelegramUser'ning bir nechta dialogi bo'lishi mumkin (turli savol).
        Schema::create('customer_need_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('telegram_user_id');
            $table->uuid('conversation_id')->nullable();

            $table->string('primary_intent')->nullable()
                ->comment('Asosiy ehtiyoj: krossovka_sotib_olish, kosmetika_tanlash, ...');
            $table->string('use_case')->nullable()
                ->comment('Foydalanish maqsadi: kunlik_ish, sport, sovga, ...');

            $table->json('constraints')->nullable()
                ->comment('budget_max, size, color, brand preferences, avoidances');
            $table->json('viewed_products')->nullable()
                ->comment('Mijoz ko\'rgan mahsulot ID lari (rejected emas)');
            $table->json('rejected_products')->nullable()
                ->comment('Mijoz "yoqmaydi" deganlari');
            $table->json('recommended_products')->nullable()
                ->comment('AI tavsiya qilgan mahsulotlar (history)');

            $table->decimal('info_completeness', 3, 2)->default(0.00)
                ->comment('0.00-1.00 — AI tavsiya berishga tayyormi');
            $table->boolean('ready_to_buy')->default(false);
            $table->json('blockers')->nullable()
                ->comment('AI aniqlagan to\'siqlar: narx, ishonchsizlik, ...');

            $table->string('current_state', 30)->default('greeting')
                ->comment('greeting/discovery/recommend/objection/checkout/post_sale');

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('telegram_user_id')->references('id')->on('telegram_users')->cascadeOnDelete();
            $table->index(['business_id', 'ready_to_buy', 'updated_at'], 'cnp_ready_to_buy_idx');
            $table->index(['telegram_user_id', 'updated_at'], 'cnp_user_idx');
        });

        // ─── 3. customer_behavior_events — har action log ───────────────
        // Marketing tahlili uchun. Misol: viewed_product, clicked_button,
        // added_to_cart, abandoned_cart, completed_purchase, asked_question
        Schema::create('customer_behavior_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('telegram_user_id');
            $table->string('event_type', 50);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('telegram_user_id')->references('id')->on('telegram_users')->cascadeOnDelete();
            $table->index(['business_id', 'event_type', 'created_at'], 'cbe_business_event_idx');
            $table->index(['telegram_user_id', 'created_at'], 'cbe_user_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_behavior_events');
        Schema::dropIfExists('customer_need_profiles');

        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropIndex('tu_business_last_purchase_idx');
            $table->dropIndex('tu_business_dormant_idx');
            $table->dropColumn([
                'customer_profile',
                'lifetime_value',
                'purchase_count',
                'last_purchase_at',
                'dormant_score',
                'last_recommended_at',
            ]);
        });
    }
};
