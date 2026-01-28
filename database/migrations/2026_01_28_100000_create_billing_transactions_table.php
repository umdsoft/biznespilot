<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS Billing Transactions - Payme va Click Merchant Integratsiyasi
 *
 * Bu jadval bizneslar platformaga to'lov qilganda (subscription uchun)
 * barcha tranzaksiyalarni saqlaydi.
 *
 * MUHIM: Payme summani tiyinda (1 so'm = 100 tiyin) yuboradi.
 * Bazada esa so'mda saqlaymiz. Konversiya service darajasida bo'ladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // BILLING TRANSACTIONS - Asosiy tranzaksiyalar jadvali
        // ============================================================
        Schema::create('billing_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Tashqi dunyo uchun unikal ID

            // Bog'lanishlar
            $table->uuid('business_id');
            $table->uuid('plan_id');
            $table->uuid('subscription_id')->nullable(); // Yangilanayotgan subscription

            // Provider ma'lumotlari
            $table->enum('provider', ['payme', 'click']);
            $table->string('provider_transaction_id')->nullable(); // Payme yoki Click IDsi
            $table->string('order_id')->unique(); // Bizning ichki order ID

            // Moliyaviy ma'lumotlar
            $table->decimal('amount', 15, 2); // So'mda (UZS)
            $table->string('currency', 3)->default('UZS');

            // Status tracking
            $table->enum('status', [
                'created',    // Tranzaksiya yaratildi, to'lov kutilmoqda
                'waiting',    // Payme/Click tomonidan qabul qilindi
                'processing', // To'lov jarayonida
                'paid',       // To'lov muvaffaqiyatli
                'cancelled',  // Bekor qilindi
                'failed',     // Xatolik yuz berdi
                'refunded',   // Pul qaytarildi
            ])->default('created');

            $table->integer('status_code')->nullable(); // Provayder xatolik kodi
            $table->string('status_message')->nullable(); // Xatolik xabari

            // Vaqt belgilari
            $table->timestamp('performed_at')->nullable(); // To'lov amalga oshgan vaqt
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Tranzaksiya amal qilish muddati

            // Bekor qilish sababi
            $table->string('cancel_reason')->nullable();

            // Debug va audit uchun
            $table->json('payload')->nullable(); // Provayderdan kelgan barcha ma'lumotlar
            $table->json('metadata')->nullable(); // Qo'shimcha biznes ma'lumotlari
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('business_id')
                ->references('id')->on('businesses')
                ->onDelete('cascade');
            $table->foreign('plan_id')
                ->references('id')->on('plans')
                ->onDelete('restrict');
            $table->foreign('subscription_id')
                ->references('id')->on('subscriptions')
                ->onDelete('set null');

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'created_at']);
            $table->index(['provider', 'status']);
            $table->index('provider_transaction_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('created_at');
        });

        // ============================================================
        // PAYME TRANSACTIONS - Payme holat tracking
        // ============================================================
        Schema::create('billing_payme_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_transaction_id')
                ->constrained('billing_transactions')
                ->onDelete('cascade');

            // Payme tomonidan berilgan ID'lar
            $table->string('payme_id')->nullable(); // Payme transaction ID (_id)
            $table->bigInteger('payme_time')->nullable(); // Payme timestamp (milliseconds)

            // Payme holatlari (state machine)
            // 1 = Created, 2 = Completed, -1 = Cancelled after create, -2 = Cancelled after complete
            $table->tinyInteger('state')->default(1);

            // Vaqt belgilari (milliseconds - Payme formati)
            $table->bigInteger('create_time')->nullable();
            $table->bigInteger('perform_time')->nullable();
            $table->bigInteger('cancel_time')->nullable();

            // Bekor qilish sababi
            // 1 = Unknown, 2 = Wrong amount, 3 = Order cancelled, 4 = Timeout, 5 = Refund
            $table->tinyInteger('reason')->nullable();

            // So'rovlar tarixi
            $table->json('requests_log')->nullable(); // Barcha so'rovlar logi

            $table->timestamps();

            // Indexes
            $table->unique('payme_id');
            $table->index('state');
        });

        // ============================================================
        // CLICK TRANSACTIONS - Click holat tracking
        // ============================================================
        Schema::create('billing_click_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_transaction_id')
                ->constrained('billing_transactions')
                ->onDelete('cascade');

            // Click tomonidan berilgan ID'lar
            $table->bigInteger('click_trans_id')->nullable();
            $table->bigInteger('click_paydoc_id')->nullable();
            $table->string('merchant_trans_id')->nullable(); // Bizning order_id
            $table->string('merchant_prepare_id')->nullable(); // Prepare bosqichidagi ID

            // Click holati
            $table->tinyInteger('action')->nullable(); // 0 = Prepare, 1 = Complete
            $table->integer('error_code')->default(0);
            $table->string('error_note')->nullable();

            // Imzo tekshiruvi
            $table->string('sign_string')->nullable();
            $table->string('sign_time')->nullable();

            // So'rovlar tarixi
            $table->json('requests_log')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('click_trans_id');
            $table->index('merchant_trans_id');
        });

        // ============================================================
        // BILLING WEBHOOKS LOG - Barcha webhook'lar logi
        // ============================================================
        Schema::create('billing_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('provider', ['payme', 'click']);
            $table->string('method')->nullable(); // Payme: CheckPerformTransaction, etc.
            $table->string('action')->nullable(); // Click: prepare, complete

            // So'rov ma'lumotlari
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_body')->nullable();

            $table->integer('response_code')->default(200);
            $table->string('ip_address', 45)->nullable();

            // Bog'lanish (agar topilsa)
            $table->foreignId('billing_transaction_id')
                ->nullable()
                ->constrained('billing_transactions')
                ->onDelete('set null');

            $table->boolean('is_successful')->default(false);
            $table->text('error_message')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['provider', 'created_at']);
            $table->index('billing_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_webhook_logs');
        Schema::dropIfExists('billing_click_transactions');
        Schema::dropIfExists('billing_payme_transactions');
        Schema::dropIfExists('billing_transactions');
    }
};
