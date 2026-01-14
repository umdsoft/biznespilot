<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Illuminate\Support\Facades\Http;

class TelegramLogChannel
{
    public function __invoke(array $config): Logger
    {
        return new Logger('telegram', [
            new TelegramLogHandler(
                $config['token'] ?? '',
                $config['chat_id'] ?? '',
                $config['level'] ?? Logger::CRITICAL
            ),
        ]);
    }
}

class TelegramLogHandler extends AbstractProcessingHandler
{
    private string $token;
    private string $chatId;

    public function __construct(string $token, string $chatId, $level = Logger::CRITICAL, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->token = $token;
        $this->chatId = $chatId;
    }

    protected function write(LogRecord $record): void
    {
        if (empty($this->token) || empty($this->chatId)) {
            return;
        }

        $message = $this->formatMessage($record);

        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $e) {
            // Silently fail - don't throw exceptions in logging
        }
    }

    private function formatMessage(LogRecord $record): string
    {
        $emoji = match ($record->level->getName()) {
            'EMERGENCY' => '🔴🔴🔴',
            'ALERT' => '🔴🔴',
            'CRITICAL' => '🔴',
            'ERROR' => '🟠',
            'WARNING' => '🟡',
            default => '🔵',
        };

        $env = app()->environment();
        $appName = config('app.name', 'BiznesPilot');

        $message = "<b>{$emoji} [{$record->level->getName()}] {$appName}</b>\n";
        $message .= "<b>Environment:</b> {$env}\n";
        $message .= "<b>Time:</b> " . $record->datetime->format('Y-m-d H:i:s') . "\n\n";
        $message .= "<b>Message:</b>\n<code>" . htmlspecialchars(substr($record->message, 0, 1000)) . "</code>";

        if (!empty($record->context)) {
            $context = json_encode($record->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (strlen($context) > 500) {
                $context = substr($context, 0, 500) . '...';
            }
            $message .= "\n\n<b>Context:</b>\n<code>" . htmlspecialchars($context) . "</code>";
        }

        return $message;
    }
}
