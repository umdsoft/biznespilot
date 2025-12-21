<?php

namespace App\Services;

use App\Models\Business;
use App\Models\AIDiagnostic;
use App\Models\AnnualStrategy;
use App\Models\QuarterlyPlan;
use App\Models\MonthlyPlan;
use Illuminate\Support\Facades\Log;

class AIStrategyService
{
    public function __construct(
        private ClaudeAIService $claudeService
    ) {}

    /**
     * Generate annual strategy using AI
     */
    public function generateAnnualStrategy(Business $business, AIDiagnostic $diagnostic, int $year): array
    {
        $systemPrompt = $this->getAnnualStrategySystemPrompt();

        $businessContext = $this->buildBusinessContext($business, $diagnostic);

        $prompt = <<<EOT
Quyidagi biznes ma'lumotlari asosida {$year}-yil uchun to'liq strategiya yarating.

BIZNES KONTEKSTI:
{$businessContext}

DIAGNOSTIKA NATIJALARI:
- Umumiy ball: {$diagnostic->overall_health_score}/100
- Marketing ball: {$diagnostic->marketing_score}/100
- Savdo ball: {$diagnostic->sales_score}/100
- Kontent ball: {$diagnostic->content_score}/100

SWOT TAHLIL:
- Kuchli tomonlar: {$this->formatArray($diagnostic->strengths)}
- Zaif tomonlar: {$this->formatArray($diagnostic->weaknesses)}
- Imkoniyatlar: {$this->formatArray($diagnostic->opportunities)}
- Xavflar: {$this->formatArray($diagnostic->threats)}

TAVSIYALAR:
{$this->formatArray($diagnostic->recommendations)}

Quyidagi JSON formatida javob bering:
{
    "vision": "Kompaniya vizyoni",
    "summary": "Strategiya qisqacha bayoni (3-4 jumla)",
    "goals": [
        {"name": "Maqsad nomi", "description": "Tavsif", "target": 100, "metric": "metrika", "priority": 1}
    ],
    "focus_areas": ["Focus 1", "Focus 2", "Focus 3"],
    "growth_drivers": ["Driver 1", "Driver 2"],
    "risks": ["Risk 1", "Risk 2"],
    "revenue_target": 100000000,
    "budget": 10000000,
    "channels": ["instagram", "telegram"],
    "recommendations": [
        {"title": "Tavsiya", "description": "Tavsif", "priority": "high", "impact": "Kutilgan natija"}
    ],
    "ai_summary": "AI tomonidan yaratilgan qisqacha tahlil",
    "confidence": 85
}
EOT;

        try {
            $response = $this->claudeService->complete($prompt, $systemPrompt, 4096);
            return $this->parseJsonResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Annual Strategy generation failed', [
                'error' => $e->getMessage(),
                'business_id' => $business->id,
            ]);
            return [];
        }
    }

    /**
     * Generate quarterly plan using AI
     */
    public function generateQuarterlyPlan(Business $business, AnnualStrategy $annual, int $quarter): array
    {
        $systemPrompt = $this->getQuarterlyPlanSystemPrompt();

        $prompt = <<<EOT
{$annual->year}-yil Q{$quarter} uchun choraklik reja yarating.

YILLIK STRATEGIYA:
- Vizyon: {$annual->vision_statement}
- Maqsadlar: {$this->formatArray($annual->strategic_goals)}
- Fokus sohalar: {$this->formatArray($annual->focus_areas)}
- Yillik daromad maqsadi: {$annual->revenue_target} so'm
- Yillik byudjet: {$annual->annual_budget} so'm

CHORAK HAQIDA:
- Chorak: Q{$quarter} ({$this->getQuarterMonthNames($quarter)})
- Byudjet: {$this->getQuarterBudget($annual, $quarter)} so'm

Quyidagi JSON formatida javob bering:
{
    "theme": "Chorak mavzusi",
    "summary": "Chorak strategiyasi qisqacha",
    "objectives": ["Maqsad 1", "Maqsad 2", "Maqsad 3"],
    "goals": [
        {"name": "Maqsad", "target": 25, "metric": "leads"}
    ],
    "initiatives": [
        {"name": "Tashabbus", "description": "Tavsif", "priority": 1}
    ],
    "campaigns": [
        {"name": "Kampaniya nomi", "type": "awareness", "budget": 1000000, "duration": "2 hafta"}
    ],
    "recommendations": ["Tavsiya 1", "Tavsiya 2"],
    "ai_summary": "AI tahlili",
    "confidence": 80
}
EOT;

        try {
            $response = $this->claudeService->complete($prompt, $systemPrompt, 3000);
            return $this->parseJsonResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Quarterly Plan generation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate monthly plan using AI
     */
    public function generateMonthlyPlan(Business $business, QuarterlyPlan $quarterly, int $month): array
    {
        $monthName = MonthlyPlan::MONTHS[$month] ?? $month;
        $systemPrompt = $this->getMonthlyPlanSystemPrompt();

        $prompt = <<<EOT
{$quarterly->year}-yil {$monthName} oyi uchun oylik reja yarating.

CHORAKLIK REJA:
- Tema: {$quarterly->theme}
- Maqsadlar: {$this->formatArray($quarterly->quarterly_objectives)}
- Kampaniyalar: {$this->formatArray($quarterly->campaigns)}
- Byudjet: {$quarterly->budget} so'm

OY BYUDJETI: {$this->getMonthBudget($quarterly)} so'm

Quyidagi JSON formatida javob bering:
{
    "theme": "Oy mavzusi",
    "summary": "Oy strategiyasi",
    "objectives": ["Maqsad 1", "Maqsad 2"],
    "goals": [
        {"name": "Maqsad", "target": 10, "metric": "leads"}
    ],
    "content_target": 20,
    "posts_target": 30,
    "content_themes": ["Tema 1", "Tema 2", "Tema 3", "Tema 4"],
    "content_types": ["post", "story", "reel", "article"],
    "week_1": {"focus": "Hafta fokusi", "tasks": ["Task 1", "Task 2"]},
    "week_2": {"focus": "Hafta fokusi", "tasks": ["Task 1", "Task 2"]},
    "week_3": {"focus": "Hafta fokusi", "tasks": ["Task 1", "Task 2"]},
    "week_4": {"focus": "Hafta fokusi", "tasks": ["Task 1", "Task 2"]},
    "content_suggestions": [
        {"title": "Kontent g'oyasi", "type": "post", "theme": "educational"}
    ],
    "recommendations": ["Tavsiya 1"],
    "ai_summary": "AI tahlili",
    "confidence": 75
}
EOT;

        try {
            $response = $this->claudeService->complete($prompt, $systemPrompt, 3000);
            return $this->parseJsonResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Monthly Plan generation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate weekly plan using AI
     */
    public function generateWeeklyPlan(Business $business, MonthlyPlan $monthly, int $weekOfMonth): array
    {
        $systemPrompt = $this->getWeeklyPlanSystemPrompt();

        $prompt = <<<EOT
{$monthly->getFullPeriodLabel()} - Hafta {$weekOfMonth} uchun haftalik reja yarating.

OYLIK REJA:
- Tema: {$monthly->theme}
- Kontent temalar: {$this->formatArray($monthly->content_themes)}
- Haftalik post maqsadi: {$this->getWeeklyPostTarget($monthly)}

Quyidagi JSON formatida javob bering:
{
    "focus": "Hafta fokusi",
    "priorities": ["Ustuvorlik 1", "Ustuvorlik 2", "Ustuvorlik 3"],
    "goals": [
        {"name": "Maqsad", "target": 3, "metric": "posts"}
    ],
    "monday": {"tasks": ["Task 1"], "content": {"type": "post", "topic": "Mavzu"}},
    "tuesday": {"tasks": ["Task 1"], "content": {"type": "story", "topic": "Mavzu"}},
    "wednesday": {"tasks": ["Task 1"], "content": {"type": "reel", "topic": "Mavzu"}},
    "thursday": {"tasks": ["Task 1"], "content": {"type": "post", "topic": "Mavzu"}},
    "friday": {"tasks": ["Task 1"], "content": {"type": "post", "topic": "Mavzu"}},
    "saturday": {"tasks": [], "content": null},
    "sunday": {"tasks": ["Planning"], "content": null},
    "tasks": [
        {"title": "Vazifa", "description": "Tavsif", "day": "monday", "priority": 1}
    ],
    "posts_count": 5,
    "marketing": ["Marketing faoliyat 1"],
    "sales": ["Savdo faoliyat 1"],
    "content_ideas": [
        {"title": "Kontent g'oyasi", "type": "post", "day": "monday"}
    ],
    "suggestions": ["Tavsiya 1"]
}
EOT;

        try {
            $response = $this->claudeService->complete($prompt, $systemPrompt, 2500);
            return $this->parseJsonResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Weekly Plan generation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate content ideas for a specific day/week
     */
    public function generateContentIdeas(Business $business, array $context, int $count = 5): array
    {
        $systemPrompt = <<<EOT
Siz O'zbekiston bozoridagi bizneslar uchun ijtimoiy tarmoq kontent strategisti siz.
Kreativ, qiziqarli va engagement oluvchi kontent g'oyalarini yarating.
Har bir g'oya aniq va amalga oshiriladigan bo'lishi kerak.
EOT;

        $channelInfo = $context['channel'] ?? 'instagram';
        $theme = $context['theme'] ?? 'general';

        $prompt = <<<EOT
{$business->name} biznesi uchun {$channelInfo} kanaliga {$count} ta kontent g'oyasi yarating.

Biznes turi: {$business->industry}
Mavzu: {$theme}

JSON formatida javob bering:
{
    "ideas": [
        {
            "title": "Kontent sarlavhasi",
            "description": "Qisqa tavsif",
            "type": "post/story/reel/carousel",
            "caption": "Post caption matni",
            "hashtags": ["hashtag1", "hashtag2"],
            "best_time": "09:00",
            "engagement_tip": "Engagement oshirish maslahati"
        }
    ]
}
EOT;

        try {
            $response = $this->claudeService->complete($prompt, $systemPrompt, 2000);
            $data = $this->parseJsonResponse($response);
            return $data['ideas'] ?? [];
        } catch (\Exception $e) {
            Log::error('AI Content Ideas generation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // System prompts
    private function getAnnualStrategySystemPrompt(): string
    {
        return <<<EOT
Siz O'zbekistondagi kichik va o'rta bizneslar uchun strategik rejalashtirish bo'yicha ekspertsiz.
Sizning vazifangiz SWOT tahlil va diagnostika natijalari asosida yillik strategiya yaratish.

Strategiya quyidagilarga ega bo'lishi kerak:
- Aniq va o'lchanadigan maqsadlar (SMART format)
- Realistik byudjet taqsimoti
- Prioritetlashtirilgan harakatlar rejasi
- Xavflarni boshqarish strategiyasi

Barcha raqamlar O'zbekiston so'mida bo'lsin.
Javobni faqat JSON formatida bering.
EOT;
    }

    private function getQuarterlyPlanSystemPrompt(): string
    {
        return <<<EOT
Siz marketing va biznes strategiyasi bo'yicha ekspertsiz.
Yillik strategiya asosida choraklik rejalar yarating.
Har bir chorak o'ziga xos mavzu va fokusga ega bo'lsin.
Kampaniyalar va tashabbuslar aniq bo'lsin.
Javobni faqat JSON formatida bering.
EOT;
    }

    private function getMonthlyPlanSystemPrompt(): string
    {
        return <<<EOT
Siz kontent marketing va ijtimoiy tarmoqlar strategiyasi bo'yicha ekspertsiz.
Oylik rejalar haftalik taqsimot bilan bo'lsin.
Kontent g'oyalari kreativ va qiziqarli bo'lsin.
O'zbekiston bozoriga mos bo'lsin.
Javobni faqat JSON formatida bering.
EOT;
    }

    private function getWeeklyPlanSystemPrompt(): string
    {
        return <<<EOT
Siz kunlik operatsion rejalashtirish bo'yicha ekspertsiz.
Haftalik reja aniq vazifalar va kontent bilan bo'lsin.
Har bir kun uchun aniq maqsadlar belgilang.
Javobni faqat JSON formatida bering.
EOT;
    }

    // Helper methods
    private function buildBusinessContext(Business $business, AIDiagnostic $diagnostic): string
    {
        return json_encode([
            'name' => $business->name,
            'industry' => $business->industry,
            'business_type' => $business->business_type,
            'target_audience' => $business->target_audience,
            'products_services' => $business->products_services,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function formatArray(?array $array): string
    {
        if (!$array) return 'Mavjud emas';
        return implode(', ', array_map(function ($item) {
            return is_array($item) ? ($item['name'] ?? $item['title'] ?? json_encode($item)) : $item;
        }, $array));
    }

    private function getQuarterMonthNames(int $quarter): string
    {
        $months = [
            1 => 'Yanvar-Mart',
            2 => 'Aprel-Iyun',
            3 => 'Iyul-Sentabr',
            4 => 'Oktabr-Dekabr',
        ];
        return $months[$quarter] ?? '';
    }

    private function getQuarterBudget(AnnualStrategy $annual, int $quarter): float
    {
        return $annual->getQuarterBudget($quarter);
    }

    private function getMonthBudget(QuarterlyPlan $quarterly): float
    {
        return ($quarterly->budget ?? 0) / 3;
    }

    private function getWeeklyPostTarget(MonthlyPlan $monthly): int
    {
        return ceil(($monthly->posts_target ?? 30) / 4);
    }

    private function parseJsonResponse(string $response): array
    {
        // Extract JSON from markdown code blocks if present
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/(\{.*\})/s', $response, $matches)) {
            $json = $matches[1];
        } else {
            $json = $response;
        }

        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('Failed to parse AI response JSON', [
                'response' => $response,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
