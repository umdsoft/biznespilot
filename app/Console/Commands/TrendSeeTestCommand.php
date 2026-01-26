<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ViralHunterJob;
use App\Services\External\ApifyService;
use App\Services\TrendSee\ViralHunterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * TrendSee Test Command - Debug and test viral content fetching via Apify.
 *
 * Usage:
 *   php artisan trendsee:test            - Test API configuration
 *   php artisan trendsee:test --fetch    - Fetch and save viral content
 *   php artisan trendsee:test --dispatch - Dispatch ViralHunterJob
 *   php artisan trendsee:test --clear    - Clear rate limit cache
 */
class TrendSeeTestCommand extends Command
{
    protected $signature = 'trendsee:test
                            {--fetch : Fetch viral content from Apify}
                            {--dispatch : Dispatch ViralHunterJob to queue}
                            {--sync : Run ViralHunterJob synchronously}
                            {--clear : Clear rate limit cache}
                            {--hashtag= : Test specific hashtag}';

    protected $description = 'Test TrendSee/Apify configuration and fetch viral content';

    public function handle(ApifyService $apify, ViralHunterService $viralHunter): int
    {
        $this->info('');
        $this->info('========================================');
        $this->info('  TrendSee - Viral Content Hunter Test');
        $this->info('  Powered by Apify Instagram Scraper');
        $this->info('========================================');
        $this->info('');

        // Step 1: Check configuration
        $this->checkConfiguration($apify);

        // Step 2: Handle options
        if ($this->option('clear')) {
            return $this->clearRateLimitCache($apify);
        }

        if ($this->option('dispatch')) {
            return $this->dispatchJob();
        }

        if ($this->option('sync')) {
            return $this->runSync();
        }

        if ($this->option('fetch')) {
            return $this->fetchContent($apify, $viralHunter);
        }

        // Default: Show configuration only
        return Command::SUCCESS;
    }

    /**
     * Check Apify configuration.
     */
    private function checkConfiguration(ApifyService $apify): void
    {
        $this->info('📋 Checking configuration...');
        $this->newLine();

        $apiToken = config('services.apify.token');

        $this->table(
            ['Setting', 'Value', 'Status'],
            [
                ['APIFY_TOKEN', $apiToken ? substr($apiToken, 0, 12) . '...' : 'NOT SET', $apiToken ? '✅' : '❌'],
                ['API Configured', '', $apify->isConfigured() ? '✅ Yes' : '❌ No'],
                ['Rate Limited', '', $apify->isRateLimited() ? '⚠️ Yes' : '✅ No'],
            ]
        );

        $this->newLine();
    }

    /**
     * Clear rate limit cache.
     */
    private function clearRateLimitCache(ApifyService $apify): int
    {
        $this->info('🧹 Clearing rate limit cache...');

        $apify->clearRateLimit();
        Cache::forget('apify_rate_limit');

        $this->info('✅ Rate limit cache cleared!');
        return Command::SUCCESS;
    }

    /**
     * Dispatch ViralHunterJob to queue.
     */
    private function dispatchJob(): int
    {
        $this->info('📤 Dispatching ViralHunterJob...');

        ViralHunterJob::dispatch();

        $this->info('✅ Job dispatched to queue!');
        $this->info('   Run: php artisan queue:work --once');

        return Command::SUCCESS;
    }

    /**
     * Run ViralHunterJob synchronously.
     */
    private function runSync(): int
    {
        $this->info('⚡ Running ViralHunterJob synchronously...');

        ViralHunterJob::dispatchSync();

        $this->info('✅ Job completed!');

        return Command::SUCCESS;
    }

    /**
     * Fetch content using ViralHunterService.
     */
    private function fetchContent(ApifyService $apify, ViralHunterService $viralHunter): int
    {
        $hashtag = $this->option('hashtag') ?? 'businessuz';

        $this->info("🚀 Fetching viral content for #{$hashtag}...");
        $this->newLine();

        if (!$apify->isConfigured()) {
            $this->error('❌ Apify not configured. Set APIFY_TOKEN in .env');
            return Command::FAILURE;
        }

        if ($apify->isRateLimited()) {
            $this->warn('⚠️ API is rate limited. Use --clear to reset.');
            return Command::FAILURE;
        }

        // Fetch using ViralHunterService
        $result = $viralHunter->refreshFeed($hashtag);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Success', $result['success'] ? '✅ Yes' : '❌ No'],
                ['Fetched', $result['fetched'] ?? 0],
                ['New Saved', $result['new'] ?? 0],
                ['Duplicates', $result['duplicates'] ?? 0],
                ['Analyzed', $result['analyzed'] ?? 0],
                ['Errors', $result['errors'] ?? 0],
            ]
        );

        if (!empty($result['error'])) {
            $this->error("Error: {$result['error']}");
        }

        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
    }
}
