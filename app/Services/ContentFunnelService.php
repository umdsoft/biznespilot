<?php

namespace App\Services;

use App\Models\ContentPost;
use App\Models\ContentPostLink;
use App\Models\Lead;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContentFunnelService
{
    /**
     * Kontent → Daromad to'liq funnel
     */
    public function getContentToRevenueFunnel(string $businessId, array $filters = []): array
    {
        $query = ContentPost::where('business_id', $businessId);

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $totalContent = $query->count();
        $publishedContent = (clone $query)->where('status', 'published')->count();

        // Published kontentlarning ID lari
        $publishedIds = (clone $query)->where('status', 'published')->pluck('id');

        // Reach va engagement — ContentPostLink dan
        $linkStats = ContentPostLink::whereIn('content_post_id', $publishedIds)
            ->selectRaw('SUM(reach) as total_reach')
            ->selectRaw('SUM(likes + comments + shares + saves) as total_engagements')
            ->selectRaw('AVG(engagement_rate) as avg_engagement')
            ->first();

        $totalReach = (int) ($linkStats->total_reach ?? 0);
        $totalEngagements = (int) ($linkStats->total_engagements ?? 0);
        $avgEngagement = round((float) ($linkStats->avg_engagement ?? 0), 2);

        // Leads — content_post_id orqali
        $contentPostIds = (clone $query)->pluck('id');
        $leadsQuery = Lead::where('business_id', $businessId)
            ->whereIn('content_post_id', $contentPostIds);

        if (! empty($filters['date_from'])) {
            $leadsQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $leadsQuery->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $totalLeads = $leadsQuery->count();
        $qualifiedLeads = (clone $leadsQuery)->whereIn('status', ['qualified', 'proposal', 'negotiation', 'won'])->count();
        $wonLeads = (clone $leadsQuery)->where('status', 'won')->count();
        $totalRevenue = (clone $leadsQuery)->where('status', 'won')->sum('estimated_value');

        // Funnel bosqichlari
        $funnel = [
            [
                'stage' => 'content_created',
                'label' => 'Kontent yaratildi',
                'count' => $totalContent,
                'icon' => 'pencil',
                'color' => 'blue',
            ],
            [
                'stage' => 'published',
                'label' => "E'lon qilindi",
                'count' => $publishedContent,
                'icon' => 'globe',
                'color' => 'indigo',
            ],
            [
                'stage' => 'reached',
                'label' => 'Reach',
                'count' => $totalReach,
                'icon' => 'eye',
                'color' => 'purple',
            ],
            [
                'stage' => 'engaged',
                'label' => 'Engagement',
                'count' => $totalEngagements,
                'icon' => 'heart',
                'color' => 'pink',
            ],
            [
                'stage' => 'leads',
                'label' => 'Lidlar',
                'count' => $totalLeads,
                'icon' => 'user-plus',
                'color' => 'orange',
            ],
            [
                'stage' => 'qualified',
                'label' => 'Kvalifikatsiya',
                'count' => $qualifiedLeads,
                'icon' => 'check-circle',
                'color' => 'yellow',
            ],
            [
                'stage' => 'won',
                'label' => 'Sotuvlar',
                'count' => $wonLeads,
                'revenue' => (float) $totalRevenue,
                'icon' => 'currency-dollar',
                'color' => 'green',
            ],
        ];

        // Dropoff hisoblash
        $dropoffs = [];
        for ($i = 1; $i < count($funnel); $i++) {
            $prev = $funnel[$i - 1]['count'];
            $curr = $funnel[$i]['count'];

            // Reach va Engagement uchun maxsus hisoblash (content count dan emas, reach dan)
            if ($funnel[$i]['stage'] === 'reached') {
                // published → reach — bu foiz emas, shunchaki ko'rsatkich
                $dropoffs[] = [
                    'from' => $funnel[$i - 1]['stage'],
                    'to' => $funnel[$i]['stage'],
                    'rate' => 0,
                    'type' => 'metric', // Oddiy metrika, foiz emas
                ];
                continue;
            }
            if ($funnel[$i]['stage'] === 'engaged') {
                // reach → engagement = engagement rate
                $rate = $totalReach > 0 ? round(($totalEngagements / $totalReach) * 100, 2) : 0;
                $dropoffs[] = [
                    'from' => $funnel[$i - 1]['stage'],
                    'to' => $funnel[$i]['stage'],
                    'rate' => $rate,
                    'type' => 'conversion',
                ];
                continue;
            }
            if ($funnel[$i]['stage'] === 'leads') {
                // engagement → lead
                $rate = $totalReach > 0 ? round(($totalLeads / $totalReach) * 100, 4) : 0;
                $dropoffs[] = [
                    'from' => $funnel[$i - 1]['stage'],
                    'to' => $funnel[$i]['stage'],
                    'rate' => $rate,
                    'type' => 'conversion',
                ];
                continue;
            }

            $rate = $prev > 0 ? round((($prev - $curr) / $prev) * 100, 1) : 0;
            $dropoffs[] = [
                'from' => $funnel[$i - 1]['stage'],
                'to' => $funnel[$i]['stage'],
                'rate' => $rate,
                'type' => 'dropoff',
            ];
        }

        // Bottleneck aniqlash
        $bottleneck = $this->findBottleneck($funnel, $dropoffs);

        return [
            'funnel' => $funnel,
            'dropoffs' => $dropoffs,
            'bottleneck' => $bottleneck,
            'summary' => [
                'total_content' => $totalContent,
                'total_revenue' => (float) $totalRevenue,
                'content_to_lead_rate' => $publishedContent > 0
                    ? round(($totalLeads / $publishedContent) * 100, 2) : 0,
                'lead_to_sale_rate' => $totalLeads > 0
                    ? round(($wonLeads / $totalLeads) * 100, 2) : 0,
                'revenue_per_content' => $publishedContent > 0
                    ? round((float) $totalRevenue / $publishedContent, 0) : 0,
                'avg_engagement_rate' => $avgEngagement,
            ],
        ];
    }

    /**
     * Har bir kontent bo'yicha samaradorlik reytingi
     */
    public function getContentPerformanceRanking(string $businessId, array $filters = []): array
    {
        $query = ContentPost::where('business_id', $businessId)
            ->where('status', 'published');

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $posts = $query->with(['links'])->withCount('leads')->get();

        $ranking = $posts->map(function ($post) {
            $link = $post->links->first();
            $leadsWon = Lead::where('content_post_id', $post->id)
                ->where('status', 'won')
                ->count();
            $revenue = Lead::where('content_post_id', $post->id)
                ->where('status', 'won')
                ->sum('estimated_value');

            return [
                'id' => $post->id,
                'title' => $post->title ?: mb_substr($post->content ?? '', 0, 60) . '...',
                'content_type' => $post->content_type,
                'platform' => $post->platform,
                'published_at' => $post->published_at?->format('d.m.Y'),
                'reach' => (int) ($link->reach ?? 0),
                'engagement_rate' => (float) ($link->engagement_rate ?? 0),
                'likes' => (int) ($link->likes ?? 0),
                'comments' => (int) ($link->comments ?? 0),
                'leads_count' => $post->leads_count,
                'sales_count' => $leadsWon,
                'revenue' => (float) $revenue,
            ];
        })
            ->sortByDesc('leads_count')
            ->values()
            ->take(20)
            ->toArray();

        return $ranking;
    }

    /**
     * Kontent turi bo'yicha konversiya
     */
    public function getConversionByContentType(string $businessId, array $filters = []): array
    {
        $query = ContentPost::where('business_id', $businessId);

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $posts = $query->get()->groupBy('content_type');

        $result = [];
        foreach ($posts as $type => $typePosts) {
            if (! $type) {
                continue;
            }

            $postIds = $typePosts->pluck('id');
            $publishedCount = $typePosts->where('status', 'published')->count();

            // Reach va engagement
            $linkStats = ContentPostLink::whereIn('content_post_id', $postIds)
                ->selectRaw('SUM(reach) as total_reach')
                ->selectRaw('AVG(engagement_rate) as avg_engagement')
                ->first();

            // Leads
            $leadsCount = Lead::where('business_id', $businessId)
                ->whereIn('content_post_id', $postIds)
                ->count();
            $wonCount = Lead::where('business_id', $businessId)
                ->whereIn('content_post_id', $postIds)
                ->where('status', 'won')
                ->count();
            $revenue = Lead::where('business_id', $businessId)
                ->whereIn('content_post_id', $postIds)
                ->where('status', 'won')
                ->sum('estimated_value');

            $result[] = [
                'content_type' => $type,
                'label' => $this->getContentTypeLabel($type),
                'posts_count' => $typePosts->count(),
                'published_count' => $publishedCount,
                'avg_reach' => $publishedCount > 0
                    ? round((int) ($linkStats->total_reach ?? 0) / $publishedCount, 0)
                    : 0,
                'avg_engagement' => round((float) ($linkStats->avg_engagement ?? 0), 2),
                'leads_count' => $leadsCount,
                'sales_count' => $wonCount,
                'revenue' => (float) $revenue,
                'lead_conversion' => $publishedCount > 0
                    ? round(($leadsCount / $publishedCount) * 100, 2)
                    : 0,
                'sale_conversion' => $leadsCount > 0
                    ? round(($wonCount / $leadsCount) * 100, 2)
                    : 0,
            ];
        }

        // Sort by lead_conversion
        usort($result, fn ($a, $b) => $b['lead_conversion'] <=> $a['lead_conversion']);

        return $result;
    }

    /**
     * Haftalik trend: kontent → lid → sotuvlar dinamikasi
     */
    public function getWeeklyTrend(string $businessId, int $weeks = 8): array
    {
        $labels = [];
        $contentData = [];
        $leadsData = [];
        $salesData = [];
        $revenueData = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $labels[] = $weekStart->format('d.m');

            $contentCount = ContentPost::where('business_id', $businessId)
                ->where('status', 'published')
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();

            $contentIds = ContentPost::where('business_id', $businessId)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->pluck('id');

            $leadsCount = Lead::where('business_id', $businessId)
                ->whereIn('content_post_id', $contentIds)
                ->count();

            $wonCount = Lead::where('business_id', $businessId)
                ->whereIn('content_post_id', $contentIds)
                ->where('status', 'won')
                ->count();

            $revenue = Lead::where('business_id', $businessId)
                ->whereIn('content_post_id', $contentIds)
                ->where('status', 'won')
                ->sum('estimated_value');

            $contentData[] = $contentCount;
            $leadsData[] = $leadsCount;
            $salesData[] = $wonCount;
            $revenueData[] = (float) $revenue;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Kontent',
                    'data' => $contentData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Lidlar',
                    'data' => $leadsData,
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Sotuvlar',
                    'data' => $salesData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * Bottleneck tavsiyalar
     */
    public function getBottleneckInsights(array $funnelData): array
    {
        $insights = [];
        $funnel = $funnelData['funnel'] ?? [];
        $summary = $funnelData['summary'] ?? [];

        if (empty($funnel)) {
            return $insights;
        }

        $totalContent = $funnel[0]['count'] ?? 0;
        $published = $funnel[1]['count'] ?? 0;
        $reach = $funnel[2]['count'] ?? 0;
        $leads = $funnel[4]['count'] ?? 0;
        $sales = $funnel[6]['count'] ?? 0;

        // 1. Kontent e'lon qilinmagan
        if ($totalContent > 0 && $published < $totalContent * 0.7) {
            $unpublished = $totalContent - $published;
            $insights[] = [
                'type' => 'warning',
                'stage' => 'content_created → published',
                'title' => "{$unpublished} ta kontent e'lon qilinmagan",
                'description' => "Yaratilgan kontentning " . round(($unpublished / $totalContent) * 100) . "% i hali e'lon qilinmagan.",
                'action' => "Kontentlarni rejali e'lon qiling. Kontent kalendar dan foydalaning.",
            ];
        }

        // 2. Reach past
        if ($published > 0 && $reach > 0 && $reach / $published < 100) {
            $insights[] = [
                'type' => 'warning',
                'stage' => 'published → reach',
                'title' => "Reach past: postga o'rtacha " . round($reach / $published) . " kishi",
                'description' => "Kontentingiz yetarli auditoriyaga yetib bormayapti.",
                'action' => "Hashtag strategiyasini yaxshilang. E'lon vaqtini optimizatsiya qiling. Reklama qo'shing.",
            ];
        }

        // 3. Engagement past
        $avgEngagement = $summary['avg_engagement_rate'] ?? 0;
        if ($published > 0 && $avgEngagement < 2) {
            $insights[] = [
                'type' => 'danger',
                'stage' => 'reach → engagement',
                'title' => "Engagement past: o'rtacha {$avgEngagement}%",
                'description' => "Auditoriyangiz kontentga kam munosabat bildirmoqda.",
                'action' => "Hook ni kuchaytiring. Savollar bering. CTA qo'shing. Video formatdan foydalaning.",
            ];
        }

        // 4. Lead konversiya past
        if ($published > 5 && $leads === 0) {
            $insights[] = [
                'type' => 'danger',
                'stage' => 'engagement → leads',
                'title' => "Kontentdan lid kelmayapti",
                'description' => "{$published} ta kontent e'lon qilingan, lekin birorta ham lid kelmagan.",
                'action' => "Bio link qo'shing. Lead form yarating. Har bir postda aniq CTA bo'lsin. UTM linklar ishlating.",
            ];
        } elseif ($published > 0 && $leads > 0) {
            $rate = $summary['content_to_lead_rate'] ?? 0;
            if ($rate < 5) {
                $insights[] = [
                    'type' => 'warning',
                    'stage' => 'engagement → leads',
                    'title' => "Lead konversiya past: {$rate}%",
                    'description' => "Har 100 ta kontentdan faqat " . round($rate) . " ta lid kelmoqda.",
                    'action' => "Lead magnet taklif qiling. DM ga yozing degan CTA qo'shing.",
                ];
            }
        }

        // 5. Sotuv konversiya past
        if ($leads > 5 && $sales === 0) {
            $insights[] = [
                'type' => 'danger',
                'stage' => 'leads → won',
                'title' => "Lidlar sotuvga aylanmayapti",
                'description' => "{$leads} ta lid kelgan, lekin birorta ham sotuvga aylanmagan.",
                'action' => "Sotuv jarayonini tekshiring. Operator tezligini oshiring. Taklifni kuchaytiring.",
            ];
        } elseif ($leads > 0 && $sales > 0) {
            $saleRate = $summary['lead_to_sale_rate'] ?? 0;
            if ($saleRate < 10) {
                $insights[] = [
                    'type' => 'warning',
                    'stage' => 'leads → won',
                    'title' => "Sotuv konversiya past: {$saleRate}%",
                    'description' => "Har 100 ta liddan faqat " . round($saleRate) . " tasi sotuvga aylanmoqda.",
                    'action' => "Operator treningini o'tkazing. Sotuv skriptlarini yaxshilang.",
                ];
            }
        }

        // 6. Ijobiy natija
        if ($sales > 0) {
            $revenuePerContent = $summary['revenue_per_content'] ?? 0;
            if ($revenuePerContent > 0) {
                $insights[] = [
                    'type' => 'success',
                    'stage' => 'result',
                    'title' => "Har bir kontentdan o'rtacha " . number_format($revenuePerContent, 0, '', ' ') . " so'm",
                    'description' => "{$sales} ta sotuv, jami " . number_format($summary['total_revenue'] ?? 0, 0, '', ' ') . " so'm daromad.",
                    'action' => "Eng samarali kontent turlarini ko'paytiring.",
                ];
            }
        }

        return $insights;
    }

    /**
     * Eng katta bottleneck ni topish
     */
    protected function findBottleneck(array $funnel, array $dropoffs): ?array
    {
        $maxDropoff = null;
        $maxRate = 0;

        foreach ($dropoffs as $dropoff) {
            if ($dropoff['type'] === 'dropoff' && $dropoff['rate'] > $maxRate) {
                $maxRate = $dropoff['rate'];
                $maxDropoff = $dropoff;
            }
        }

        if (! $maxDropoff) {
            return null;
        }

        $messages = [
            'content_created' => "Ko'proq kontent e'lon qiling",
            'published' => "Reach ni oshirish uchun hashtag va vaqtni optimizatsiya qiling",
            'reached' => "Hook va CTA ni yaxshilang — engagement oshadi",
            'engaged' => "Bio link va lead form qo'shing — lidlar ko'payadi",
            'leads' => "Lid sifatini oshiring yoki kvalifikatsiya jarayonini yaxshilang",
            'qualified' => "Sotuv jarayonini tekshiring — ko'proq deal yoping",
        ];

        return [
            'from' => $maxDropoff['from'],
            'to' => $maxDropoff['to'],
            'rate' => $maxRate,
            'message' => $messages[$maxDropoff['from']] ?? 'Bu bosqichni optimizatsiya qiling',
        ];
    }

    protected function getContentTypeLabel(string $type): string
    {
        return match ($type) {
            'educational' => "Ta'limiy",
            'promotional' => 'Reklama',
            'inspirational' => 'Ilhomlantiruvchi',
            'entertaining' => "Ko'ngil ochar",
            'behind_scenes' => 'Sahna ortidan',
            'ugc' => 'UGC',
            default => ucfirst($type),
        };
    }
}
