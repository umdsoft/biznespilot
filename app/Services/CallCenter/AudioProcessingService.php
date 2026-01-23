<?php

namespace App\Services\CallCenter;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AudioProcessingService
{
    protected string $tempPath;

    protected string $storageDisk;

    public function __construct()
    {
        $this->tempPath = config('call-center.storage.temp_path', 'call-center/temp');
        $this->storageDisk = config('call-center.storage.disk', 'r2');
    }

    /**
     * Download audio from external URL (Sipuni, etc.)
     *
     * @throws \Exception
     */
    public function downloadFromUrl(string $audioUrl): string
    {
        Log::info('Downloading audio from URL', ['url' => $audioUrl]);

        try {
            $response = Http::timeout(120)
                ->withOptions(['verify' => false])
                ->get($audioUrl);

            if (! $response->successful()) {
                throw new \Exception("Failed to download audio: HTTP {$response->status()}");
            }

            // Determine file extension from URL or content type
            $extension = $this->getExtensionFromUrl($audioUrl) ?? 'mp3';
            $filename = Str::uuid().'.'.$extension;
            $localPath = storage_path("app/temp/{$filename}");

            // Ensure temp directory exists
            if (! is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            file_put_contents($localPath, $response->body());

            Log::info('Audio downloaded successfully', [
                'path' => $localPath,
                'size' => filesize($localPath),
            ]);

            return $localPath;
        } catch (\Exception $e) {
            Log::error('Failed to download audio', [
                'url' => $audioUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Compress audio using FFmpeg
     *
     * @throws \Exception
     */
    public function compressAudio(string $inputPath): string
    {
        if (! config('call-center.audio.compress.enabled', true)) {
            return $inputPath;
        }

        $config = config('call-center.audio.compress');
        $outputPath = $this->generateTempPath('mp3');

        $command = [
            'ffmpeg',
            '-i', $inputPath,
            '-vn',                        // No video
            '-ar', (string) $config['sample_rate'],
            '-ac', (string) $config['channels'],
            '-b:a', $config['bitrate'],
            '-f', $config['format'],
            '-y',                         // Overwrite output
            $outputPath,
        ];

        Log::info('Compressing audio with FFmpeg', [
            'input' => $inputPath,
            'output' => $outputPath,
            'settings' => $config,
        ]);

        try {
            $process = new Process($command);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Clean up original file
            @unlink($inputPath);

            Log::info('Audio compressed successfully', [
                'path' => $outputPath,
                'size' => filesize($outputPath),
            ]);

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('FFmpeg compression failed', [
                'error' => $e->getMessage(),
                'input' => $inputPath,
            ]);
            throw new \Exception('Audio compression failed: '.$e->getMessage());
        }
    }

    /**
     * Upload audio to cloud storage (R2)
     */
    public function uploadToStorage(string $localPath): string
    {
        $filename = basename($localPath);
        $storagePath = "{$this->tempPath}/{$filename}";

        Log::info('Uploading audio to storage', [
            'local' => $localPath,
            'remote' => $storagePath,
            'disk' => $this->storageDisk,
        ]);

        try {
            $content = file_get_contents($localPath);
            Storage::disk($this->storageDisk)->put($storagePath, $content);

            // Clean up local file
            @unlink($localPath);

            Log::info('Audio uploaded successfully', ['path' => $storagePath]);

            return $storagePath;
        } catch (\Exception $e) {
            Log::error('Failed to upload audio to storage', [
                'error' => $e->getMessage(),
                'path' => $localPath,
            ]);
            throw $e;
        }
    }

    /**
     * Get temporary URL for stored audio
     */
    public function getTemporaryUrl(string $storagePath, int $minutes = 30): ?string
    {
        try {
            return Storage::disk($this->storageDisk)->temporaryUrl(
                $storagePath,
                now()->addMinutes($minutes)
            );
        } catch (\Exception $e) {
            Log::warning('Could not generate temporary URL', [
                'path' => $storagePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete audio from storage
     */
    public function deleteFromStorage(string $storagePath): bool
    {
        try {
            $deleted = Storage::disk($this->storageDisk)->delete($storagePath);

            Log::info('Audio deleted from storage', [
                'path' => $storagePath,
                'success' => $deleted,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::warning('Failed to delete audio from storage', [
                'path' => $storagePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get audio duration in seconds using FFprobe
     */
    public function getAudioDuration(string $filePath): ?int
    {
        try {
            $command = [
                'ffprobe',
                '-v', 'error',
                '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1',
                $filePath,
            ];

            $process = new Process($command);
            $process->setTimeout(30);
            $process->run();

            if ($process->isSuccessful()) {
                return (int) round((float) trim($process->getOutput()));
            }
        } catch (\Exception $e) {
            Log::warning('Could not get audio duration', [
                'path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Validate audio file
     *
     * @throws \Exception
     */
    public function validateAudio(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \Exception('Audio file not found');
        }

        $size = filesize($filePath);
        $duration = $this->getAudioDuration($filePath);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $supportedFormats = config('call-center.audio.supported_formats', []);
        if (! in_array(strtolower($extension), $supportedFormats)) {
            throw new \Exception("Unsupported audio format: {$extension}");
        }

        $minDuration = config('call-center.audio.min_duration', 30);
        $maxDuration = config('call-center.audio.max_duration', 3600);

        if ($duration !== null) {
            if ($duration < $minDuration) {
                throw new \Exception("Audio too short: {$duration}s (minimum: {$minDuration}s)");
            }
            if ($duration > $maxDuration) {
                throw new \Exception("Audio too long: {$duration}s (maximum: {$maxDuration}s)");
            }
        }

        return [
            'size' => $size,
            'duration' => $duration,
            'extension' => $extension,
            'valid' => true,
        ];
    }

    /**
     * Process audio: download, validate, compress, upload
     *
     * @throws \Exception
     */
    public function processAudio(string $audioUrl): array
    {
        $startTime = microtime(true);

        // Download
        $localPath = $this->downloadFromUrl($audioUrl);

        try {
            // Validate
            $validation = $this->validateAudio($localPath);

            // Compress
            $compressedPath = $this->compressAudio($localPath);

            // Upload
            $storagePath = $this->uploadToStorage($compressedPath);

            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'storage_path' => $storagePath,
                'duration' => $validation['duration'],
                'original_size' => $validation['size'],
                'processing_time_ms' => $processingTime,
            ];
        } catch (\Exception $e) {
            // Clean up on failure
            @unlink($localPath);
            throw $e;
        }
    }

    /**
     * Clean up old temporary files
     */
    public function cleanup(): int
    {
        $cleanupAfterMinutes = config('call-center.storage.cleanup_after_minutes', 30);
        $threshold = now()->subMinutes($cleanupAfterMinutes);
        $deleted = 0;

        try {
            $files = Storage::disk($this->storageDisk)->files($this->tempPath);

            foreach ($files as $file) {
                $lastModified = Storage::disk($this->storageDisk)->lastModified($file);
                if ($lastModified < $threshold->timestamp) {
                    Storage::disk($this->storageDisk)->delete($file);
                    $deleted++;
                }
            }

            Log::info('Cleaned up temporary audio files', ['deleted' => $deleted]);
        } catch (\Exception $e) {
            Log::warning('Cleanup failed', ['error' => $e->getMessage()]);
        }

        return $deleted;
    }

    /**
     * Generate a temporary file path
     */
    protected function generateTempPath(string $extension): string
    {
        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.'/'.Str::uuid().'.'.$extension;
    }

    /**
     * Extract file extension from URL
     */
    protected function getExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if ($extension) {
                return strtolower($extension);
            }
        }

        return null;
    }

    /**
     * Get file content from storage for API upload
     */
    public function getFileContent(string $storagePath): string
    {
        return Storage::disk($this->storageDisk)->get($storagePath);
    }

    /**
     * Get file path from storage
     */
    public function getLocalPath(string $storagePath): string
    {
        // Download from storage to local temp
        $content = $this->getFileContent($storagePath);
        $localPath = $this->generateTempPath('mp3');
        file_put_contents($localPath, $content);

        return $localPath;
    }
}
