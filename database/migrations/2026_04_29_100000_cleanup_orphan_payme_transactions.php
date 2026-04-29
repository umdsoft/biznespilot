<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PAYME credentials .env'da BO'SH bo'lgani sababli foydalanuvchilar
 * Payme tugmasini bosgan vaqtda sahifa to'g'ri ochilmagan, lekin
 * BillingTransaction yaratilgan. Bu DB'da hech qachon to'lanmaydigan
 * orphan tranzaksiyalarni hosil qiladi.
 *
 * Bu migration tozalaydi:
 *  - provider='payme' AND status='created' AND performed_at IS NULL
 *  - YOKI muddati tugagan ('created' va expires_at < now())
 *
 * Click tranzaksiyalari saqlanadi (haqiqiy to'lov yo'lida bo'lishi mumkin).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Payme orphans — credentials yo'q bo'lsa hech qachon to'lanmaydi
        $deletedPayme = DB::table('billing_transactions')
            ->where('provider', 'payme')
            ->where('status', 'created')
            ->whereNull('performed_at')
            ->delete();

        // Tugagan muddati Click tranzaksiyalarini ham tozalash
        $deletedExpired = DB::table('billing_transactions')
            ->where('status', 'created')
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        if ($deletedPayme > 0 || $deletedExpired > 0) {
            \Log::info('Payment cleanup', [
                'payme_orphans' => $deletedPayme,
                'expired' => $deletedExpired,
            ]);
        }
    }

    public function down(): void
    {
        // Reversible emas
    }
};
