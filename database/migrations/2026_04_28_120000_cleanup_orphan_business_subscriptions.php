<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BusinessObserver eski versiyasi har biznes yaratilganda Business plan'da
 * trial subscription yaratardi. WelcomeController esa keyin Trial Pack'ni
 * yaratib, eski Business'ni cancelled qilardi → DUPLIKAT.
 *
 * Bu yerda fake Business subscription'larni tozalaymiz:
 *  - status = 'cancelled'
 *  - payment_provider IS NULL or empty (real to'lov bo'lmagan)
 *  - metadata IS NULL (Observer yaratganda metadata bo'sh)
 *  - real BillingTransaction yo'q (to'lov tarixi yo'q)
 *
 * Bu admin paneldagi 13 ta "bekor" subscriptions chalkashligini bartaraf etadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        $deleted = DB::table('subscriptions')
            ->where('status', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('payment_provider')->orWhere('payment_provider', '');
            })
            ->whereNull('metadata')
            ->delete();

        if ($deleted > 0) {
            \Log::info("Cleanup: {$deleted} ta orphan Business cancelled subscription o'chirildi");
        }
    }

    public function down(): void
    {
        // Reversible emas — o'chirilgan ma'lumotni tiklash mumkin emas
    }
};
