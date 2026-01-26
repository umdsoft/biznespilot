<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

/**
 * TokenExpiredNotification - Token Muammosi Haqida Admin Ogohlantirish
 *
 * Bu notification quyidagi holatlarda yuboriladi:
 * - Token butunlay eskirgan
 * - Token yangilash muvaffaqiyatsiz
 * - Foydalanuvchi paroli o'zgarganligi sababli token invalid
 *
 * CRITICAL: Bu xatoliklar butun Chatbot va Integratsiya tizimini to'xtatadi!
 */
class TokenExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Integration $integration,
        public string $reason
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): TelegramMessage
    {
        $business = $this->integration->business;
        $businessName = $business?->name ?? 'Unknown';

        $message = "🚨 *CRITICAL: Token Muammosi!*\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🏢 *Biznes:* {$businessName}\n";
        $message .= "🔗 *Integration:* {$this->integration->name}\n";
        $message .= "🆔 *ID:* `{$this->integration->id}`\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "⚠️ *Sabab:*\n";
        $message .= $this->reason . "\n\n";

        $message .= "📋 *Ta'sir:*\n";
        $message .= "• Instagram/Facebook DM botlari ishlamaydi\n";
        $message .= "• Marketing ma'lumotlari sinxronlanmaydi\n";
        $message .= "• Voronka avtomatizatsiyasi to'xtaydi\n\n";

        $message .= "✅ *Yechim:*\n";
        $message .= "1. Biznes sozlamalariga o'ting\n";
        $message .= "2. Integratsiyani qayta ulang\n";
        $message .= "3. Facebook/Instagram da parol o'zgarganmi tekshiring\n\n";

        $message .= "🕐 *Vaqt:* " . now()->format('d.m.Y H:i') . "\n";

        // Ulash ko'rsatmasi
        $settingsUrl = url("/business/settings");

        return TelegramMessage::create()
            ->content($message)
            ->button('Sozlamalarni ochish', $settingsUrl)
            ->button('Dokumentatsiya', 'https://developers.facebook.com/docs/facebook-login/guides/access-tokens');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'integration_id' => $this->integration->id,
            'business_id' => $this->integration->business_id,
            'reason' => $this->reason,
            'type' => 'token_expired',
            'severity' => 'critical',
        ];
    }
}
