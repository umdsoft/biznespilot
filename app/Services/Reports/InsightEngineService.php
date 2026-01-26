<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\Lead;
use App\Models\LostOpportunity;
use App\Models\Order;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * InsightEngineService - Actionable Financial Insights Generator
 *
 * "Black Box" konsepsiyasi: Quruq statistika emas, HARAKATGA CHORLASH.
 *
 * Har bir insight:
 * - Muammo: Nima yuz berdi?
 * - Ta'sir: Qancha pul yo'qotildi/topildi?
 * - Harakat: Nima qilish kerak?
 */
class InsightEngineService
{
    // ==========================================
    // TELEGRAM DAILY BRIEF GENERATION
    // ==========================================

    /**
     * Generate full daily brief for Telegram (Markdown format).
     */
    public function generateDailyBrief(string $businessId, ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::yesterday();
        $business = Business::find($businessId);

        if (!$business) {
            return "❌ Biznes topilmadi";
        }

        $brief = $this->buildHeader($business, $date);
        $brief .= $this->buildBleedingMoneySection($businessId, $date);
        $brief .= $this->buildMarketingTruthSection($businessId, $date);
        $brief .= $this->buildActionableTasksSection($businessId, $date);
        $brief .= $this->buildQuickWinsSection($businessId, $date);
        $brief .= $this->buildFooter($date);

        Log::info('InsightEngineService: Daily brief generated', [
            'business_id' => $businessId,
            'date' => $date->toDateString(),
            'length' => strlen($brief),
        ]);

        return $brief;
    }

    /**
     * Header section.
     */
    protected function buildHeader(Business $business, Carbon $date): string
    {
        $dayName = $this->getUzbekDayName($date->dayOfWeek);
        $formattedDate = $date->format('d.m.Y');

        return "🌅 *ERTALABKI BRIEF*\n"
            . "📅 {$dayName}, {$formattedDate}\n"
            . "🏢 {$business->name}\n"
            . "━━━━━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * Section 1: BLEEDING MONEY (Yo'qotilgan pul)
     *
     * Lost Opportunities jadvalidan ma'lumot oladi.
     */
    protected function buildBleedingMoneySection(string $businessId, Carbon $date): string
    {
        $section = "💸 *YO'QOTILGAN IMKONIYATLAR*\n\n";

        // Get yesterday's lost opportunities
        $lostOpportunities = LostOpportunity::where('business_id', $businessId)
            ->whereDate('lost_at', $date)
            ->get();

        if ($lostOpportunities->isEmpty()) {
            $section .= "✅ Kecha hech qanday lid yo'qotilmadi!\n\n";
            return $section;
        }

        $totalLost = $lostOpportunities->sum('estimated_value');
        $totalWasted = $lostOpportunities->sum('acquisition_cost');
        $count = $lostOpportunities->count();

        // Main insight
        $section .= "⚠️ *Kecha yo'qotildi:*\n";
        $section .= "• Potensial daromad: *" . $this->formatMoney($totalLost) . "*\n";
        $section .= "• Marketing xarajati (isrof): *" . $this->formatMoney($totalWasted) . "*\n";
        $section .= "• Jami: *{$count} ta lid*\n\n";

        // Group by reason
        $byReason = $lostOpportunities->groupBy('lost_reason');
        $topReason = $byReason->sortByDesc(fn($group) => $group->count())->keys()->first();
        $topReasonLabel = LostOpportunity::LOST_REASONS[$topReason] ?? $topReason;
        $topReasonCount = $byReason->get($topReason)->count();

        $section .= "📊 *Asosiy sabab:* {$topReasonLabel} ({$topReasonCount} ta)\n";

        // Group by user (who lost)
        $byUser = $lostOpportunities->groupBy('assigned_to')->filter(fn($v, $k) => $k !== null);
        if ($byUser->isNotEmpty()) {
            $worstUser = $byUser->sortByDesc(fn($group) => $group->sum('estimated_value'))->first();
            $userId = $worstUser->first()->assigned_to;
            $user = User::find($userId);
            $userName = $user?->name ?? 'Noma\'lum';
            $userLostValue = $worstUser->sum('estimated_value');

            $section .= "👤 *Eng ko'p yo'qotgan:* {$userName} (" . $this->formatMoney($userLostValue) . ")\n";
        }

        // Group by channel (which marketing channel is losing)
        $byChannel = $lostOpportunities->groupBy('marketing_channel_id')->filter(fn($v, $k) => $k !== null);
        if ($byChannel->isNotEmpty()) {
            $worstChannel = $byChannel->sortByDesc(fn($group) => $group->count())->first();
            $channelName = $worstChannel->first()->marketingChannel?->name ?? 'Noma\'lum';
            $channelCount = $worstChannel->count();

            $section .= "📢 *Muammoli kanal:* {$channelName} ({$channelCount} ta yo'qotildi)\n";
        }

        // Actionable insight
        $section .= "\n💡 *Tavsiya:* ";
        $section .= $this->getRecommendationForLostReason($topReason);
        $section .= "\n\n";

        return $section;
    }

    /**
     * Get recommendation based on lost reason.
     */
    protected function getRecommendationForLostReason(?string $reason): string
    {
        return match($reason) {
            'price' => "Narx siyosatini qayta ko'rib chiqing yoki qiymat propositionni kuchaytiring.",
            'competitor' => "Raqobatchilar tahlilini o'tkazing. Ular nimani yaxshi qilishyapti?",
            'no_response' => "Follow-up tizimini kuchaytiring. 24 soat ichida min 3 marta aloqa.",
            'no_budget' => "To'lov bo'lib-bo'lib olish yoki chegirma takliflarini ko'rib chiqing.",
            'no_need' => "Lid kvalifikatsiyasini yaxshilang - sifatsiz lidlarga vaqt sarflamang.",
            'timing' => "Lead nurturing kampaniyasini ishga tushiring - qayta aloqada bo'ling.",
            default => "Lid kvalifikatsiyasini yaxshilang - sifatsiz lidlarga vaqt sarflamang.",
        };
    }

    /**
     * Section 2: MARKETING TRUTH (Marketing ROI)
     */
    protected function buildMarketingTruthSection(string $businessId, Carbon $date): string
    {
        $section = "📈 *MARKETING HAQIQATI*\n\n";

        // Get yesterday's orders with attribution
        $orders = Order::where('business_id', $businessId)
            ->whereDate('ordered_at', $date)
            ->get();

        // Get yesterday's sales with attribution
        $sales = Sale::where('business_id', $businessId)
            ->whereDate('sale_date', $date)
            ->get();

        $totalRevenue = $orders->sum('total_amount') + $sales->sum('amount');

        if ($totalRevenue == 0 && $orders->isEmpty() && $sales->isEmpty()) {
            $section .= "📭 Kecha sotuvlar ro'yxatga olinmadi.\n\n";
            return $section;
        }

        $section .= "💰 *Kecha jami daromad:* " . $this->formatMoney($totalRevenue) . "\n\n";

        // Group orders by utm_source
        $ordersBySource = $orders->whereNotNull('utm_source')
            ->groupBy('utm_source')
            ->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('total_amount'),
            ])
            ->sortByDesc('revenue');

