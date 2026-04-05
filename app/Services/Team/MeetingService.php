<?php

namespace App\Services\Team;

use App\Models\TeamMeeting;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Jamoa majlis xizmati — ertalabki majlis, kunlik xulosa, haftalik/oylik reja.
 *
 * Gibrid: 80% shablon (bepul), 20% Haiku (faqat muhim xabar bo'lsa)
 */
class MeetingService
{
    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Ertalabki majlis — har bir agent o'z hisobotini bazadan tayyorlaydi
     */
    public function generateMorningStandup(string $businessId): array
    {
        try {
            $date = now()->format('d.m.Y');

            // Har bir agent hisobotini bazadan yig'ish (bepul)
            $reports = [
                'imronbek' => $this->getImronbekReport($businessId),
                'salomatxon' => $this->getSalomatxonReport($businessId),
                'jasurbek' => $this->getJasurbekReport($businessId),
                'nodira' => $this->getNodiraReport($businessId),
            ];

            // Shoshilinch masalalar
            $urgent = $this->getUrgentItems($businessId);

            // Shablon bilan formatlash (bepul)
            $template = file_get_contents(__DIR__ . '/Templates/morning_standup.txt');
            $summary = str_replace(
                ['{date}', '{imronbek_report}', '{salomatxon_report}', '{jasurbek_report}', '{nodira_report}', '{urgent_section}'],
                [$date, $reports['imronbek'], $reports['salomatxon'], $reports['jasurbek'], $reports['nodira'],
                 $urgent ? "\n⚠️ **SHOSHILINCH:** {$urgent}" : ''],
                $template,
            );

            // Saqlash
            $meeting = TeamMeeting::create([
                'business_id' => $businessId,
                'meeting_type' => 'morning_standup',
                'meeting_date' => now()->toDateString(),
                'agent_reports' => $reports,
                'director_summary' => $summary,
                'urgent_items' => $urgent ? [$urgent] : [],
                'ai_tokens_used' => 0,
            ]);

            return ['success' => true, 'summary' => $summary, 'meeting_id' => $meeting->id];

        } catch (\Exception $e) {
            Log::error('MeetingService: standup xatosi', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kunlik xulosa (18:00)
     */
    public function generateDailySummary(string $businessId): array
    {
        try {
            $date = now()->format('d.m.Y');

            // Bazadan kunlik raqamlar (bepul)
            $newLeads = DB::table('leads')->where('business_id', $businessId)
                ->whereDate('created_at', now()->toDateString())->count();
            $orders = DB::table('sales')->where('business_id', $businessId)
                ->whereDate('created_at', now()->toDateString())->count();
            $revenue = (float) DB::table('sales')->where('business_id', $businessId)
                ->whereDate('created_at', now()->toDateString())->sum('amount');
            $callsAnalyzed = DB::table('call_analyses')->where('business_id', $businessId)
                ->whereDate('created_at', now()->toDateString())->count();
            $avgScore = (float) DB::table('call_analyses')->where('business_id', $businessId)
                ->whereDate('created_at', now()->toDateString())->avg('overall_score') ?: 0;

            $template = file_get_contents(__DIR__ . '/Templates/daily_summary.txt');
            $summary = str_replace(
                ['{date}', '{new_leads}', '{orders}', '{revenue}', '{posts}', '{best_type}', '{engagement}',
                 '{daily_revenue}', '{revenue_change}', '{cac}', '{calls_analyzed}', '{avg_score}',
                 '{important_note}', '{top_task}'],
                [$date, $newLeads, $orders, number_format($revenue), 0, '-', '0',
                 number_format($revenue), '', '-', $callsAnalyzed, round($avgScore),
                 '', 'Yangi leadlarga javob berish'],
                $template,
            );

            $meeting = TeamMeeting::create([
                'business_id' => $businessId,
                'meeting_type' => 'daily_summary',
                'meeting_date' => now()->toDateString(),
                'agent_reports' => ['leads' => $newLeads, 'orders' => $orders, 'revenue' => $revenue],
                'director_summary' => $summary,
                'ai_tokens_used' => 0,
            ]);

            return ['success' => true, 'summary' => $summary, 'meeting_id' => $meeting->id];
        } catch (\Exception $e) {
            Log::error('MeetingService: daily summary xatosi', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // === AGENT HISOBOTLARI (bazadan, bepul) ===

    private function getImronbekReport(string $businessId): string
    {
        try {
            $posts = DB::table('instagram_media')
                ->whereIn('account_id', DB::table('instagram_accounts')->where('business_id', $businessId)->pluck('id'))
                ->where('posted_at', '>=', now()->subDay())
                ->count();
            $avgEng = (float) DB::table('instagram_media')
                ->whereIn('account_id', DB::table('instagram_accounts')->where('business_id', $businessId)->pluck('id'))
                ->where('posted_at', '>=', now()->subDay())
                ->avg('engagement_rate') ?: 0;

            return "Kecha {$posts} ta post, o'rtacha engagement " . round($avgEng, 1) . "%.";
        } catch (\Exception $e) {
            return "Kontent ma'lumotlari hozircha mavjud emas.";
        }
    }

    private function getSalomatxonReport(string $businessId): string
    {
        $newLeads = DB::table('leads')->where('business_id', $businessId)
            ->whereDate('created_at', now()->subDay()->toDateString())->count();
        $hotLeads = DB::table('leads')->where('business_id', $businessId)
            ->where('score', '>=', 76)->where('status', 'new')->count();
        $unanswered = DB::table('leads')->where('business_id', $businessId)
            ->where('status', 'new')->where('created_at', '<', now()->subMinutes(30))->count();

        return "Kecha {$newLeads} ta yangi lead. {$hotLeads} ta issiq lead. {$unanswered} ta javobsiz.";
    }

    private function getJasurbekReport(string $businessId): string
    {
        $yesterday = (float) DB::table('sales')->where('business_id', $businessId)
            ->whereDate('created_at', now()->subDay()->toDateString())->sum('amount');
        $dayBefore = (float) DB::table('sales')->where('business_id', $businessId)
            ->whereDate('created_at', now()->subDays(2)->toDateString())->sum('amount');
        $change = $dayBefore > 0 ? round((($yesterday - $dayBefore) / $dayBefore) * 100, 1) : 0;
        $emoji = $change > 0 ? '📈' : ($change < 0 ? '📉' : '➡️');

        return "Kechagi daromad: " . number_format($yesterday) . " so'm ({$emoji}{$change}%).";
    }

    private function getNodiraReport(string $businessId): string
    {
        try {
            $calls = DB::table('call_analyses')->where('business_id', $businessId)
                ->whereDate('created_at', now()->subDay()->toDateString())->count();
            $avg = (float) DB::table('call_analyses')->where('business_id', $businessId)
                ->whereDate('created_at', now()->subDay()->toDateString())->avg('overall_score') ?: 0;

            return "Kecha {$calls} ta qo'ng'iroq tahlil qilindi, o'rtacha {$avg}/100.";
        } catch (\Exception $e) {
            return "Qo'ng'iroq tahlili hozircha mavjud emas.";
        }
    }

    private function getUrgentItems(string $businessId): ?string
    {
        $hotUnanswered = DB::table('leads')
            ->where('business_id', $businessId)
            ->where('score', '>=', 76)
            ->where('status', 'new')
            ->where('created_at', '<', now()->subMinutes(30))
            ->count();

        if ($hotUnanswered > 0) {
            return "{$hotUnanswered} ta issiq lead javobsiz — darhol javob bering!";
        }

        return null;
    }
}
