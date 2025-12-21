<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Log;

class AIAnalysisService
{
    protected ClaudeAIService $claudeService;
    protected DiagnosticDataAggregator $dataAggregator;

    public function __construct(
        ClaudeAIService $claudeService,
        DiagnosticDataAggregator $dataAggregator
    ) {
        $this->claudeService = $claudeService;
        $this->dataAggregator = $dataAggregator;
    }

    /**
     * Perform full AI analysis for diagnostic
     */
    public function performAnalysis(
        Business $business,
        array $aggregatedData,
        array $benchmarkComparison,
        array $healthScore
    ): array {
        $results = [
            'swot' => null,
            'ai_insights' => null,
            'questions' => [],
            'tokens_used' => 0,
            'cost' => 0,
        ];

        try {
            // 1. Generate SWOT analysis
            $swotResult = $this->generateSWOTAnalysis($business, $aggregatedData, $benchmarkComparison, $healthScore);
            $results['swot'] = $swotResult['swot'];
            $results['tokens_used'] += $swotResult['tokens'];

            // 2. Generate AI insights
            $insightsResult = $this->generateAIInsights($business, $aggregatedData, $benchmarkComparison, $healthScore);
            $results['ai_insights'] = $insightsResult['insights'];
            $results['tokens_used'] += $insightsResult['tokens'];

            // 3. Generate follow-up questions
            $questionsResult = $this->generateFollowUpQuestions($business, $aggregatedData, $results['swot']);
            $results['questions'] = $questionsResult['questions'];
            $results['tokens_used'] += $questionsResult['tokens'];

            // Calculate cost (approximate: $15/1M input tokens, $75/1M output tokens for Claude 3 Opus)
            // Using Sonnet pricing: $3/1M input, $15/1M output
            $results['cost'] = round(($results['tokens_used'] / 1000000) * 9, 4); // Average

        } catch (\Exception $e) {
            Log::error('AI Analysis failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            // Return fallback data
            $results['swot'] = $this->getFallbackSWOT($aggregatedData, $healthScore);
            $results['ai_insights'] = $this->getFallbackInsights($healthScore);
            $results['questions'] = $this->getFallbackQuestions();
        }

        return $results;
    }

    /**
     * Generate SWOT analysis using Claude
     */
    protected function generateSWOTAnalysis(
        Business $business,
        array $aggregatedData,
        array $benchmarkComparison,
        array $healthScore
    ): array {
        $formattedData = $this->dataAggregator->getFormattedForAI($business);

        $prompt = $this->buildSWOTPrompt($formattedData, $benchmarkComparison, $healthScore);

        try {
            $response = $this->claudeService->sendMessage($prompt, 'diagnostic_swot');

            $swot = $this->parseSWOTResponse($response);

            return [
                'swot' => $swot,
                'tokens' => $this->estimateTokens($prompt . ($response['content'] ?? '')),
            ];
        } catch (\Exception $e) {
            Log::warning('SWOT generation failed, using fallback', ['error' => $e->getMessage()]);

            return [
                'swot' => $this->getFallbackSWOT($aggregatedData, $healthScore),
                'tokens' => 0,
            ];
        }
    }

    /**
     * Build SWOT analysis prompt
     */
    protected function buildSWOTPrompt(string $businessData, array $benchmarkComparison, array $healthScore): string
    {
        $benchmarkSummary = $this->formatBenchmarkForPrompt($benchmarkComparison);
        $healthSummary = $this->formatHealthScoreForPrompt($healthScore);

        return <<<PROMPT
Siz O'zbekistondagi kichik va o'rta bizneslar uchun marketing va sotuv bo'yicha ekspert maslahatchisiz.

Quyidagi biznes ma'lumotlarini tahlil qilib, to'liq SWOT tahlilini O'zbek tilida yozing.

{$businessData}

## BENCHMARK TAQQOSLASH
{$benchmarkSummary}

## SOGLIQ BALLARI
{$healthSummary}

## VAZIFA
SWOT tahlilini quyidagi formatda yozing. Har bir bo'lim uchun 3-5 ta aniq, amaliy fikr bering:

STRENGTHS (Kuchli tomonlar):
- [Fikr 1]
- [Fikr 2]
- [Fikr 3]

WEAKNESSES (Zaif tomonlar):
- [Fikr 1]
- [Fikr 2]
- [Fikr 3]

OPPORTUNITIES (Imkoniyatlar):
- [Fikr 1]
- [Fikr 2]
- [Fikr 3]

THREATS (Tahdidlar):
- [Fikr 1]
- [Fikr 2]
- [Fikr 3]

Muhim: Fikrlar biznesga xos, aniq va amaliy bo'lsin. Umumiy gaplardan saqlaning.
PROMPT;
    }

    /**
     * Parse SWOT response from AI
     */
    protected function parseSWOTResponse(array $response): array
    {
        $content = $response['content'] ?? '';

        $swot = [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];

        // Parse each section
        $sections = [
            'strengths' => ['STRENGTHS', 'Kuchli tomonlar'],
            'weaknesses' => ['WEAKNESSES', 'Zaif tomonlar'],
            'opportunities' => ['OPPORTUNITIES', 'Imkoniyatlar'],
            'threats' => ['THREATS', 'Tahdidlar'],
        ];

        foreach ($sections as $key => $patterns) {
            $items = $this->extractListItems($content, $patterns);
            $swot[$key] = array_slice($items, 0, 5); // Max 5 items
        }

        return $swot;
    }

    /**
     * Extract list items from text
     */
    protected function extractListItems(string $content, array $sectionPatterns): array
    {
        $items = [];

        foreach ($sectionPatterns as $pattern) {
            // Find section start
            $pos = stripos($content, $pattern);
            if ($pos === false) continue;

            // Extract content after section header
            $sectionContent = substr($content, $pos);

            // Find next section or end
            $nextSection = PHP_INT_MAX;
            foreach (['STRENGTHS', 'WEAKNESSES', 'OPPORTUNITIES', 'THREATS'] as $next) {
                if (stripos($pattern, $next) === false) {
                    $nextPos = stripos($sectionContent, $next, 10);
                    if ($nextPos !== false && $nextPos < $nextSection) {
                        $nextSection = $nextPos;
                    }
                }
            }

            if ($nextSection < PHP_INT_MAX) {
                $sectionContent = substr($sectionContent, 0, $nextSection);
            }

            // Extract bullet points
            preg_match_all('/[-•*]\s*(.+?)(?=\n[-•*]|\n\n|$)/s', $sectionContent, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $item = trim($match);
                    if (strlen($item) > 10) { // Filter out too short items
                        $items[] = $item;
                    }
                }
            }

            if (!empty($items)) break;
        }

        return $items;
    }