        // Group sales by channel
        $salesByChannel = $sales->whereNotNull('marketing_channel_id')
            ->groupBy('marketing_channel_id')
            ->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('amount'),
                'channel_name' => $group->first()->marketingChannel?->name ?? 'Noma\'lum',
            ])
            ->sortByDesc('revenue');

        // Best performing channel
        if ($ordersBySource->isNotEmpty()) {
            $bestSource = $ordersBySource->keys()->first();
            $bestSourceData = $ordersBySource->first();
            $section .= "✅ *Eng yaxshi kanal:* {$bestSource}\n";
            $section .= "   Daromad: *" . $this->formatMoney($bestSourceData['revenue']) . "*\n";
            $section .= "   Buyurtmalar: *{$bestSourceData['count']} ta*\n\n";
        } elseif ($salesByChannel->isNotEmpty()) {
            $bestChannel = $salesByChannel->first();
            $section .= "✅ *Eng yaxshi kanal:* {$bestChannel['channel_name']}\n";
            $section .= "   Daromad: *" . $this->formatMoney($bestChannel['revenue']) . "*\n";
            $section .= "   Sotuvlar: *{$bestChannel['count']} ta*\n\n";
        }

        // Orders without attribution (problem!)
        if ($orders->isNotEmpty()) {
            $ordersWithoutAttribution = $orders->filter(fn($o) => !$o->hasAttribution())->count();
            if ($ordersWithoutAttribution > 0) {
                $percentage = round(($ordersWithoutAttribution / $orders->count()) * 100);
                $section .= "⚠️ *Muammo:* {$ordersWithoutAttribution} ta buyurtma ({$percentage}%) ";
                $section .= "marketing manbasiz! UTM tracking ni tekshiring.\n\n";
            }
        }

        // Channel breakdown
        if ($ordersBySource->count() > 1) {
            $section .= "📊 *Kanallar bo'yicha:*\n";
            foreach ($ordersBySource->take(5) as $source => $data) {
                $section .= "• {$source}: " . $this->formatMoney($data['revenue']) . " ({$data['count']} ta)\n";
            }
            $section .= "\n";
        }

        return $section;
    }

    /**
     * Section 3: ACTIONABLE TASKS (Cold Leads)
     */
    protected function buildActionableTasksSection(string $businessId, Carbon $date): string
    {
        $section = "🔥 *SHOSHILINCH VAZIFALAR*\n\n";

        $tasks = [];

        // 1. Cold leads (new status, created > 24h ago, not touched)
        $coldLeads = Lead::where('business_id', $businessId)
            ->where('status', 'new')
            ->where('created_at', '<', $date->copy()->subDay())
            ->whereNull('last_contacted_at')
            ->count();

        if ($coldLeads > 0) {
            $tasks[] = [
                'priority' => 1,
                'icon' => '🧊',
                'message' => "*{$coldLeads} ta lid sovumoqda!*\n   24+ soatdan beri aloqa yo'q. Hoziroq qo'ng'iroq qiling.",
            ];
        }

        // 2. Leads in negotiation stage for too long
        $stuckLeads = Lead::where('business_id', $businessId)
            ->whereIn('status', ['negotiation', 'proposal'])
            ->where('stage_changed_at', '<', $date->copy()->subDays(3))
            ->count();

        if ($stuckLeads > 0) {
            $tasks[] = [
                'priority' => 2,
                'icon' => '⏰',
                'message' => "*{$stuckLeads} ta lid tiqilib qolgan*\n   3+ kun bir bosqichda. Qaror qabul qilish vaqti!",
            ];
        }

        // 3. Hot leads (high score) without recent contact
        $hotLeadsNeglected = Lead::where('business_id', $businessId)
            ->where('score_category', 'hot')
            ->where(function($q) use ($date) {
                $q->whereNull('last_contacted_at')
                  ->orWhere('last_contacted_at', '<', $date->copy()->subHours(12));
            })
            ->whereNotIn('status', ['won', 'lost'])
            ->count();

        if ($hotLeadsNeglected > 0) {
            $tasks[] = [
                'priority' => 1,
                'icon' => '🔥',
                'message' => "*{$hotLeadsNeglected} ta ISSIQ lid!*\n   Yuqori ball, lekin aloqa kam. Prioritet bering!",
            ];
        }

        // 4. Unassigned leads
        $unassignedLeads = Lead::where('business_id', $businessId)
            ->whereNull('assigned_to')
            ->whereNotIn('status', ['won', 'lost'])
            ->count();

        if ($unassignedLeads > 0) {
            $tasks[] = [
                'priority' => 2,
                'icon' => '👤',
                'message' => "*{$unassignedLeads} ta lid tayinlanmagan!*\n   Operatorlarga taqsimlang.",
            ];
        }

        // 5. Recoverable lost opportunities
        $recoverableCount = LostOpportunity::where('business_id', $businessId)
            ->recoverable()
            ->where('lost_at', '>', $date->copy()->subDays(7))
            ->count();

        if ($recoverableCount > 0) {
            $tasks[] = [
                'priority' => 3,
                'icon' => '♻️',
                'message' => "*{$recoverableCount} ta yo'qotilgan lid*\n   Qayta urinib ko'rish mumkin. Recovery kampaniya o'tkazing.",
            ];
        }

        if (empty($tasks)) {
            $section .= "✅ Shoshilinch vazifalar yo'q. Ajoyib!\n\n";
            return $section;
        }

        // Sort by priority
        usort($tasks, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($tasks as $index => $task) {
            $num = $index + 1;
            $section .= "{$task['icon']} {$num}. {$task['message']}\n\n";
        }

        return $section;
    }

    /**
     * Section 4: QUICK WINS (Tez yutuqlar)
     */
    protected function buildQuickWinsSection(string $businessId, Carbon $date): string
    {
        $section = "⚡ *TEZKOR YUTUQLAR*\n\n";

        $wins = [];

        // 1. SQLs ready for closing
        $sqlLeads = Lead::where('business_id', $businessId)
            ->where('qualification_status', 'sql')
            ->whereNotIn('status', ['won', 'lost'])
            ->count();

        if ($sqlLeads > 0) {
            $wins[] = "• *{$sqlLeads} ta SQL* tayyor - sotuvni yoping!";
        }

        // 2. High-value leads in pipeline
        $highValueLeads = Lead::where('business_id', $businessId)
            ->where('estimated_value', '>', 1000000) // 1M+ UZS
            ->whereNotIn('status', ['won', 'lost', 'new'])
            ->count();

        if ($highValueLeads > 0) {
            $wins[] = "• *{$highValueLeads} ta katta deal* - alohida e'tibor bering!";
        }

        // 3. Leads contacted yesterday but need follow-up
        $needFollowUp = Lead::where('business_id', $businessId)
            ->whereDate('last_contacted_at', $date)
            ->whereNotIn('status', ['won', 'lost'])
            ->count();

        if ($needFollowUp > 0) {
            $wins[] = "• Kecha bog'lanilgan *{$needFollowUp} ta lid* - bugun follow-up!";
        }

        if (empty($wins)) {
            $section .= "Bugun uchun maxsus tavsiyalar yo'q.\n\n";
            return $section;
        }

        foreach ($wins as $win) {
            $section .= "{$win}\n";
        }
        $section .= "\n";

        return $section;
    }

    /**
     * Footer with motivational quote.
     */
    protected function buildFooter(Carbon $date): string
    {
        $generatedAt = now()->format('H:i');
        $quote = $this->getRandomQuote();

        return "━━━━━━━━━━━━━━━━━━━━━\n"
            . "🧠 *KUN HIKMATI:*\n"
            . "_{$quote}_\n\n"
            . "🤖 _BiznesPilot | {$generatedAt}_";
    }

    /**
     * Get random motivational business quote.
     */
    protected function getRandomQuote(): string
    {
        $quotes = [
            "Mijoz qiroli — xizmat ko'rsatish san'at.",
            "Kichik qadamlar — katta natijalar.",
            "Bugun qilmagan ishni ertaga raqib qiladi.",
            "Tezlik — biznesning kaliti.",
            "Har bir muammo — yangi imkoniyat.",
            "Mijoz shikoyati — bepul maslahat.",
            "O'z biznesingizni sevmang, mijozni seving.",
            "Muvaffaqiyat — kundalik odatlarning yig'indisi.",
            "Yaxshi xizmat — eng yaxshi reklama.",
            "Kechikkan javob — yo'qolgan mijoz.",
            "Biznes — bu marafon, sprint emas.",
            "Kelajak ularga tegishli, kimlar erta turadi.",
            "Eng katta risk — hech qanday risk olmaslik.",
            "Yaxshi rahbar birinchi xizmat qiladi.",
            "Soddalik — murakkablikning eng yuqori shakli.",
            "Harakat qilmagan — yutuqqa erishmaydi.",
            "Vaqt — eng qimmatbaho resurs.",
            "Jamoa kuchi — yakkaning kuchidan ko'p.",
            "Mijozning muammosi — sizning imkoniyatingiz.",
            "Bugun o'rgan, ertaga yetakchi bo'l.",
        ];

        return $quotes[array_rand($quotes)];
    }

    /**
     * Format money for display.
     */
    protected function formatMoney(float $amount): string
    {
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 1) . 'M so\'m';
        } elseif ($amount >= 1000) {
            return number_format($amount / 1000, 0) . 'K so\'m';
        }
        return number_format($amount, 0) . ' so\'m';
    }

    /**
     * Get Uzbek day name.
     */
    protected function getUzbekDayName(int $dayOfWeek): string
    {
        return [
            0 => 'Yakshanba', 1 => 'Dushanba', 2 => 'Seshanba',
            3 => 'Chorshanba', 4 => 'Payshanba', 5 => 'Juma', 6 => 'Shanba',
        ][$dayOfWeek] ?? '';
    }

    // ==========================================
    // INDIVIDUAL STAT METHODS (for API usage)
    // ==========================================

    /**
     * Get bleeding money stats only.
     */
    public function getBleedingMoneyStats(string $businessId, Carbon $date): array
    {
        $lostOpportunities = LostOpportunity::where('business_id', $businessId)
            ->whereDate('lost_at', $date)
            ->get();

        return [
            'total_lost_value' => $lostOpportunities->sum('estimated_value'),
            'total_wasted_spend' => $lostOpportunities->sum('acquisition_cost'),
            'count' => $lostOpportunities->count(),
            'by_reason' => $lostOpportunities->groupBy('lost_reason')
                ->map(fn($g) => ['count' => $g->count(), 'value' => $g->sum('estimated_value')])
                ->toArray(),
        ];
    }

    /**
     * Get marketing truth stats only.
     */
    public function getMarketingTruthStats(string $businessId, Carbon $date): array
    {
        $orders = Order::where('business_id', $businessId)
            ->whereDate('ordered_at', $date)
            ->get();

        return [
            'total_revenue' => $orders->sum('total_amount'),
            'orders_count' => $orders->count(),
            'by_source' => $orders->whereNotNull('utm_source')
                ->groupBy('utm_source')
                ->map(fn($g) => ['count' => $g->count(), 'revenue' => $g->sum('total_amount')])
                ->toArray(),
            'without_attribution' => $orders->filter(fn($o) => !$o->hasAttribution())->count(),
        ];
    }

    /**
     * Get cold leads stats only.
     */
    public function getColdLeadsStats(string $businessId): array
    {
        return [
            'cold_leads' => Lead::where('business_id', $businessId)
                ->where('status', 'new')
                ->where('created_at', '<', now()->subDay())
                ->whereNull('last_contacted_at')
                ->count(),
            'stuck_leads' => Lead::where('business_id', $businessId)
                ->whereIn('status', ['negotiation', 'proposal'])
                ->where('stage_changed_at', '<', now()->subDays(3))
                ->count(),
            'unassigned_leads' => Lead::where('business_id', $businessId)
                ->whereNull('assigned_to')
                ->whereNotIn('status', ['won', 'lost'])
                ->count(),
        ];
    }

    // ==========================================
    // ORIGINAL GENERATE METHOD (kept for compatibility)
    // ==========================================

    public function generate(Business $business, array $metrics, array $trends): array
    {
        $insights = [];
        $recommendations = [];

        // Sales insights
        if (isset($metrics['sales'])) {
            $sales = $metrics['sales'];

            if ($sales['total_count'] > 0) {
                $insights[] = [
                    'type' => 'sales',
                    'title' => 'Sotuv ko\'rsatkichlari',
                    'description' => sprintf(
                        'Tanlangan davrda %d ta sotuv amalga oshirildi, umumiy daromad %s so\'m.',
                        $sales['total_count'],
                        number_format($sales['total_revenue'], 0, '.', ' ')
                    ),
                    'priority' => 'info',
                ];
            } else {
                $insights[] = [
                    'type' => 'sales',
                    'title' => 'Sotuv yo\'q',
                    'description' => 'Tanlangan davrda sotuvlar qayd etilmagan.',
                    'priority' => 'warning',
                ];

                $recommendations[] = [
                    'type' => 'action',
                    'title' => 'Sotuvlarni kuzatish',
                    'description' => 'Sotuvlarni tizimga kiritishni boshlang yoki mavjud sotuvlarni import qiling.',
                    'action_url' => '/business/sales',
                    'priority' => 'high',
                ];
            }
        }

        // Lead insights
        if (isset($metrics['leads'])) {
            $leads = $metrics['leads'];

            if ($leads['conversion_rate'] > 0) {
                $insights[] = [
                    'type' => 'leads',
                    'title' => 'Lead konversiyasi',
                    'description' => sprintf(
                        'Lead konversiya darajasi: %.1f%%. Jami %d ta leaddan %d tasi mijozga aylandi.',
                        $leads['conversion_rate'],
                        $leads['total_leads'],
                        $leads['converted_leads']
                    ),
                    'priority' => $leads['conversion_rate'] >= 10 ? 'success' : 'warning',
                ];
            }
        }

        // Trend insights
        if (isset($trends['period_comparison'])) {
            $comparison = $trends['period_comparison'];

            if ($comparison['trend'] === 'up') {
                $insights[] = [
                    'type' => 'trend',
                    'title' => 'O\'sish tendensiyasi',
                    'description' => sprintf(
                        'Sotuv hajmi oldingi davrga nisbatan %.1f%% ga oshgan.',
                        $comparison['change_percent']
                    ),
                    'priority' => 'success',
                ];
            } elseif ($comparison['trend'] === 'down') {
                $insights[] = [
                    'type' => 'trend',
                    'title' => 'Pasayish tendensiyasi',
                    'description' => sprintf(
                        'Sotuv hajmi oldingi davrga nisbatan %.1f%% ga kamaygan.',
                        abs($comparison['change_percent'])
                    ),
                    'priority' => 'danger',
                ];

                $recommendations[] = [
                    'type' => 'analysis',
                    'title' => 'Sabablarni tahlil qiling',
                    'description' => 'Sotuv pasayishi sabablarini aniqlash uchun raqobatchilar va bozor holatini tahlil qiling.',
                    'priority' => 'high',
                ];
            }
        }

        // Default recommendation if empty
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'general',
                'title' => 'Ma\'lumotlarni boyiting',
                'description' => 'Yanada aniqroq tavsiyalar olish uchun ko\'proq ma\'lumot kiriting.',
                'priority' => 'medium',
            ];
        }

        return [
            'insights' => $insights,
            'recommendations' => $recommendations,
        ];
    }
}
