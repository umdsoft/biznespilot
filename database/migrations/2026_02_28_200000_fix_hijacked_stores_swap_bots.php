<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O'g'irlangan do'konlarni to'g'ri botlarga qayta biriktirish.
 *
 * Sabab: StoreSetupController dagi bug ikkinchi bot yaratganda
 * birinchi botning do'konini ustiga yozgan. Natijada:
 * - Bot B (yangi) → Bot A ning asliy do'konini olgan (data bilan)
 * - Bot A (eski) → fix_orphaned_bots migration yangi bo'sh do'kon yaratgan
 *
 * Bu migration: hijacked do'konlarni aniqlaydi va botlarni almashtirib tuzatadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Data mavjudligini tekshirish uchun jadvallar (store_id bilan)
        $dataTables = ['store_orders', 'store_products', 'store_categories', 'store_customers'];

        // 1. Har bir do'konni uning boti bilan birga olish
        $stores = DB::table('telegram_stores as ts')
            ->join('telegram_bots as tb', 'ts.telegram_bot_id', '=', 'tb.id')
            ->select(
                'ts.id as store_id',
                'ts.business_id',
                'ts.telegram_bot_id',
                'ts.store_type',
                'ts.name as store_name',
                'ts.is_active as store_is_active',
                'ts.created_at as store_created_at',
                'tb.id as bot_id',
                'tb.bot_first_name',
                'tb.created_at as bot_created_at'
            )
            ->get();

        $swapped = 0;

        foreach ($stores as $store) {
            // 2. Do'konda bot yaratilishidan OLDIN kiritilgan data bormi?
            $hasOldData = false;

            foreach ($dataTables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                if (! Schema::hasColumn($table, 'store_id')) {
                    continue;
                }

                $oldDataExists = DB::table($table)
                    ->where('store_id', $store->store_id)
                    ->where('created_at', '<', $store->bot_created_at)
                    ->exists();

                if ($oldDataExists) {
                    $hasOldData = true;
                    break;
                }
            }

            if (! $hasOldData) {
                continue;
            }

            // 3. Bu do'kon o'g'irlangan! Asliy egasini topish.
            // Asliy egasi = shu biznesdagi boshqa bot, hozir bo'sh do'konga ega
            $candidateBots = DB::table('telegram_bots as tb')
                ->join('telegram_stores as ts', 'tb.id', '=', 'ts.telegram_bot_id')
                ->where('tb.business_id', $store->business_id)
                ->where('tb.id', '!=', $store->telegram_bot_id)
                ->select(
                    'tb.id as bot_id',
                    'tb.bot_first_name',
                    'tb.created_at as bot_created_at',
                    'ts.id as store_id',
                    'ts.store_type',
                    'ts.name as store_name',
                    'ts.is_active as store_is_active'
                )
                ->get();

            foreach ($candidateBots as $candidate) {
                // Candidate ning do'koni bo'sh ekanini tekshirish
                $candidateStoreHasData = false;

                foreach ($dataTables as $table) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }
                    if (! Schema::hasColumn($table, 'store_id')) {
                        continue;
                    }

                    if (DB::table($table)->where('store_id', $candidate->store_id)->exists()) {
                        $candidateStoreHasData = true;
                        break;
                    }
                }

                if ($candidateStoreHasData) {
                    // Bu candidate ning do'konida ham data bor — skip
                    continue;
                }

                // Candidate ning boti o'g'irlangan do'kondan OLDIN yaratilganmi?
                // (Eski bot = asliy egasi)
                $candidateBotCreated = Carbon::parse($candidate->bot_created_at);
                $hijackerBotCreated = Carbon::parse($store->bot_created_at);

                if ($candidateBotCreated->greaterThan($hijackerBotCreated)) {
                    // Candidate yangi bot — bu asliy egasi emas
                    continue;
                }

                // 4. Topildi! Almashtiramiz.
                // O'g'irlangan do'kon (data bilan) → asliy egasiga
                // Bo'sh do'kon → hozirgi botga (o'g'irlovchi)

                // store_type larni almashtiramiz
                $hijackedStoreType = $store->store_type;       // delivery (o'g'irlovchi o'zgartirgan)
                $emptyStoreType = $candidate->store_type;      // ecommerce (fix migration yozgan)

                // Nomi: asliy egasiga bot_first_name dan olamiz
                $originalBotName = $candidate->bot_first_name;
                $hijackerBotName = $store->bot_first_name;

                // O'g'irlangan do'konni asliy egasiga qaytarish
                DB::table('telegram_stores')
                    ->where('id', $store->store_id)
                    ->update([
                        'telegram_bot_id' => $candidate->bot_id,
                        'store_type' => $emptyStoreType,
                        'name' => $originalBotName ?: $candidate->store_name,
                        'updated_at' => now(),
                    ]);

                // Bo'sh do'konni o'g'irlovchi botga berish
                DB::table('telegram_stores')
                    ->where('id', $candidate->store_id)
                    ->update([
                        'telegram_bot_id' => $store->telegram_bot_id,
                        'store_type' => $hijackedStoreType,
                        'name' => $hijackerBotName ?: $store->store_name,
                        'updated_at' => now(),
                    ]);

                $swapped++;

                logger()->info(
                    "Fix hijacked store: Do'kon {$store->store_id} (data bilan) → Bot {$candidate->bot_id} ({$originalBotName}), "
                    ."Do'kon {$candidate->store_id} (bo'sh) → Bot {$store->telegram_bot_id} ({$hijackerBotName})"
                );

                break; // Bitta candidate topilsa yetarli
            }
        }

        if ($swapped > 0) {
            logger()->info("Fix: {$swapped} ta o'g'irlangan do'kon to'g'ri botlarga qayta biriktirildi.");
        } else {
            logger()->info('Fix: O\'g\'irlangan do\'konlar topilmadi.');
        }
    }

    public function down(): void
    {
        // Bu migration ni qaytarib bo'lmaydi — data yaxlitligi muhimroq
    }
};