    /**
     * Generate AI insights
     */
    protected function generateAIInsights(
        Business $business,
        array $aggregatedData,
        array $benchmarkComparison,
        array $healthScore
    ): array {
        $formattedData = $this->dataAggregator->getFormattedForAI($business);

        $prompt = $this->buildInsightsPrompt($formattedData, $healthScore);

        try {
            $response = $this->claudeService->sendMessage($prompt, 'diagnostic_insights');

            return [
                'insights' => $response['content'] ?? '',
                'tokens' => $this->estimateTokens($prompt . ($response['content'] ?? '')),
            ];
        } catch (\Exception $e) {
            Log::warning('Insights generation failed', ['error' => $e->getMessage()]);

            return [
                'insights' => $this->getFallbackInsights($healthScore),
                'tokens' => 0,
            ];
        }
    }

    /**
     * Build insights prompt
     */
    protected function buildInsightsPrompt(string $businessData, array $healthScore): string
    {
        $overallScore = $healthScore['overall_score'] ?? 50;
        $weakest = $healthScore['weakest_category']['label'] ?? 'noma\'lum';

        return <<<PROMPT
Siz O'zbekistondagi bizneslar uchun marketing strategiya bo'yicha katta tajribaga ega maslahatchisiz.

Quyidagi biznes ma'lumotlarini chuqur tahlil qilib, 3-4 paragraflik professional xulosa yozing.

{$businessData}

Umumiy sog'liq balli: {$overallScore}/100
Eng zaif yo'nalish: {$weakest}

## VAZIFA
Quyidagilarni yoritib bering:
1. Biznesning hozirgi holati haqida qisqacha baho
2. Eng muhim kuchli tomonlar va ulardan qanday foydalanish
3. Eng katta muammolar va ularni hal qilish yo'llari
4. Kelgusi 90 kun uchun eng muhim 2-3 ta qadam

Yozing professional, lekin sodda tilda. Texnik atamalarni ishlatmang.
O'zbek tilida yozing.
PROMPT;
    }

