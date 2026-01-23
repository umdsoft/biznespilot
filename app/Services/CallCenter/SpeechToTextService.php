<?php

namespace App\Services\CallCenter;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpeechToTextService
{
    protected string $apiKey;

    protected string $apiUrl;

    protected string $model;

    protected string $language;

    protected int $timeout;

    public function __construct()
    {
        $config = config('call-center.stt.groq');

        $this->apiKey = $config['api_key'] ?? '';
        $this->apiUrl = $config['api_url'] ?? 'https://api.groq.com/openai/v1/audio/transcriptions';
        $this->model = $config['model'] ?? 'whisper-large-v3-turbo';
        $this->language = $config['language'] ?? 'uz';
        $this->timeout = $config['timeout'] ?? 120;
    }

    /**
     * Transcribe audio file to text
     *
     * @param  string  $audioPath  Local path to audio file
     * @return array{text: string, duration: int, cost: float, model: string}
     *
     * @throws \Exception
     */
    public function transcribe(string $audioPath): array
    {
        $this->validateApiKey();

        if (! file_exists($audioPath)) {
            throw new \Exception("Audio file not found: {$audioPath}");
        }

        Log::info('Starting transcription', [
            'path' => $audioPath,
            'model' => $this->model,
            'language' => $this->language,
        ]);

        $startTime = microtime(true);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                ])
                ->attach(
                    'file',
                    file_get_contents($audioPath),
                    basename($audioPath)
                )
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'language' => $this->language,
                    'response_format' => 'json',
                ]);

            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                $error = $response->json('error.message') ?? $response->body();
                Log::error('Groq API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                throw new \Exception("Groq API error: {$error}");
            }

            $data = $response->json();
            $text = $data['text'] ?? '';

            if (empty(trim($text))) {
                throw new \Exception('Transcription resulted in empty text');
            }

            // Calculate duration from file (approximate)
            $duration = $this->getAudioDuration($audioPath);
            $cost = $this->calculateCost($duration);

            Log::info('Transcription completed', [
                'duration' => $duration,
                'text_length' => strlen($text),
                'cost' => $cost,
                'processing_time_ms' => $processingTime,
            ]);

            return [
                'text' => $text,
                'duration' => $duration,
                'cost' => $cost,
                'model' => $this->model,
                'processing_time_ms' => $processingTime,
            ];
        } catch (\Exception $e) {
            Log::error('Transcription failed', [
                'path' => $audioPath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Transcribe audio from URL
     *
     * @param  string  $audioUrl  URL to audio file
     * @return array{text: string, duration: int, cost: float, model: string}
     *
     * @throws \Exception
     */
    public function transcribeFromUrl(string $audioUrl, AudioProcessingService $audioService): array
    {
        // Download audio to local temp file
        $localPath = $audioService->downloadFromUrl($audioUrl);

        try {
            $result = $this->transcribe($localPath);
        } finally {
            // Clean up
            @unlink($localPath);
        }

        return $result;
    }

    /**
     * Calculate cost based on duration
     * Groq Whisper: $0.04 per hour
     */
    public function calculateCost(int $durationSeconds): float
    {
        $pricePerHour = config('call-center.stt.pricing.per_hour', 0.04);
        $hours = $durationSeconds / 3600;

        return round($hours * $pricePerHour, 6);
    }

    /**
     * Get estimated cost for a call (before transcription)
     */
    public function estimateCost(int $durationSeconds): array
    {
        $cost = $this->calculateCost($durationSeconds);
        $uzsRate = config('call-center.currency.usd_to_uzs', 12800);

        return [
            'usd' => $cost,
            'uzs' => round($cost * $uzsRate),
            'formatted' => number_format($cost * $uzsRate, 0, '.', ' ').' so\'m',
        ];
    }

    /**
     * Get audio duration using FFprobe
     */
    protected function getAudioDuration(string $filePath): int
    {
        try {
            $command = sprintf(
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
                escapeshellarg($filePath)
            );

            $output = shell_exec($command);
            if ($output !== null) {
                return (int) round((float) trim($output));
            }
        } catch (\Exception $e) {
            Log::warning('Could not determine audio duration', ['error' => $e->getMessage()]);
        }

        // Fallback: estimate from file size (rough estimate for MP3)
        $fileSize = filesize($filePath);

        return (int) ($fileSize / 8000); // ~8KB per second for 64kbps
    }

    /**
     * Validate API key is configured
     *
     * @throws \Exception
     */
    protected function validateApiKey(): void
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Groq API key is not configured. Please set GROQ_API_KEY in .env');
        }
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            $this->validateApiKey();

            // Make a simple request to verify the API key
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                ])
                ->get('https://api.groq.com/openai/v1/models');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Groq API connection successful',
                ];
            }

            return [
                'success' => false,
                'error' => 'API returned status: '.$response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return [
            'model' => $this->model,
            'language' => $this->language,
            'timeout' => $this->timeout,
            'api_configured' => ! empty($this->apiKey),
        ];
    }
}
