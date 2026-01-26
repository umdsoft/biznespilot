<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SocialTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CheckAllTokensJob - Kundalik Token Salomatligi Tekshiruvi
 *
 * Bu Job har kuni tunda 03:00 da ishlaydi va:
 * - Barcha Facebook/Instagram tokenlarni tekshiradi
 * - Eskirayotgan tokenlarni avtomatik yangilaydi
 * - Muammoli tokenlar uchun adminni ogohlantiradi
 *
 * SELF-HEALING: Token 7 kundan kam vaqt qolsa, avtomatik yangilanadi.
 *
 * Scheduling: routes/console.php da Schedule::job qo'shilgan
 */
class CheckAllTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(SocialTokenService $tokenService): void
    {
        Log::info('CheckAllTokensJob: Starting token health check');

        $integrations = $tokenService->getMetaIntegrations();

        if ($integrations->isEmpty()) {
            Log::info('CheckAllTokensJob: No Meta integrations found');

            return;
        }

        Log::info('CheckAllTokensJob: Checking tokens', [
            'total_integrations' => $integrations->count(),
        ]);

        $stats = [
            'total' => $integrations->count(),
            'healthy' => 0,
            'refreshed' => 0,
            'expired' => 0,
            'failed' => 0,
        ];

        foreach ($integrations as $integration) {
            try {
                $result = $tokenService->checkAndRefreshIfNeeded($integration);

                // Statistikani yangilash
                match ($result['status']) {
                    'healthy' => $stats['healthy']++,
                    'refreshed' => $stats['refreshed']++,
                    'expired' => $stats['expired']++,
                    default => $stats['failed']++,
                };

                Log::info('CheckAllTokensJob: Integration checked', [
                    'integration_id' => $integration->id,
                    'business_id' => $integration->business_id,
                    'status' => $result['status'],
                    'action' => $result['action_taken'],
                ]);

            } catch (\Exception $e) {
                $stats['failed']++;

                Log::error('CheckAllTokensJob: Integration check failed', [
                    'integration_id' => $integration->id,
                    'error' => $e->getMessage(),
                ]);

                // Xatolik bo'lsa ham davom etamiz (boshqa integratsiyalar uchun)
                continue;
            }
        }

        Log::info('CheckAllTokensJob: Completed', [
            'stats' => $stats,
        ]);

        // Agar ko'p xatolik bo'lsa, umumiy ogohlantirish
        if ($stats['expired'] > 0 || $stats['failed'] > 0) {
            Log::warning('CheckAllTokensJob: Issues detected', [
                'expired_count' => $stats['expired'],
                'failed_count' => $stats['failed'],
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckAllTokensJob: Job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
