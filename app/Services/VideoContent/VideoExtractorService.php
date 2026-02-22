<?php

namespace App\Services\VideoContent;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class VideoExtractorService
{
    protected string $binary;

    protected int $timeout;

    protected int $maxDuration;

    protected string $audioFormat;

    protected string $audioQuality;

    protected string $tempPath;

    protected ?string $ffmpegPath;

    public function __construct()
    {
        $config = config('video-content.extractor');
        $this->binary = $config['binary'] ?? 'yt-dlp';
        $this->timeout = $config['timeout'] ?? 120;
        $this->maxDuration = $config['max_duration'] ?? 1800;
        $this->audioFormat = $config['audio_format'] ?? 'mp3';
        $this->audioQuality = $config['audio_quality'] ?? '64k';
        $this->tempPath = storage_path('app/' . config('video-content.storage.temp_path', 'video-content/temp'));
        $this->ffmpegPath = $config['ffmpeg_location'] ?? null;
    }

    /**
     * Run yt-dlp process with correct environment (TEMP dir for PyInstaller)
     */
    protected function runProcess(array $cmd, int $timeout = null): \Illuminate\Contracts\Process\ProcessResult
    {
        $tempDir = sys_get_temp_dir();

        return Process::timeout($timeout ?? $this->timeout)
            ->env([
                'TEMP' => $tempDir,
                'TMP' => $tempDir,
                'TMPDIR' => $tempDir,
                'PATH' => getenv('PATH') ?: '',
                'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\Windows',
            ])
            ->run($cmd);
    }

    /**
     * Extract audio from video URL
     *
     * @return array{audio_path: string, metadata: array}
     *
     * @throws \Exception
     */
    public function extractAudio(string $videoUrl): array
    {
        $this->ensureTempDir();

        // Step 1: Get video metadata
        $metadata = $this->getMetadata($videoUrl);

        // Step 2: Validate duration
        $duration = $metadata['duration'] ?? 0;
        if ($duration > $this->maxDuration) {
            throw new \Exception(
                "Video juda uzun: {$duration}s (max: {$this->maxDuration}s / " . ($this->maxDuration / 60) . " min). " .
                "Qisqaroq video tanlang."
            );
        }

        if ($duration > 0 && $duration < 10) {
            throw new \Exception("Video juda qisqa: {$duration}s (min: 10s).");
        }

        // Step 3: Download audio only
        $outputFile = $this->tempPath . '/' . Str::uuid() . '.' . $this->audioFormat;

        Log::info('Extracting audio from video', [
            'url' => $videoUrl,
            'title' => $metadata['title'] ?? 'Unknown',
            'duration' => $duration,
        ]);

        $cmd = [
            $this->binary,
            '--extract-audio',
            '--audio-format', $this->audioFormat,
            '--audio-quality', $this->audioQuality,
            '--output', $outputFile,
            '--no-playlist',
            '--no-warnings',
        ];

        if ($this->ffmpegPath) {
            $cmd[] = '--ffmpeg-location';
            $cmd[] = $this->ffmpegPath;
        }

        $cmd[] = $videoUrl;

        $result = $this->runProcess($cmd);

        if (! $result->successful()) {
            $error = $result->errorOutput();
            Log::error('yt-dlp audio extraction failed', [
                'url' => $videoUrl,
                'error' => $error,
            ]);
            throw new \Exception("Video audiosi yuklab olinmadi: " . $this->parseYtdlpError($error));
        }

        // Verify output file exists
        if (! file_exists($outputFile)) {
            // yt-dlp may add extension, search for the file
            $outputFile = $this->findOutputFile($outputFile);
            if (! $outputFile) {
                throw new \Exception('Audio fayl yaratilmadi. Video formatini tekshiring.');
            }
        }

        Log::info('Audio extracted successfully', [
            'path' => $outputFile,
            'size' => filesize($outputFile),
        ]);

        return [
            'audio_path' => $outputFile,
            'metadata' => $metadata,
        ];
    }

    /**
     * Get video metadata without downloading
     */
    public function getMetadata(string $videoUrl): array
    {
        $result = $this->runProcess([
            $this->binary,
            '--dump-json',
            '--no-download',
            '--no-playlist',
            '--no-warnings',
            $videoUrl,
        ], 30);

        if (! $result->successful()) {
            $error = $result->errorOutput();
            Log::warning('Failed to get video metadata', [
                'url' => $videoUrl,
                'error' => $error,
            ]);
            throw new \Exception("Video ma'lumotlari olinmadi: " . $this->parseYtdlpError($error));
        }

        $data = json_decode($result->output(), true);
        if (! $data) {
            throw new \Exception("Video metadata parse qilib bo'lmadi.");
        }

        return [
            'title' => $data['title'] ?? null,
            'duration' => (int) ($data['duration'] ?? 0),
            'thumbnail' => $data['thumbnail'] ?? null,
            'uploader' => $data['uploader'] ?? null,
            'view_count' => $data['view_count'] ?? null,
            'description' => mb_substr($data['description'] ?? '', 0, 500),
        ];
    }

    /**
     * Cleanup temp files
     */
    public function cleanup(string $audioPath): void
    {
        if (file_exists($audioPath)) {
            @unlink($audioPath);
        }
    }

    /**
     * Check if yt-dlp is available
     */
    public function isAvailable(): bool
    {
        try {
            $result = $this->runProcess([$this->binary, '--version'], 5);

            return $result->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get yt-dlp version
     */
    public function getVersion(): ?string
    {
        try {
            $result = $this->runProcess([$this->binary, '--version'], 5);

            return $result->successful() ? trim($result->output()) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function ensureTempDir(): void
    {
        if (! is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    /**
     * Search for output file (yt-dlp may modify filename)
     */
    protected function findOutputFile(string $expectedPath): ?string
    {
        $dir = dirname($expectedPath);
        $baseName = pathinfo($expectedPath, PATHINFO_FILENAME);

        $files = glob($dir . '/' . $baseName . '.*');

        return ! empty($files) ? $files[0] : null;
    }

    /**
     * Parse yt-dlp error into user-friendly message
     */
    protected function parseYtdlpError(string $error): string
    {
        if (str_contains($error, 'is not a valid URL')) {
            return 'Noto\'g\'ri URL format.';
        }
        if (str_contains($error, 'Video unavailable') || str_contains($error, 'Private video')) {
            return 'Video mavjud emas yoki yopiq (private).';
        }
        if (str_contains($error, 'Sign in to confirm') || str_contains($error, 'age restriction') || str_contains($error, 'age-restricted')) {
            return 'Video kirish talab qiladi (age restriction).';
        }
        if (str_contains($error, 'HTTP Error 403') || str_contains($error, 'HTTP Error 404')) {
            return 'Videoga kirish taqiqlangan yoki topilmadi.';
        }

        return mb_substr($error, 0, 200);
    }
}