    /**
     * Generate follow-up questions
     */
    protected function generateFollowUpQuestions(
        Business $business,
        array $aggregatedData,
        ?array $swot
    ): array {
        $prompt = $this->buildQuestionsPrompt($aggregatedData, $swot);

        try {
            $response = $this->claudeService->sendMessage($prompt, 'diagnostic_questions');

            $questions = $this->parseQuestionsResponse($response);

            return [
                'questions' => $questions,
                'tokens' => $this->estimateTokens($prompt . ($response['content'] ?? '')),
            ];
        } catch (\Exception $e) {
            Log::warning('Questions generation failed', ['error' => $e->getMessage()]);

            return [
                'questions' => $this->getFallbackQuestions(),
                'tokens' => 0,
            ];
        }
    }

    /**
     * Build questions prompt
     */
    protected function buildQuestionsPrompt(array $data, ?array $swot): string
    {
        $maturityLevel = $data['maturity']['level'] ?? 'unknown';
        $problems = count($data['problems']['details'] ?? []);
        $weaknesses = implode(', ', $swot['weaknesses'] ?? []);

        return <<<PROMPT
Siz biznes diagnostika mutaxassisisiz. Quyidagi ma'lumotlar asosida biznes egasiga berilishi kerak bo'lgan 5 ta muhim savolni yozing.

Maturity darajasi: {$maturityLevel}
Muammolar soni: {$problems}
Zaif tomonlar: {$weaknesses}

## VAZIFA
5 ta savol yozing. Har bir savol:
- Aniq va tushunarli bo'lsin
- Biznesning hozirgi holatini yaxshiroq tushunishga yordam bersin
- Ha/Yo'q javob emas, tafsilotli javob talab qilsin
- Amaliy ahamiyatga ega bo'lsin

Format:
1. [Savol 1]
2. [Savol 2]
3. [Savol 3]
4. [Savol 4]
5. [Savol 5]

O'zbek tilida yozing.
PROMPT;
    }

    /**
     * Parse questions response
     */
    protected function parseQuestionsResponse(array $response): array
    {
        $content = $response['content'] ?? '';
        $questions = [];

        // Extract numbered items
        preg_match_all('/\d+\.\s*(.+?)(?=\n\d+\.|\n\n|$)/s', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $question) {
                $question = trim($question);
                if (strlen($question) > 10) {
                    $questions[] = [
                        'id' => $index + 1,
                        'question' => $question,
                        'category' => $this->categorizeQuestion($question),
                        'priority' => $index < 2 ? 'high' : 'medium',
                    ];
                }
            }
        }

