<?php

namespace App\Jobs;

use App\Models\TelegramBot;
use App\Models\TelegramFunnelStep;
use App\Models\TelegramUser;
use App\Services\Telegram\FunnelEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Funnel delay step uchun async step-execution job.
 *
 * Avvalgi flow: `executeDelayStep` → `usleep(N ms)` → `goToStep(next)` —
 *  bu PHP-FPM webhook worker'ni 10s'gacha bloklashi mumkin edi.
 *
 * Yangi flow: `executeDelayStep` shu job'ni `->delay(seconds)` bilan dispatch
 *  qiladi va darhol qaytadi. Vaqt o'tgach worker job'ni oladi, yangi
 *  FunnelEngineService instansiyasini tuzadi va `goToStep(targetStepId)`
 *  chaqiradi — user'ga xabar o'sha paytda yuboriladi.
 *
 * Idempotency: job ikki marta ishlab ketsa (retry yoki Redis duplikat), user_state.current_step_id
 *  tekshiriladi — agar allaqachon oldinga o'tib ketgan bo'lsa, no-op.
 */
class ExecuteFunnelStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(
        public string $botId,
        public string $telegramUserId,
        public string $targetStepId,
        public ?string $expectedCurrentStepId = null,
        public ?string $businessConnectionId = null,
    ) {
    }

    public function handle(): void
    {
        $bot = TelegramBot::find($this->botId);
        if (! $bot) {
            Log::warning('ExecuteFunnelStepJob: bot not found', ['bot_id' => $this->botId]);
            return;
        }

        $user = TelegramUser::find($this->telegramUserId);
        if (! $user) {
            Log::warning('ExecuteFunnelStepJob: user not found', ['user_id' => $this->telegramUserId]);
            return;
        }

        // Target step faqat shu bot'ga tegishli bo'lsin (cross-tenant guard).
        $step = TelegramFunnelStep::whereHas('funnel', function ($q) {
            $q->where('telegram_bot_id', $this->botId);
        })->find($this->targetStepId);

        if (! $step) {
            Log::warning('ExecuteFunnelStepJob: step not found or cross-tenant', [
                'step_id' => $this->targetStepId,
                'bot_id' => $this->botId,
            ]);
            return;
        }

        // Idempotency — agar user allaqachon boshqa step'ga o'tgan bo'lsa, ortga qaytmaymiz.
        if ($this->expectedCurrentStepId) {
            $state = $user->state ?? null;
            if ($state && $state->current_step_id !== $this->expectedCurrentStepId) {
                Log::info('ExecuteFunnelStepJob: skipped (user already advanced)', [
                    'expected' => $this->expectedCurrentStepId,
                    'actual' => $state->current_step_id,
                ]);
                return;
            }
        }

        // Engine bilan step'ni bajaramiz.
        $engine = new FunnelEngineService($bot, $user);
        if ($this->businessConnectionId) {
            $engine->setBusinessConnection($this->businessConnectionId);
        }
        $engine->executeDeferredStep($this->targetStepId);
    }
}
