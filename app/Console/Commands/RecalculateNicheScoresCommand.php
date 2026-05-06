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
                // Service `processed`/`updated`/`errors` qaytaradi (industries_processed emas).
                $processed = $stats['processed'] ?? 0;
                $updated = $stats['updated'] ?? 0;
                $errors = $stats['errors'] ?? 0;
                $this->info("Yakunlandi: {$processed} ta soha qayta ishlandi, {$updated} ta yangilandi, {$errors} ta xato");
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
