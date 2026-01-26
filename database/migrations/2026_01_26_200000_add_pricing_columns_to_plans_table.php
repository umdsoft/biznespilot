<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plans jadvaliga narxlash va limitlar uchun yangi ustunlar qo'shish
 *
 * Bu migration quyidagi muammolarni hal qiladi:
 * 1. price_monthly va price_yearly ustunlari (Model bilan moslik)
 * 2. audio_minutes_limit - Call Center AI uchun limit
 * 3. ai_requests_limit - AI so'rovlari uchun limit
 * 4. storage_limit_mb - Fayl saqlash uchun limit
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Narxlash ustunlari (agar mavjud bo'lmasa)
            if (!Schema::hasColumn('plans', 'price_monthly')) {
                $table->decimal('price_monthly', 12, 2)->default(0)->after('description');
            }

            if (!Schema::hasColumn('plans', 'price_yearly')) {
                $table->decimal('price_yearly', 12, 2)->default(0)->after('price_monthly');
            }

            // Audio daqiqalari limiti (Call Center AI uchun)
            if (!Schema::hasColumn('plans', 'audio_minutes_limit')) {
                $table->integer('audio_minutes_limit')->nullable()->after('chatbot_channel_limit');
            }

            // AI so'rovlari limiti
            if (!Schema::hasColumn('plans', 'ai_requests_limit')) {
                $table->integer('ai_requests_limit')->nullable()->after('audio_minutes_limit');
            }

            // Saqlash limiti (MB)
            if (!Schema::hasColumn('plans', 'storage_limit_mb')) {
                $table->integer('storage_limit_mb')->nullable()->after('ai_requests_limit');
            }

            // Instagram DM limiti (oylik)
            if (!Schema::hasColumn('plans', 'instagram_dm_limit')) {
                $table->integer('instagram_dm_limit')->nullable()->after('storage_limit_mb');
            }

            // Content posts limiti (oylik)
            if (!Schema::hasColumn('plans', 'content_posts_limit')) {
                $table->integer('content_posts_limit')->nullable()->after('instagram_dm_limit');
            }
        });

        // Agar eski 'price' ustuni mavjud bo'lsa, ma'lumotlarni ko'chirish
        if (Schema::hasColumn('plans', 'price') && Schema::hasColumn('plans', 'price_monthly')) {
            \DB::statement('UPDATE plans SET price_monthly = price WHERE price_monthly = 0');
            // Yillik narx = oylik * 10 (2 oy tekin)
            \DB::statement('UPDATE plans SET price_yearly = price_monthly * 10 WHERE price_yearly = 0');
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $columns = [
                'price_monthly',
                'price_yearly',
                'audio_minutes_limit',
                'ai_requests_limit',
                'storage_limit_mb',
                'instagram_dm_limit',
                'content_posts_limit',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
