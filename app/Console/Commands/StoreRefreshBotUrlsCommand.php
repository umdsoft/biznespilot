<?php

namespace App\Console\Commands;

use App\Models\Store\TelegramStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoreRefreshBotUrlsCommand extends Command
{
    protected $signature = 'store:refresh-bot-urls';

    protected $description = 'Barcha store botlarning webhook va menu button URL larini APP_URL ga yangilash (tunnel o\'zgarganda ishlatiladi)';

    public function handle(): int
    {
        $baseUrl = config('app.url');
        $this->info("APP_URL: {$baseUrl}");
        $this->line('');

        $stores = TelegramStore::whereNotNull('telegram_bot_id')
            ->with('telegramBot')
            ->get();

        if ($stores->isEmpty()) {
            $this->warn('Hech qanday store-bot topilmadi.');

            return self::SUCCESS;
        }

        $this->info("{$stores->count()} ta store topildi.");
        $this->line('');

        $success = 0;
        $failed = 0;

        foreach ($stores as $store) {
            $bot = $store->telegramBot;
            if (! $bot || ! $bot->bot_token) {
                $this->warn("  [{$store->name}] Bot token yo'q — o'tkazib yuborildi");
                $failed++;

                continue;
            }

            $this->line("  [{$store->name}] @{$bot->bot_username}");

            $botToken = $bot->bot_token;

            // 1. Update webhook
            $webhookUrl = "{$baseUrl}/webhooks/telegram-funnel/{$bot->id}";
            $webhookSecret = $bot->webhook_secret ?: bin2hex(random_bytes(32));

            $webhookResponse = Http::withOptions(['verify' => false])->timeout(30)->post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                'url' => $webhookUrl,
                'secret_token' => $webhookSecret,
                'allowed_updates' => ['message', 'callback_query', 'my_chat_member'],
            ]);

            if ($webhookResponse->successful()) {
                $bot->update([
                    'webhook_url' => $webhookUrl,
                    'webhook_secret' => $webhookSecret,
                ]);
                $this->info("    Webhook: {$webhookUrl}");
            } else {
                $this->error("    Webhook xatolik: " . $webhookResponse->body());
                $failed++;

                continue;
            }

            // 2. Update menu button
            $miniAppUrl = "{$baseUrl}/miniapp/{$store->slug}";
            $menuResponse = Http::withOptions(['verify' => false])->timeout(30)->post("https://api.telegram.org/bot{$botToken}/setChatMenuButton", [
                'menu_button' => [
                    'type' => 'web_app',
                    'text' => 'Do\'kon',
                    'web_app' => ['url' => $miniAppUrl],
                ],
            ]);

            if ($menuResponse->successful()) {
                $this->info("    Menu button: {$miniAppUrl}");
            } else {
                $this->error("    Menu button xatolik: " . $menuResponse->body());
            }

            $success++;
        }

        $this->line('');
        $this->info("Tayyor: {$success} muvaffaqiyatli, {$failed} xatolik.");

        return self::SUCCESS;
    }
}
