<?php

namespace App\Console\Commands;

use App\Services\ContentAI\CrossBusinessLearningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecalculateNicheScoresCommand extends Command
{
    protected $signature = 'content-ai:recalculate-niche-scores {--industry= : Faqat bitta soha uchun}';

    protected $description = 'Soha bo\'yicha niche topic score larni qayta hisoblash (Jamoaviy Aql)';

    public function handle(CrossBusinessLearningService $service): int
    {
        $industryId = $this->option('industry');

        $this->info('Niche topic score larni qayta hisoblash boshlanmoqda...');

        try {
            if ($industryId) {
                $this->info("Soha ID: {$industryId}");
                $updated = $service->recalculateForIndustry($industryId);
                $this->info("Yakunlandi: {$updated} ta mavzu yangilandi");
            } else {
                $stats = $service->recalculateAllScores();
                $this->info("Yakunlandi: {$stats['industries_processed']} ta soha, {$stats['topics_updated']} ta mavzu yangilandi");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Xatolik: '.$e->getMessage());
            Log::error('RecalculateNicheScoresCommand failed', [
                'industry_id' => $industryId,
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
