<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\TeamPlan;
use App\Services\AI\AIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Haftalik reja — har dushanba 09:00.
 * php artisan team:weekly-planning
 */
class TeamWeeklyPlanning extends Command
{
    protected $signature = 'team:weekly-planning';
    protected $description = 'Barcha bizneslar uchun haftalik reja yaratish';

    public function handle(AIService $aiService): int
    {
        $this->info('Haftalik reja yaratilmoqda...');

        $businesses = Business::all();

        foreach ($businesses as $business) {
            try {
                // Bazadan o'tgan hafta raqamlari (bepul)
                $weekStart = now()->subWeek()->startOfWeek()->toDateString();
                $weekEnd = now()->subWeek()->endOfWeek()->toDateString();

                $results = [
                    'revenue' => (float) DB::table('sales')->where('business_id', $business->id)
                        ->whereBetween('created_at', [$weekStart, $weekEnd . ' 23:59:59'])->sum('amount'),
                    'leads' => DB::table('leads')->where('business_id', $business->id)
                        ->whereBetween('created_at', [$weekStart, $weekEnd . ' 23:59:59'])->count(),
                    'orders' => DB::table('sales')->where('business_id', $business->id)
                        ->whereBetween('created_at', [$weekStart, $weekEnd . ' 23:59:59'])->count(),
                ];

                // Haiku bilan qisqa reja (~400 token)
                $prompt = "O'tgan hafta: daromad " . number_format($results['revenue']) . " so'm, "
                    . "{$results['leads']} lead, {$results['orders']} buyurtma. "
                    . "Kelasi hafta uchun 3 ta aniq vazifa ber.";

                $aiResponse = $aiService->ask(
                    prompt: $prompt,
                    systemPrompt: "Sen Umidbek (Rahbar) — jamoa boshqaruvchisisan. Haftalik reja tuz. 3 ta aniq vazifa. O'zbek tilida, qisqa.",
                    preferredModel: 'haiku',
                    maxTokens: 400,
                    businessId: $business->id,
                    agentType: 'team_planning',
                );

                TeamPlan::create([
                    'business_id' => $business->id,
                    'plan_type' => 'weekly',
                    'period_start' => now()->startOfWeek()->toDateString(),
                    'period_end' => now()->endOfWeek()->toDateString(),
                    'previous_results' => $results,
                    'agent_suggestions' => [],
                    'final_plan' => ['summary' => $aiResponse->content],
                    'ai_tokens_used' => $aiResponse->tokensInput + $aiResponse->tokensOutput,
                ]);

                $this->line("  ✓ {$business->name}");

            } catch (\Exception $e) {
                Log::warning("WeeklyPlanning: {$business->id} xato", ['error' => $e->getMessage()]);
            }
        }

        $this->info('Tayyor.');
        return self::SUCCESS;
    }
}
