<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Admin tarif narxini o'zgartirsa, eski "created" tranzaksiyalar
 * eski narxni saqlab qoladi. Foydalanuvchi to'lov sahifasiga
 * o'tganda eski narxni ko'radi.
 *
 * Bu migration mavjud "created" status tranzaksiyalarning amount'i
 * hozirgi plan narxiga mos kelmasa — ularni bekor qiladi (yangisi
 * yaratiladi getOrCreatePaymentUrl chaqirilganda).
 */
return new class extends Migration
{
    public function up(): void
    {
        $cancelled = 0;

        // Har "created" tranzaksiya uchun
        DB::table('billing_transactions')
            ->where('status', 'created')
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use (&$cancelled) {
                foreach ($transactions as $txn) {
                    $plan = DB::table('plans')->where('id', $txn->plan_id)->first();
                    if (! $plan) continue;

                    $cycle = $txn->metadata
                        ? (json_decode($txn->metadata, true)['billing_cycle'] ?? 'monthly')
                        : 'monthly';

                    $currentPrice = $cycle === 'yearly'
                        ? (float) $plan->price_yearly
                        : (float) $plan->price_monthly;

                    // Narx mos kelmasa — bekor qilish
                    if (bccomp((string) $txn->amount, (string) $currentPrice, 2) !== 0) {
                        DB::table('billing_transactions')
                            ->where('id', $txn->id)
                            ->update([
                                'status' => 'cancelled',
                                'cancelled_at' => now(),
                                'cancel_reason' => 'Plan price changed (auto-cleanup)',
                            ]);
                        $cancelled++;
                    }
                }
            });

        if ($cancelled > 0) {
            \Log::info("Stale priced transactions cancelled: {$cancelled}");
        }
    }

    public function down(): void
    {
        // Reversible emas
    }
};
