<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telegram bot limiti qo'shish
 *
 * Free tarifda faqat Telegram, Instagram yo'q
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'telegram_bot_limit')) {
                $table->integer('telegram_bot_limit')->nullable()->after('chatbot_channel_limit');
            }

            if (!Schema::hasColumn('plans', 'has_instagram')) {
                $table->boolean('has_instagram')->default(true)->after('telegram_bot_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'telegram_bot_limit')) {
                $table->dropColumn('telegram_bot_limit');
            }
            if (Schema::hasColumn('plans', 'has_instagram')) {
                $table->dropColumn('has_instagram');
            }
        });
    }
};
