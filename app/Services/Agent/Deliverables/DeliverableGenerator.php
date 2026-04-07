<?php

namespace App\Services\Agent\Deliverables;

use App\Models\Business;
use App\Models\ContentGeneration;
use App\Models\Deliverable;
use App\Models\DreamBuyer;
use App\Models\Lead;
use App\Services\Agent\Knowledge\PlatformKnowledge;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Agentlar uchun tayyor mahsulotlar (deliverables) generatori.
 *
 * Har bir agent o'z sohasidagi ishni BAJARIB foydalanuvchiga taqdim etadi.
 * Foydalanuvchi faqat TASDIQLAYDI — ish qilmaydi.
 */
class DeliverableGenerator
{
    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Imronbek: 4 haftalik kontent reja tayyorlaydi
     */
    public function generateContentPlan(string $businessId, string $conversationId = null): ?array
    {
        $business = Business::find($businessId);
        if (!$business) return null;

        $dreamBuyer = DreamBuyer::where('business_id', $businessId)->first();

        // Eng yaxshi avvalgi kontentlar
        $topContent = ContentGeneration::where('business_id', $businessId)
            ->where('status', 'completed')
            ->where('was_published', true)
            ->orderByDesc('post_engagement_rate')
            ->limit(10)
            ->get(['topic', 'content_type', 'target_channel', 'post_engagement_rate', 'post_likes']);

        // Kanallar
        $channels = [];
        try {
            $channels = $business->marketingChannels()->where('is_active', true)->pluck('type')->toArray();
        } catch (\Exception $e) {}
        if (empty($channels)) $channels = ['instagram', 'telegram'];

        $prompt = "Quyidagi biznes uchun 4 haftalik (28 kun) kontent reja tayyorla. "
            . "Har bir post uchun: kun_raqami (1-28), sarlavha, qisqa matn (caption 2-3 jumla), "
            . "5 ta hashtag, kanal ({$this->implodeChannels($channels)}), post_turi (post/story/reel/carousel/article), "
            . "post_vaqti (masalan 10:00 yoki 18:30).\n\n"
            . "Biznes: {$business->name}\n"
            . "Soha: " . ($business->category ?? $business->industry_code ?? 'umumiy') . "\n"
            . "Ideal mijoz: " . ($dreamBuyer ? $dreamBuyer->name . ' — ' . mb_substr($dreamBuyer->description ?? '', 0, 200) : 'belgilanmagan') . "\n"
            . "Mavjud kanallar: " . implode(', ', $channels) . "\n"
            . ($topContent->isNotEmpty() ? "Eng yaxshi avvalgi mavzular: " . $topContent->pluck('topic')->implode(', ') : '') . "\n\n"
            . "MUHIM: JSON formatda qaytar. Faqat JSON, boshqa matn yo'q. Struktura:\n"
            . '[{"kun":1,"sarlavha":"...","matn":"...","hashtaglar":["#.."],"kanal":"instagram","turi":"post","vaqt":"10:00"}]';

        try {
            $response = $this->aiService->ask(
                prompt: $prompt,
                systemPrompt: "Sen professional kontent marketing mutaxassisisan. O'zbek tilida. Faqat JSON qaytar, boshqa matn yozma.",
                preferredModel: 'haiku',
                maxTokens: 3000,
                businessId: $businessId,
                agentType: 'marketing',
            );

            $contentPlan = $this->parseJSON($response->content);
            if (empty($contentPlan)) {
                // Agar JSON parse bo'lmasa — sof matn sifatida saqlash
                $contentPlan = [['raw_text' => $response->content]];
            }

            $deliverable = Deliverable::create([
                'business_id' => $businessId,
                'agent' => 'imronbek',
                'type' => 'content_plan',
                'title' => '4 haftalik kontent reja — ' . count($contentPlan) . ' ta post',
                'data' => ['posts' => $contentPlan, 'channels' => $channels],
                'preview' => ['count' => count($contentPlan), 'first_3' => array_slice($contentPlan, 0, 3)],
                'status' => 'pending_approval',
                'conversation_id' => $conversationId,
            ]);

            return [
                'deliverable_id' => $deliverable->id,
                'title' => $deliverable->title,
                'count' => count($contentPlan),
                'preview' => array_slice($contentPlan, 0, 3),
            ];
        } catch (\Exception $e) {
            Log::error('DeliverableGenerator: contentPlan xato', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Salomatxon: javobsiz lidlar uchun shaxsiy javob shablonlari
     */
    public function generateLeadResponses(string $businessId, string $conversationId = null): ?array
    {
        $business = Business::find($businessId);
        if (!$business) return null;

        $unansweredLeads = Lead::where('business_id', $businessId)
            ->where('status', 'new')
            ->orderByDesc('score')
            ->limit(20)
            ->get(['id', 'name', 'company', 'status', 'score', 'estimated_value', 'created_at']);

        if ($unansweredLeads->isEmpty()) {
            // Yangi + contacted lidlar
            $unansweredLeads = Lead::where('business_id', $businessId)
                ->whereIn('status', ['new', 'contacted'])
                ->orderByDesc('created_at')
                ->limit(15)
                ->get(['id', 'name', 'company', 'status', 'score', 'estimated_value', 'created_at']);
        }

        if ($unansweredLeads->isEmpty()) return null;

        // Takliflar
        $offers = DB::table('offers')->where('business_id', $businessId)->where('status', 'active')->pluck('name')->implode(', ');

        $leadsText = $unansweredLeads->map(fn($l) =>
            "- {$l->name}" . ($l->company ? " ({$l->company})" : '') . ", ball: {$l->score}, {$l->created_at->diffForHumans()}"
        )->implode("\n");

        $prompt = "Quyidagi {$unansweredLeads->count()} ta lid uchun har biriga SHAXSIY javob yoz. "
            . "Har bir javob 2-3 jumla, do'stona, qiymat taklif qiladigan. Ismini ishlatib murojaat qil.\n\n"
            . "Biznes: {$business->name}\n"
            . "Takliflar: {$offers}\n\n"
            . "Lidlar:\n{$leadsText}\n\n"
            . "MUHIM: JSON formatda qaytar. Faqat JSON, boshqa matn yo'q.\n"
            . '[{"lid_nomi":"Aziz Karimov","javob":"Assalomu alaykum, Aziz aka! ..."}]';

        try {
            $response = $this->aiService->ask(
                prompt: $prompt,
                systemPrompt: "Sen professional sotuv mutaxassisisan. O'zbek tilida. Faqat JSON qaytar.",
                preferredModel: 'haiku',
                maxTokens: 2000,
                businessId: $businessId,
                agentType: 'sales',
            );

            $responses = $this->parseJSON($response->content);
            if (empty($responses)) {
                $responses = [['raw_text' => $response->content]];
            }

            // Lid ID larini biriktirish
            $responseData = [];
            foreach ($unansweredLeads as $i => $lead) {
                $responseData[] = [
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->name,
                    'lead_company' => $lead->company,
                    'lead_score' => $lead->score,
                    'response_text' => $responses[$i]['javob'] ?? ($responses[$i]['response'] ?? ''),
                ];
            }

            $deliverable = Deliverable::create([
                'business_id' => $businessId,
                'agent' => 'salomatxon',
                'type' => 'lead_responses',
                'title' => $unansweredLeads->count() . ' ta lid uchun javob shablonlari',
                'data' => ['responses' => $responseData, 'total' => count($responseData)],
                'preview' => ['count' => count($responseData), 'first_3' => array_slice($responseData, 0, 3)],
                'status' => 'pending_approval',
                'conversation_id' => $conversationId,
            ]);

            return [
                'deliverable_id' => $deliverable->id,
                'title' => $deliverable->title,
                'count' => count($responseData),
                'preview' => array_slice($responseData, 0, 3),
            ];
        } catch (\Exception $e) {
            Log::error('DeliverableGenerator: leadResponses xato', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Jasurbek: KPI va ROAS hisobotini tayyorlaydi
     */
    public function generateKPIReport(string $businessId, string $conversationId = null): ?array
    {
        $business = Business::find($businessId);
        if (!$business) return null;

        // Oxirgi 30 kunlik KPI
        $kpiData = DB::table('kpi_daily_entries')
            ->where('business_id', $businessId)
            ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->orderBy('date')
            ->get();

        if ($kpiData->isEmpty()) return null;

        // Lead statistikasi
        $leadStats = DB::table('leads')
            ->where('business_id', $businessId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as won,
                SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_leads,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as this_month,
                AVG(CASE WHEN status = 'won' THEN estimated_value ELSE NULL END) as avg_deal_value
            ", [now()->startOfMonth()])
            ->first();

        $totalSpend = $kpiData->sum('spend_total');
        $totalRevenue = $kpiData->sum('revenue_total');
        $totalLeads = $kpiData->sum('leads_total');
        $totalSales = $kpiData->sum('sales_total');
        $avgConversion = $totalLeads > 0 ? round($totalSales / $totalLeads * 100, 1) : 0;
        $roas = $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0;
        $cac = $totalSales > 0 ? round($totalSpend / $totalSales) : 0;

        $dataText = "30 kunlik KPI:\n"
            . "- Jami daromad: " . number_format($totalRevenue) . " so'm\n"
            . "- Jami xarajat: " . number_format($totalSpend) . " so'm\n"
            . "- Jami lidlar: {$totalLeads}\n"
            . "- Jami sotuvlar: {$totalSales}\n"
            . "- ROAS: {$roas}x\n"
            . "- CAC: " . number_format($cac) . " so'm\n"
            . "- Konversiya: {$avgConversion}%\n"
            . "- Lead baza: jami {$leadStats->total}, yangi {$leadStats->new_leads}, yutilgan {$leadStats->won}, yo'qotilgan {$leadStats->lost}\n"
            . "- O'rtacha bitim: " . number_format($leadStats->avg_deal_value ?? 0) . " so'm";

        $prompt = "Quyidagi biznes KPI ma'lumotlariga asoslanib tahlil va tavsiyalar ber. "
            . "Qaysi ko'rsatkichlar yaxshi, qaysilari yomon, nima qilish kerak.\n\n"
            . "Biznes: {$business->name}\n"
            . $dataText . "\n\n"
            . "MUHIM: JSON formatda qaytar. Struktura:\n"
            . '{"xulosa":"2-3 jumla umumiy holat","yaxshi":["..."],"yomon":["..."],"tavsiyalar":[{"nomi":"...","tushuntirish":"...","muhimlik":"yuqori/o\'rta"}],"raqamlar":{"daromad":"...","xarajat":"...","roas":"...","cac":"...","konversiya":"..."}}';

        try {
            $response = $this->aiService->ask(
                prompt: $prompt,
                systemPrompt: "Sen professional biznes tahlilchisan. O'zbek tilida. Faqat JSON qaytar.",
                preferredModel: 'haiku',
                maxTokens: 1500,
                businessId: $businessId,
                agentType: 'analytics',
            );

            $report = $this->parseJSON($response->content);
            if (empty($report)) {
                $report = ['raw_text' => $response->content];
            }

            // Raqamlarni deliverable ga qo'shish
            $report['raw_kpi'] = [
                'revenue' => $totalRevenue,
                'spend' => $totalSpend,
                'roas' => $roas,
                'cac' => $cac,
                'conversion' => $avgConversion,
                'total_leads' => $totalLeads,
                'total_sales' => $totalSales,
                'period_days' => 30,
            ];

            $deliverable = Deliverable::create([
                'business_id' => $businessId,
                'agent' => 'jasurbek',
                'type' => 'kpi_report',
                'title' => '30 kunlik KPI hisobot — ROAS ' . $roas . 'x',
                'data' => $report,
                'preview' => [
                    'roas' => $roas,
                    'revenue' => $totalRevenue,
                    'spend' => $totalSpend,
                    'conversion' => $avgConversion,
                ],
                'status' => 'completed', // hisobot — faqat ko'rish
                'conversation_id' => $conversationId,
            ]);

            return [
                'deliverable_id' => $deliverable->id,
                'title' => $deliverable->title,
                'roas' => $roas,
                'revenue' => $totalRevenue,
                'spend' => $totalSpend,
                'conversion' => $avgConversion,
                'summary' => $report['xulosa'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('DeliverableGenerator: kpiReport xato', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Nodira: Sifat nazorati hisoboti — operatorlar yoki umumiy
     */
    public function generateQualityReport(string $businessId, string $conversationId = null): ?array
    {
        $business = Business::find($businessId);
        if (!$business) return null;

        // Qo'ng'iroq tahlillari bor-yo'qligini tekshirish
        $hasCallData = false;
        try {
            $hasCallData = DB::table('call_analyses')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(30))
                ->exists();
        } catch (\Exception $e) {}

        if ($hasCallData) {
            return $this->generateCallQualityReport($businessId, $conversationId);
        }

        // Qo'ng'iroq ma'lumotlari yo'q — umumiy sifat tavsiyalari
        $leadConversion = DB::table('leads')
            ->where('business_id', $businessId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as won,
                SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost
            ")->first();

        $lostRate = ($leadConversion->total ?? 0) > 0
            ? round(($leadConversion->lost ?? 0) / $leadConversion->total * 100, 1)
            : 0;

        $report = [
            'telephony_connected' => false,
            'recommendation' => 'IP telefoniya ulansa, qo\'ng\'iroqlar avtomatik tahlil qilinadi',
            'connect_path' => 'Bosh sahifa > Integratsiyalar > Sipuni yoki Utel',
            'current_stats' => [
                'total_leads' => $leadConversion->total ?? 0,
                'won' => $leadConversion->won ?? 0,
                'lost' => $leadConversion->lost ?? 0,
                'lost_rate' => $lostRate,
            ],
            'quality_tips' => [
                'Yo\'qotilgan lidlar sababini yozib boring — bu sifatni oshiradi',
                'Har bir lid bilan 24 soat ichida bog\'laning — tez javob konversiyani 2x oshiradi',
                'E\'tirozlarga tayyor javob shablonlari tuzing',
            ],
        ];

        $deliverable = Deliverable::create([
            'business_id' => $businessId,
            'agent' => 'nodira',
            'type' => 'quality_report',
            'title' => 'Sifat nazorati hisoboti',
            'data' => $report,
            'preview' => ['telephony' => false, 'lost_rate' => $lostRate],
            'status' => 'completed',
            'conversation_id' => $conversationId,
        ]);

        return [
            'deliverable_id' => $deliverable->id,
            'title' => $deliverable->title,
            'telephony_connected' => false,
            'lost_rate' => $lostRate,
        ];
    }

    /**
     * Qo'ng'iroq sifati hisoboti (agar ma'lumot bor bo'lsa)
     */
    private function generateCallQualityReport(string $businessId, ?string $conversationId): ?array
    {
        $analyses = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $avgScore = round($analyses->avg('overall_score') ?? 0);
        $totalCalls = $analyses->count();

        $report = [
            'telephony_connected' => true,
            'total_calls' => $totalCalls,
            'avg_score' => $avgScore,
            'period' => '30 kun',
        ];

        $deliverable = Deliverable::create([
            'business_id' => $businessId,
            'agent' => 'nodira',
            'type' => 'quality_report',
            'title' => "Sifat hisoboti — {$totalCalls} qo'ng'iroq, o'rtacha {$avgScore} ball",
            'data' => $report,
            'preview' => ['avg_score' => $avgScore, 'total_calls' => $totalCalls],
            'status' => 'completed',
            'conversation_id' => $conversationId,
        ]);

        return [
            'deliverable_id' => $deliverable->id,
            'title' => $deliverable->title,
            'avg_score' => $avgScore,
            'total_calls' => $totalCalls,
        ];
    }

    /**
     * Salomatxon: follow-up ketma-ketligi — eng yuqori ballik lidlar uchun
     */
    public function generateFollowUpPlan(string $businessId, string $conversationId = null): ?array
    {
        $business = Business::find($businessId);
        if (!$business) return null;

        $hotLeads = Lead::where('business_id', $businessId)
            ->whereIn('status', ['contacted', 'qualified', 'proposal', 'negotiation'])
            ->orderByDesc('score')
            ->limit(15)
            ->get(['id', 'name', 'company', 'status', 'score', 'estimated_value']);

        if ($hotLeads->isEmpty()) return null;

        $prompt = "Quyidagi {$hotLeads->count()} ta issiq lid uchun 5 kunlik follow-up xabar ketma-ketligi tayyorla. "
            . "Har bir lid uchun: 1-kun, 3-kun, 5-kun xabarlari. Qisqa, do'stona, qiymatli.\n\n"
            . "Biznes: {$business->name}\n"
            . "Lidlar:\n" . $hotLeads->map(fn($l) => "- {$l->name} ({$l->status}, ball: {$l->score})")->implode("\n") . "\n\n"
            . "JSON formatda qaytar:\n"
            . '[{"lid_nomi":"...","xabarlar":[{"kun":1,"matn":"..."},{"kun":3,"matn":"..."},{"kun":5,"matn":"..."}]}]';

        try {
            $response = $this->aiService->ask(
                prompt: $prompt,
                systemPrompt: "Sen professional sotuv mutaxassisisan. O'zbek tilida. Faqat JSON qaytar.",
                preferredModel: 'haiku',
                maxTokens: 3000,
                businessId: $businessId,
                agentType: 'sales',
            );

            $followUps = $this->parseJSON($response->content);

            $deliverable = Deliverable::create([
                'business_id' => $businessId,
                'agent' => 'salomatxon',
                'type' => 'follow_up_plan',
                'title' => $hotLeads->count() . ' ta issiq lid uchun follow-up reja',
                'data' => ['follow_ups' => $followUps ?: [['raw_text' => $response->content]], 'lead_count' => $hotLeads->count()],
                'preview' => ['count' => $hotLeads->count()],
                'status' => 'pending_approval',
                'conversation_id' => $conversationId,
            ]);

            return [
                'deliverable_id' => $deliverable->id,
                'title' => $deliverable->title,
                'count' => $hotLeads->count(),
            ];
        } catch (\Exception $e) {
            Log::error('DeliverableGenerator: followUp xato', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * JSON parsing helper — AI javobidan JSON ajratib olish
     */
    private function parseJSON(string $text): ?array
    {
        // To'g'ridan-to'g'ri parse qilishga urinish
        $decoded = json_decode($text, true);
        if ($decoded !== null) return $decoded;

        // JSON blokni qidirish (```json ... ``` yoki [ ... ] yoki { ... })
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) return $decoded;
        }

        if (preg_match('/(\[[\s\S]*\])/', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) return $decoded;
        }

        if (preg_match('/(\{[\s\S]*\})/', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) return $decoded;
        }

        return null;
    }

    private function implodeChannels(array $channels): string
    {
        return implode('/', $channels);
    }
}
