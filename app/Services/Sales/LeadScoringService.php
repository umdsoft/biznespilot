<?php

namespace App\Services\Sales;

use App\Events\LeadScoreUpdated;
use App\Models\Lead;
use App\Models\LeadScoreHistory;
use App\Models\SalesLeadScoringRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadScoringService
{
    /**
     * Score kategoriyalari
     */
    public const CATEGORY_HOT = 'hot';

    public const CATEGORY_WARM = 'warm';

    public const CATEGORY_COOL = 'cool';

    public const CATEGORY_COLD = 'cold';

    public const CATEGORY_FROZEN = 'frozen';

    public const CATEGORIES = [
        self::CATEGORY_HOT => ['min' => 80, 'max' => 100, 'name' => 'Issiq', 'color' => '#ef4444'],
        self::CATEGORY_WARM => ['min' => 60, 'max' => 79, 'name' => 'Iliq', 'color' => '#f97316'],
        self::CATEGORY_COOL => ['min' => 40, 'max' => 59, 'name' => 'Salqin', 'color' => '#eab308'],
        self::CATEGORY_COLD => ['min' => 20, 'max' => 39, 'name' => 'Sovuq', 'color' => '#3b82f6'],
        self::CATEGORY_FROZEN => ['min' => 0, 'max' => 19, 'name' => 'Muzlagan', 'color' => '#6b7280'],
    ];

    /**
     * Lead score ni hisoblash
     */
    public function calculateScore(Lead $lead): int
    {
        $score = 50; // Boshlang'ich ball
        $rules = $this->getScoringRules($lead->business_id);

        foreach ($rules as $rule) {
            $score += $rule->evaluate($lead);
        }

        // 0-100 oralig'ida cheklash
        return max(0, min(100, $score));
    }

    /**
     * Lead category ni aniqlash
     */
    public function getLeadCategory(int $score): string
    {
        foreach (self::CATEGORIES as $key => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $key;
            }
        }

        return self::CATEGORY_FROZEN;
    }

    /**
     * Kategoriya ma'lumotlarini olish
     */
    public function getCategoryInfo(string $category): array
    {
        return self::CATEGORIES[$category] ?? self::CATEGORIES[self::CATEGORY_FROZEN];
    }

    /**
     * Lead uchun to'liq scoring natijasini olish
     */
    public function getScoreDetails(Lead $lead): array
    {
        $rules = $this->getScoringRules($lead->business_id);
        $breakdown = [];
        $totalScore = 50; // Boshlang'ich ball

        foreach ($rules as $rule) {
            $points = $rule->evaluate($lead);
            $totalScore += $points;

            $breakdown[] = [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'category' => $rule->category,
                'category_info' => $rule->category_info,
                'points_possible' => $rule->points,
                'points_earned' => $points,
                'applied' => $points !== 0,
            ];
        }

        $finalScore = max(0, min(100, $totalScore));
        $category = $this->getLeadCategory($finalScore);

        return [
            'score' => $finalScore,
            'category' => $category,
            'category_label' => $this->getCategoryLabel($category),
            'category_color' => $this->getCategoryColor($category),
            'base_score' => 50,
            'breakdown' => $breakdown,
            'positive_points' => collect($breakdown)->where('points_earned', '>', 0)->sum('points_earned'),
            'negative_points' => collect($breakdown)->where('points_earned', '<', 0)->sum('points_earned'),
        ];
    }

    /**
     * Scoring qoidalarini olish
     */
    protected function getScoringRules(string $businessId): \Illuminate\Database\Eloquent\Collection
    {
        return SalesLeadScoringRule::forBusiness($businessId)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Lead score ni yangilash va saqlash
     */
    public function updateLeadScore(Lead $lead, string $reason = LeadScoreHistory::REASON_RECALCULATED): array
    {
        $oldScore = $lead->score ?? 50;
        $oldCategory = $lead->score_category ?? 'warm';

        $scoreDetails = $this->getScoreDetails($lead);
        $newScore = $scoreDetails['score'];
        $newCategory = $scoreDetails['category'];

        // updateQuietly - observer ni qayta ishga tushirmaslik uchun
        $lead->updateQuietly([
            'score' => $newScore,
            'score_category' => $newCategory,
            'score_breakdown' => $scoreDetails['breakdown'],
            'scored_at' => now(),
        ]);

        $changed = $oldScore !== $newScore;

        // Tarix yozish (agar o'zgargan bo'lsa)
        if ($changed) {
            LeadScoreHistory::log(
                $lead,
                $oldScore,
                $newScore,
                $oldCategory,
                $newCategory,
                $reason,
                ['breakdown' => $scoreDetails['breakdown']]
            );

            // Event dispatch
            event(new LeadScoreUpdated($lead, $oldScore, $newScore, $oldCategory, $newCategory));
        }

        Log::debug('LeadScoringService: Score updated', [
            'lead_id' => $lead->id,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'changed' => $changed,
        ]);

        return [
            'score' => $newScore,
            'category' => $newCategory,
            'changed' => $changed,
            'old_score' => $oldScore,
            'breakdown' => $scoreDetails['breakdown'],
        ];
    }

    /**
     * Yangi lead uchun scoring
     */
    public function scoreNewLead(Lead $lead): array
    {
        return $this->updateLeadScore($lead, LeadScoreHistory::REASON_INITIAL);
    }

    /**
     * Ma'lumot yangilanganda scoring
     */
    public function scoreOnDataUpdate(Lead $lead): array
    {
        return $this->updateLeadScore($lead, LeadScoreHistory::REASON_DATA_UPDATED);
    }

    /**
     * Faollik qo'shilganda scoring
     */
    public function scoreOnActivity(Lead $lead): array
    {
        $lead->updateQuietly(['last_engagement_at' => now()]);

        return $this->updateLeadScore($lead, LeadScoreHistory::REASON_ACTIVITY_CREATED);
    }

    /**
     * Qo'ng'iroq qilinganda scoring
     */
    public function scoreOnCall(Lead $lead): array
    {
        $lead->updateQuietly(['last_engagement_at' => now()]);

        return $this->updateLeadScore($lead, LeadScoreHistory::REASON_CALL_MADE);
    }

    /**
     * Vazifa bajarilganda scoring
     */
    public function scoreOnTaskCompleted(Lead $lead): array
    {
        $lead->updateQuietly(['last_engagement_at' => now()]);

        return $this->updateLeadScore($lead, LeadScoreHistory::REASON_TASK_COMPLETED);
    }

    /**
     * Bosqich o'zgarganda scoring
     */
    public function scoreOnStageChanged(Lead $lead): array
    {
        return $this->updateLeadScore($lead, LeadScoreHistory::REASON_STAGE_CHANGED);
    }

    /**
     * Score decay (kunlik job uchun)
     */
    public function applyScoreDecay(Lead $lead, int $decayPoints = 2): array
    {
        $oldScore = $lead->score ?? 50;
        $oldCategory = $lead->score_category ?? 'warm';

        if ($oldScore <= 0) {
            return ['score' => 0, 'changed' => false];
        }

        // Oxirgi faollikdan beri 7+ kun o'tganmi
        $lastEngagement = $lead->last_engagement_at ?? $lead->last_contacted_at ?? $lead->created_at;

        if ($lastEngagement && $lastEngagement->diffInDays(now()) >= 7) {
            $newScore = max(0, $oldScore - $decayPoints);
            $newCategory = $this->getLeadCategory($newScore);

            $lead->updateQuietly([
                'score' => $newScore,
                'score_category' => $newCategory,
                'scored_at' => now(),
            ]);

            LeadScoreHistory::log(
                $lead,
                $oldScore,
                $newScore,
                $oldCategory,
                $newCategory,
                LeadScoreHistory::REASON_DECAY,
                ['decay_points' => $decayPoints, 'days_inactive' => $lastEngagement->diffInDays(now())],
                LeadScoreHistory::TRIGGERED_BY_SCHEDULED
            );

            return [
                'score' => $newScore,
                'category' => $newCategory,
                'changed' => true,
                'old_score' => $oldScore,
            ];
        }

        return ['score' => $oldScore, 'changed' => false];
    }

    /**
     * Lead uchun scoring tarixini olish
     */
    public function getLeadHistory(string $leadId, int $limit = 20): \Illuminate\Support\Collection
    {
        return LeadScoreHistory::forLead($leadId)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Batch update - barcha leadlar uchun
     * Scheduler orqali chaqiriladi
     */
    public function recalculateAllScores(string $businessId): array
    {
        $results = [
            'total' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        Lead::where('business_id', $businessId)
            ->whereNull('lost_reason')
            ->chunk(100, function ($leads) use (&$results) {
                foreach ($leads as $lead) {
                    $results['total']++;

                    try {
                        $result = $this->updateLeadScore($lead, LeadScoreHistory::REASON_RECALCULATED);

                        if ($result['changed']) {
                            $results['updated']++;
                        }
                    } catch (\Exception $e) {
                        $results['errors']++;
                        Log::error('Lead scoring failed', [
                            'lead_id' => $lead->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        // Cache ni tozalash
        Cache::forget("lead_scoring_rules:{$businessId}");

        Log::info('LeadScoringService: Batch recalculation completed', [
            'business_id' => $businessId,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Kategory label
     */
    public function getCategoryLabel(string $category): string
    {
        return self::CATEGORIES[$category]['name'] ?? $category;
    }

    /**
     * Kategory rang
     */
    public function getCategoryColor(string $category): string
    {
        return self::CATEGORIES[$category]['color'] ?? '#6b7280';
    }

    /**
     * Scoring statistikalarini olish
     */
    public function getScoreDistribution(string $businessId): array
    {
        $leads = Lead::where('business_id', $businessId)
            ->whereNull('lost_reason')
            ->select('score', 'score_category')
            ->get();

        $categoryStats = [];
        foreach (self::CATEGORIES as $key => $info) {
            $categoryStats[$key] = [
                'count' => $leads->where('score_category', $key)->count(),
                'name' => $info['name'],
                'color' => $info['color'],
            ];
        }

        return [
            'total' => $leads->count(),
            'categories' => $categoryStats,
            'average_score' => round($leads->avg('score') ?? 0, 1),
            'score_ranges' => [
                '0-19' => $leads->whereBetween('score', [0, 19])->count(),
                '20-39' => $leads->whereBetween('score', [20, 39])->count(),
                '40-59' => $leads->whereBetween('score', [40, 59])->count(),
                '60-79' => $leads->whereBetween('score', [60, 79])->count(),
                '80-100' => $leads->whereBetween('score', [80, 100])->count(),
            ],
        ];
    }

    /**
     * Lead uchun scoring tavsiyalar
     */
    public function getScoringRecommendations(Lead $lead): array
    {
        $recommendations = [];
        $rules = $this->getScoringRules($lead->business_id);

        foreach ($rules as $rule) {
            // Faqat ijobiy qoidalar uchun tavsiya berish
            if ($rule->points <= 0) {
                continue;
            }

            $points = $rule->evaluate($lead);

            // Agar qoida ishlamagan bo'lsa
            if ($points === 0) {
                $recommendations[] = [
                    'rule_name' => $rule->name,
                    'potential_points' => $rule->points,
                    'suggestion' => $this->getSuggestionForRule($rule),
                    'priority' => $rule->points >= 15 ? 'high' : ($rule->points >= 10 ? 'medium' : 'low'),
                ];
            }
        }

        // Points bo'yicha tartiblash (yuqori potensial birinchi)
        usort($recommendations, fn ($a, $b) => $b['potential_points'] <=> $a['potential_points']);

        return array_slice($recommendations, 0, 5); // Top 5 tavsiya
    }

    /**
     * Qoida uchun tavsiya matni
     */
    protected function getSuggestionForRule(SalesLeadScoringRule $rule): string
    {
        return match ($rule->field) {
            'phone' => 'Telefon raqamini qo\'shing',
            'email' => 'Email manzilini qo\'shing',
            'company' => 'Kompaniya nomini qo\'shing',
            'estimated_value' => 'Taxminiy qiymatni kiriting',
            'activities_count' => 'Lid bilan faoliyat qiling (qo\'ng\'iroq, uchrashuv)',
            default => "{$rule->name} shartini bajaring",
        };
    }
}
