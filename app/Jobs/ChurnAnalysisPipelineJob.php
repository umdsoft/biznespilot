<?php

namespace App\Jobs;

use App\Jobs\Marketing\CalculateChurnRiskJob;
use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ChurnAnalysisPipelineJob — Churn tahlil va oldini olish pipeline
 *
 * Quyidagilarni o'z ichiga oladi:
 * 1. CalculateChurnRiskJob — Barcha mijozlar uchun churn xavfini hisoblash
 * 2. ChurnPreventionJob — Xavfli mijozlar uchun retention harakatlar
 *
 * Har kuni 07:00 da ishga tushiriladi (analytics queue)
 */
class ChurnAnalysisPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 900;

    public function handle(): void
    {
        Log::info('ChurnAnalysisPipelineJob: Starting churn analysis pipeline');

        // 1. Churn risk hisoblash (sinxron — natijalar keyin kerak)
        try {
            CalculateChurnRiskJob::dispatchSync();
        } catch (\Exception $e) {
            Log::error('ChurnAnalysisPipelineJob: Churn risk calculation failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // 2. Churn prevention — risk hisoblangandan keyin
        $dispatched = 0;
        Business::where('status', 'active')->chunk(100, function ($businesses) use (&$dispatched) {
            foreach ($businesses as $business) {
                try {
                    ChurnPreventionJob::dispatch($business)->onQueue('analytics');
                    $dispatched++;
                } catch (\Exception $e) {
                    Log::error('ChurnAnalysisPipelineJob: Prevention dispatch failed', [
                        'business_id' => $business->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('ChurnAnalysisPipelineJob: Pipeline completed', [
            'businesses_dispatched' => $dispatched,
        ]);
    }

    public function tags(): array
    {
        return ['churn-analysis-pipeline'];
    }
}
