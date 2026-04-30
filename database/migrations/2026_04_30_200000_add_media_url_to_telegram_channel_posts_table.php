<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telegram kanal postlari ro'yxatida rasmlarni ko'rsatish uchun
 * `media_url` ustuni qo'shamiz. URL t.me/s/{username} HTML scraper
 * tomonidan to'ldiriladi (background-image:url(...) atributidan
 * extract qilinadi). Ushbu URL'lar Telegram CDN'da public xosting'da —
 * bot token kerak emas, frontend'da to'g'ridan-to'g'ri <img src> yoki
 * background-image sifatida ko'rsatish mumkin.
 *
 * Bot API webhook (`channel_post`) orqali olingan postlarda media URL
 * yo'q (faqat file_id keladi va getFile chaqirish kerak — natijasi
 * bot token'li URL bo'ladi). Shu bois media_url asosan public scraper
 * orqali to'ldiriladi va periodik refresh paytida yangilanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('telegram_channel_posts', 'media_url')) {
            return; // already exists
        }

        Schema::table('telegram_channel_posts', function (Blueprint $table) {
            $table->string('media_url', 1000)->nullable()->after('text_preview')
                ->comment('Public CDN URL — t.me/s scraper orqali olinadi');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('telegram_channel_posts', 'media_url')) {
            return;
        }

        Schema::table('telegram_channel_posts', function (Blueprint $table) {
            $table->dropColumn('media_url');
        });
    }
};