        return array_slice($questions, 0, 5);
    }

    /**
     * Categorize question by content
     */
    protected function categorizeQuestion(string $question): string
    {
        $keywords = [
            'marketing' => ['marketing', 'reklama', 'brend', 'auditoriya', 'kontent'],
            'sales' => ['sotuv', 'mijoz', 'konversiya', 'deal', 'narx'],
            'operations' => ['jamoa', 'jarayon', 'vaqt', 'resurs', 'tizim'],
            'finance' => ['byudjet', 'pul', 'xarajat', 'foyda', 'investitsiya'],
            'strategy' => ['reja', 'maqsad', 'strategiya', 'yo\'nalish', 'kelajak'],
        ];

        $question = mb_strtolower($question);

        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (mb_strpos($question, $word) !== false) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    /**
     * Format benchmark for prompt
     */
    protected function formatBenchmarkForPrompt(array $benchmarkComparison): string
    {
        $lines = [];

        foreach ($benchmarkComparison as $metric => $data) {
            $status = $data['status_label'] ?? $data['status'] ?? 'unknown';
            $value = $data['formatted_value'] ?? $data['value'] ?? 'N/A';
            $benchmark = $data['formatted_benchmark'] ?? $data['benchmark_average'] ?? 'N/A';
            $name = $data['metric_name'] ?? $metric;

            $lines[] = "- {$name}: {$value} (soha o'rtachasi: {$benchmark}) - {$status}";
        }

        return implode("\n", $lines);
    }

    /**
     * Format health score for prompt
     */
    protected function formatHealthScoreForPrompt(array $healthScore): string
    {
        $lines = ["Umumiy ball: {$healthScore['overall_score']}/100"];

        foreach ($healthScore['category_scores'] ?? [] as $category => $data) {
            $label = match ($category) {
                'marketing' => 'Marketing',
                'sales' => 'Sotuvlar',
                'content' => 'Kontent',
                'funnel' => 'Funnel',
                'analytics' => 'Analitika',
                default => $category,
            };
            $lines[] = "- {$label}: {$data['score']}/100";
        }

        return implode("\n", $lines);
    }

    /**
     * Estimate tokens from text
     */
    protected function estimateTokens(string $text): int
    {
        // Rough estimate: 1 token ≈ 4 characters for English, ~2-3 for Cyrillic/Uzbek
        return (int) ceil(mb_strlen($text) / 3);
    }

    /**
     * Fallback SWOT when AI fails
     */
    protected function getFallbackSWOT(array $data, array $healthScore): array
    {
        $swot = [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];

        // Generate from maturity data
        if (isset($data['maturity']['strengths'])) {
            $swot['strengths'] = array_slice($data['maturity']['strengths'], 0, 3);
        }

        if (isset($data['maturity']['weaknesses'])) {
            $swot['weaknesses'] = array_slice($data['maturity']['weaknesses'], 0, 3);
        }

        // Default items if empty
        if (empty($swot['strengths'])) {
            $swot['strengths'] = ['Ma\'lumotlar yetarli emas'];
        }

        if (empty($swot['weaknesses'])) {
            $swot['weaknesses'] = ['Ma\'lumotlar yetarli emas'];
        }

        if (empty($swot['opportunities'])) {
            $swot['opportunities'] = ['Bozorni kengaytirish imkoniyati', 'Raqamli marketing orqali o\'sish'];
        }

        if (empty($swot['threats'])) {
            $swot['threats'] = ['Raqobat kuchayishi', 'Bozor o\'zgarishlari'];
        }

        return $swot;
    }

    /**
     * Fallback insights when AI fails
     */
    protected function getFallbackInsights(array $healthScore): string
    {
        $score = $healthScore['overall_score'] ?? 50;
        $weakest = $healthScore['weakest_category']['label'] ?? 'noma\'lum';

        if ($score >= 70) {
            return "Sizning biznesingiz yaxshi holatda ({$score}/100). Asosiy yo'nalishlar bo'yicha barqaror rivojlanish kuzatilmoqda. {$weakest} yo'nalishiga e'tibor qarating va mavjud kuchli tomonlarni yanada rivojlantiring.";
        }

        if ($score >= 50) {
            return "Biznesingiz o'rtacha holatda ({$score}/100). {$weakest} yo'nalishi eng zaif nuqta hisoblanadi va unga ustuvor e'tibor berish lozim. Tavsiyalarni bosqichma-bosqich amalga oshiring.";
        }

        return "Biznesingiz rivojlanish uchun jiddiy sa'y-harakatlarga muhtoj ({$score}/100). {$weakest} yo'nalishidan boshlab, tizimli yondashuvni joriy qilish tavsiya etiladi. Professional yordam olishni ko'rib chiqing.";
    }

    /**
     * Fallback questions when AI fails
     */
    protected function getFallbackQuestions(): array
    {
        return [
            [
                'id' => 1,
                'question' => 'Hozirgi paytda eng katta marketing muammoingiz nima?',
                'category' => 'marketing',
                'priority' => 'high',
            ],
            [
                'id' => 2,
                'question' => 'Sotuv jarayoningizda qaysi bosqichda eng ko\'p mijoz yo\'qotiladi?',
                'category' => 'sales',
                'priority' => 'high',
            ],
            [
                'id' => 3,
                'question' => 'Kelgusi 6 oy ichida qanday natijaga erishmoqchisiz?',
                'category' => 'strategy',
                'priority' => 'medium',
            ],
            [
                'id' => 4,
                'question' => 'Marketing uchun oylik byudjetingiz qancha?',
                'category' => 'finance',
                'priority' => 'medium',
            ],
            [
                'id' => 5,
                'question' => 'Jamoangizdagi eng katta muammo nima?',
                'category' => 'operations',
                'priority' => 'medium',
            ],
        ];
    }
}
