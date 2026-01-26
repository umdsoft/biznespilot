<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System Bot Authentication Fields
 *
 * BiznesPilot "Dual Bot Strategy":
 * - telegram_chat_id: Foydalanuvchi Telegram chat ID (System Bot orqali)
 * - telegram_auth_token: Vaqtinchalik token deep linking uchun
 *
 * Bu maydonlar Business Owner larning Telegram hisobini
 * BiznesPilot System Bot ga ulash uchun ishlatiladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // System Bot authentication
            $table->string('telegram_chat_id')->nullable()->after('email_verified_at')
                ->comment('Telegram chat ID for System Bot notifications');

            $table->string('telegram_auth_token')->nullable()->after('telegram_chat_id')
                ->comment('Temporary token for deep linking authentication');

            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_auth_token')
                ->comment('When Telegram account was linked');

            // Notification preferences
            $table->boolean('receive_daily_reports')->default(true)->after('telegram_linked_at')
                ->comment('Whether to receive daily reports via Telegram');

            // Index for quick lookup during webhook processing
            $table->index('telegram_chat_id');
            $table->index('telegram_auth_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['telegram_chat_id']);
            $table->dropIndex(['telegram_auth_token']);
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_auth_token',
                'telegram_linked_at',
                'receive_daily_reports',
            ]);
        });
    }
};
