<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TelegramBot;
use App\Services\Telegram\TelegramApiService;

$bot = TelegramBot::first();

echo "=== BOT INFO ===\n";
echo "Bot: @" . $bot->bot_username . "\n";
echo "Bot ID: " . $bot->id . "\n";
echo "Webhook URL (DB): " . ($bot->webhook_url ?? 'Not set') . "\n\n";

$api = new TelegramApiService($bot);

echo "=== TELEGRAM WEBHOOK INFO ===\n";
$result = $api->getWebhookInfo();

if ($result['success']) {
    $info = $result['result'];
    echo "URL: " . ($info['url'] ?? 'Not set') . "\n";
    echo "Pending updates: " . ($info['pending_update_count'] ?? 0) . "\n";
    echo "Last error: " . ($info['last_error_message'] ?? 'None') . "\n";
    echo "Last error date: " . (isset($info['last_error_date']) ? date('Y-m-d H:i:s', $info['last_error_date']) : 'None') . "\n";
} else {
    echo "Error: " . ($result['description'] ?? 'Unknown error') . "\n";
}
