<?php

namespace App\Notifications;

use App\Models\WeeklyAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class WeeklyAnalyticsReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WeeklyAnalytics $analytics,
        public array $channels = ['mail', 'telegram']
    ) {}

    public function via(object $notifiable): array
    {
        $via = [];

        if (in_array('mail', $this->channels) && $notifiable->email) {
            $via[] = 'mail';
        }

        if (in_array('telegram', $this->channels) && $notifiable->telegram_chat_id) {
            $via[] = 'telegram';
        }

        return $via;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summary = $this->analytics->summary_stats ?? [];
        $business = $this->analytics->business;

        $totalLeads = $summary['total_leads'] ?? 0;
        $won = $summary['won'] ?? 0;
        $lost = $summary['lost'] ?? 0;
        $conversion = $summary['conversion_rate'] ?? 0;
        $revenue = $this->formatMoney($summary['total_revenue'] ?? 0);
        $vsLastWeek = $summary['vs_last_week'] ?? [];

        $leadsChange = $vsLastWeek['leads'] ?? '0%';
        $wonChange = $vsLastWeek['won'] ?? '0%';
        $conversionChange = $this->formatChange($vsLastWeek['conversion'] ?? 0);
        $revenueChange = $vsLastWeek['revenue'] ?? '0%';

        return (new MailMessage)
            ->subject("Haftalik Hisobot: {$this->analytics->week_label} - {$business->name}")
            ->greeting("Salom, {$notifiable->name}!")
            ->line("**{$business->name}** uchun haftalik hisobot tayyor.")
            ->line("---")
            ->line("**Hafta:** {$this->analytics->week_label}")
            ->line("**Umumiy ko'rsatkichlar:**")
            ->line("- Jami lidlar: **{$totalLeads}** ({$leadsChange})")
            ->line("- Yutilgan: **{$won}** ({$wonChange})")
            ->line("- Yo'qotilgan: **{$lost}**")
            ->line("- Konversiya: **{$conversion}%** ({$conversionChange})")
            ->line("- Daromad: **{$revenue}** ({$revenueChange})")
            ->line("---")
            ->when($this->analytics->hasAiAnalysis(), function ($message) {
                $message->line("**🤖 AI Tahlil mavjud!**");
                if ($this->analytics->ai_next_week_goal) {
                    $message->line("Keyingi hafta maqsadi: {$this->analytics->ai_next_week_goal}");
                }
            })
            ->action('Hisobotni ko\'rish', url("/business/analytics/weekly-report"))
            ->salutation("Omad tilaymiz! 🚀");
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $summary = $this->analytics->summary_stats ?? [];
        $business = $this->analytics->business;

        $totalLeads = $summary['total_leads'] ?? 0;
        $won = $summary['won'] ?? 0;
        $lost = $summary['lost'] ?? 0;
        $conversion = $summary['conversion_rate'] ?? 0;
        $revenue = $this->formatMoney($summary['total_revenue'] ?? 0);
        $vsLastWeek = $summary['vs_last_week'] ?? [];

        $leadsChange = $vsLastWeek['leads'] ?? '0%';
        $conversionChange = $this->formatChange($vsLastWeek['conversion'] ?? 0);

        $message = "Haftalik Hisobot\n";
        $message .= "*{$business->name}*\n";
        $message .= "{$this->analytics->week_label}\n\n";

        $message .= "*Ko'rsatkichlar:*\n";
        $message .= "- Lidlar: *{$totalLeads}* ({$leadsChange})\n";
        $message .= "- Yutilgan: *{$won}*\n";
        $message .= "- Yo'qotilgan: *{$lost}*\n";
        $message .= "- Konversiya: *{$conversion}%* ({$conversionChange})\n";
        $message .= "- Daromad: *{$revenue}*\n\n";

        if ($this->analytics->hasAiAnalysis()) {
            $message .= "🤖 *AI Tahlil:*\n";

            $goodResults = $this->analytics->ai_good_results ?? [];
            if (! empty($goodResults)) {
                $message .= "✅ " . ($goodResults[0] ?? '') . "\n";
            }

            $problems = $this->analytics->ai_problems ?? [];
            if (! empty($problems)) {
                $message .= "⚠️ " . ($problems[0] ?? '') . "\n";
            }

            if ($this->analytics->ai_next_week_goal) {
                $message .= "\n🎯 *Maqsad:* " . $this->analytics->ai_next_week_goal . "\n";
            }
        }

        return TelegramMessage::create()
            ->to($notifiable->telegram_chat_id)
            ->content($message)
            ->button('Hisobotni ko\'rish', url("/business/analytics/weekly-report"));
    }

    protected function formatMoney($amount): string
    {
        if ($amount >= 1000000000) {
            return round($amount / 1000000000, 1) . ' mlrd so\'m';
        }
        if ($amount >= 1000000) {
            return round($amount / 1000000, 1) . ' mln so\'m';
        }
        if ($amount >= 1000) {
            return round($amount / 1000, 1) . 'k so\'m';
        }

        return number_format($amount, 0, '.', ' ') . ' so\'m';
    }

    protected function formatChange($value): string
    {
        if ($value > 0) {
            return "+{$value}%";
        }
        return "{$value}%";
    }
}
