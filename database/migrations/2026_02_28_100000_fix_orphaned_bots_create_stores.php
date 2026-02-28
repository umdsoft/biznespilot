<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Do'konsiz (yetim) botlarga TelegramStore yaratish.
 *
 * Sabab: StoreSetupController dagi bug — ikkinchi bot yaratganda
 * birinchi botning do'konini ustiga yozib yuborgan.
 * Bu migration yetim botlarni tuzatadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Do'konsiz (yetim) botlarni topish
        $orphanedBots = DB::table('telegram_bots as tb')
            ->leftJoin('telegram_stores as ts', 'tb.id', '=', 'ts.telegram_bot_id')
            ->whereNull('ts.id')
            ->select('tb.id', 'tb.business_id', 'tb.bot_first_name', 'tb.bot_username')
            ->get();

        foreach ($orphanedBots as $bot) {
            $slug = Str::slug($bot->bot_first_name ?: $bot->bot_username) . '-' . Str::random(6);

            DB::table('telegram_stores')->insert([
                'id' => Str::uuid()->toString(),
                'business_id' => $bot->business_id,
                'telegram_bot_id' => $bot->id,
                'name' => $bot->bot_first_name ?: $bot->bot_username,
                'slug' => $slug,
                'store_type' => 'ecommerce',
                'currency' => 'UZS',
                'is_active' => false,
                'settings' => json_encode([]),
                'enabled_features' => json_encode([]),
                'theme' => json_encode(config('store.default_theme', [])),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $count = count($orphanedBots);
        if ($count > 0) {
            logger()->info("Fix: {$count} ta yetim bot uchun TelegramStore yaratildi.");
        }

        // 2. NULL yoki bo'sh store_type larni ecommerce ga o'zgartirish
        $fixed = DB::table('telegram_stores')
            ->where(function ($q) {
                $q->whereNull('store_type')
                    ->orWhere('store_type', '');
            })
            ->update(['store_type' => 'ecommerce']);

        if ($fixed > 0) {
            logger()->info("Fix: {$fixed} ta TelegramStore ning store_type ecommerce ga o'zgartirildi.");
        }
    }

    public function down(): void
    {
        // Rollback: yetim botlar uchun yaratilgan do'konlarni o'chirish mumkin emas
        // chunki qaysi do'konlar bu migration da yaratilganini ajratib bo'lmaydi
    }
};
