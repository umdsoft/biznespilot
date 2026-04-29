<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trialing subscription'lar uchun amount=0 ga normalize qilish.
 *
 * SubscriptionService::create eski versiyasi trial uchun ham
 * `amount = plan->price_monthly` saqlardi. Bu noto'g'ri — trial
 * davomida foydalanuvchi hech qanday pul to'lamagan.
 *
 * Bu admin paneldagi "Narx" ustunida 1,199,000 UZS ko'rsatilishiga
 * sabab bo'lardi (xato semantika). Endi trialing → amount=0,
 * faqat haqiqiy to'lov bo'lgan active subscription'larda
 * amount = plan_price.
 */
return new class extends Migration
{
    public function up(): void
    {
        $updated = DB::table('subscriptions')
            ->where('status', 'trialing')
            ->where('amount', '>', 0)
            ->update(['amount' => 0]);

        if ($updated > 0) {
            \Log::info("Trialing amount normalize: {$updated} ta subscription amount=0 ga o'zgartirildi");
        }
    }

    public function down(): void
    {
        // Reversible emas — eski qiymatlarni tiklash mumkin emas
    }
};
