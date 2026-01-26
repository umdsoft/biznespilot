<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Telegram\SystemBotService;
use Illuminate\Console\Command;

/**
 * Telegram System Bot Webhook Setup Command
 *
 * Usage:
 *   php artisan telegram:setup-webhook              # Uses TELEGRAM_SYSTEM_WEBHOOK_URL from .env
 *   php artisan telegram:setup-webhook --url=https://example.com/api/webhooks/system-bot
 *   php artisan telegram:setup-webhook --delete     # Delete webhook
 *   php artisan telegram:setup-webhook --info       # Show bot info
 */
class TelegramSetupWebhookCommand extends Command
{
    protected $signature = 'telegram:setup-webhook
                            {--url= : Custom webhook URL (overrides .env)}
                            {--delete : Delete the webhook}
                            {--info : Show bot info}';

    protected $description = 'Setup Telegram System Bot webhook';

    public function handle(SystemBotService $systemBot): int
    {
        // Check if bot is configured
        if (!$systemBot->isConfigured()) {
            $this->error('Telegram System Bot is not configured!');
            $this->line('Please set TELEGRAM_SYSTEM_BOT_TOKEN in .env file.');

            return self::FAILURE;
        }

        // Show bot info
        if ($this->option('info')) {
            return $this->showBotInfo($systemBot);
        }

        // Delete webhook
        if ($this->option('delete')) {
            return $this->deleteWebhook($systemBot);
        }

        // Set webhook
        return $this->setWebhook($systemBot);
    }

    protected function setWebhook(SystemBotService $systemBot): int
    {
        // Get webhook URL from option or config
        $webhookUrl = $this->option('url') ?? config('services.telegram.webhook_url');

        if (empty($webhookUrl)) {
            $this->error('Webhook URL not provided!');
            $this->line('');
            $this->line('Options:');
            $this->line('  1. Set TELEGRAM_SYSTEM_WEBHOOK_URL in .env file');
            $this->line('  2. Use --url option: php artisan telegram:setup-webhook --url=https://...');

            return self::FAILURE;
        }

        $this->info('Setting up Telegram System Bot webhook...');
        $this->line('');
        $this->line("URL: {$webhookUrl}");
        $this->line('');

        $result = $systemBot->setWebhook($webhookUrl);

        if ($result) {
            $this->info('Webhook set successfully!');
            $this->line('');
            $this->line('Bot is now ready to receive messages.');

            return self::SUCCESS;
        }

        $this->error('Failed to set webhook!');
        $this->line('Please check the logs for more details.');

        return self::FAILURE;
    }

    protected function deleteWebhook(SystemBotService $systemBot): int
    {
        $this->info('Deleting Telegram System Bot webhook...');

        $result = $systemBot->deleteWebhook();

        if ($result) {
            $this->info('Webhook deleted successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to delete webhook!');

        return self::FAILURE;
    }

    protected function showBotInfo(SystemBotService $systemBot): int
    {
        $this->info('Fetching Telegram System Bot info...');
        $this->line('');

        $botInfo = $systemBot->getBotInfo();

        if (!$botInfo) {
            $this->error('Failed to get bot info!');
            $this->line('Please check if the bot token is correct.');

            return self::FAILURE;
        }

        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $botInfo['id'] ?? 'N/A'],
                ['Username', '@' . ($botInfo['username'] ?? 'N/A')],
                ['First Name', $botInfo['first_name'] ?? 'N/A'],
                ['Can Join Groups', ($botInfo['can_join_groups'] ?? false) ? 'Yes' : 'No'],
                ['Can Read Messages', ($botInfo['can_read_all_group_messages'] ?? false) ? 'Yes' : 'No'],
                ['Supports Inline', ($botInfo['supports_inline_queries'] ?? false) ? 'Yes' : 'No'],
            ]
        );

        $this->line('');
        $this->line('Configured webhook URL: ' . (config('services.telegram.webhook_url') ?: 'Not set'));

        return self::SUCCESS;
    }
}
