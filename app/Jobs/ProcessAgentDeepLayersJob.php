<?php

namespace App\Jobs;

use App\Services\Agent\OrchestratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Agent Layer 3+4 (secondary, merger, director) ni background'da ishlash.
 *
 * Dispatch usuli: dispatchAfterResponse() — PHP-FPM response'ni clientga
 * jo'natgandan keyin background'da ishlaydi (queue worker shart emas).
 *
 * Foydalanuvchi POST javobini (L1+L2) darhol oladi, keyin frontend
 * polling qilib (GET /api/v1/agent/job/{id}) L3+L4 natijalarini oladi.
 */
class ProcessAgentDeepLayersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout (Laravel queue konteksti uchun).
     */
    public int $timeout = 120;

    /**
     * Faqat 1 marta urinish (idempotent emas).
     */
    public int $tries = 1;

    public function __construct(
        public string $jobId,
        public string $message,
        public string $businessId,
        public string $conversationId,
        public array $routing,
    ) {}

    public function handle(OrchestratorService $orchestrator): void
    {
        $orchestrator->handleDeepLayers(
            $this->jobId,
            $this->message,
            $this->businessId,
            $this->conversationId,
            $this->routing,
        );
    }
}
