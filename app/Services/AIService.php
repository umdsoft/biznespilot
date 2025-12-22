<?php

namespace App\Services;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;

class AIService
{
    protected $openAIService;
    protected $claudeService;
    protected $userSettings = null;

    public function __construct(OpenAIService $openAI, ClaudeService $claude)
    {
        $this->openAIService = $openAI;
        $this->claudeService = $claude;

        // Try to get user settings if user is authenticated
        if (Auth::check()) {
            $this->userSettings = UserSetting::where('user_id', Auth::id())
                ->where('key', 'ai_settings')
                ->first();
        }
    }

    /**
     * Get the active AI service based on user preference and available API keys
     */
    protected function getActiveService()
    {
        $preferredModel = $this->userSettings?->preferred_ai_model ?? 'gpt-4';
        $openaiKey = $this->userSettings?->openai_api_key ?? config('services.openai.key');
        $claudeKey = $this->userSettings?->claude_api_key ?? config('services.anthropic.key');

        // Check if Claude is preferred and available
        if (str_contains($preferredModel, 'claude') && $claudeKey) {
            return $this->claudeService;
        }

        // Check if OpenAI is preferred and available
        if (str_contains($preferredModel, 'gpt') && $openaiKey) {
            return $this->openAIService;
        }

        // Fallback: use whichever service has an API key
        if ($openaiKey) {
            return $this->openAIService;
        }

        if ($claudeKey) {
            return $this->claudeService;
        }

        // Default to OpenAI service (will show proper error message when called)
        return $this->openAIService;
    }

    /**
     * Generate text completion
     */
    public function generateText(string $prompt, array $context = []): string
    {
        $service = $this->getActiveService();
        $creativity = $this->userSettings?->ai_creativity_level ?? 7;

        return $service->generateText($prompt, $context, $creativity);
    }

    /**
     * Analyze Dream Buyer and generate insights
     */
    public function analyzeDreamBuyer(array $dreamBuyerData): array
    {
        $prompt = $this->buildDreamBuyerPrompt($dreamBuyerData);
        $response = $this->generateText($prompt);

        return [
            'insights' => $response,
            'recommendations' => $this->extractRecommendations($response),
        ];
    }

    /**
     * Generate marketing content
     */
    public function generateMarketingContent(string $contentType, array $context): string
    {
        $prompt = $this->buildMarketingContentPrompt($contentType, $context);
        return $this->generateText($prompt, $context);
    }

    /**
     * Analyze competitor and generate insights
     */
    public function analyzeCompetitor(array $competitorData): array
    {
        $prompt = $this->buildCompetitorAnalysisPrompt($competitorData);
        $response = $this->generateText($prompt);

        return [
            'analysis' => $response,
            'swot_suggestions' => $this->extractSWOTSuggestions($response),
        ];
    }

    /**
     * Optimize offer and generate recommendations
     */
    public function optimizeOffer(array $offerData): array
    {
        $prompt = $this->buildOfferOptimizationPrompt($offerData);
        $response = $this->generateText($prompt);

        return [
            'optimizations' => $response,
            'usp_suggestions' => $this->extractUSPSuggestions($response),
        ];
    }

    /**
     * General business advice
     */
    public function getBusinessAdvice(string $question, array $context = []): string
    {
        $prompt = "Siz professional biznes maslahatchi sifatida quyidagi savolga javob bering:\n\n";
        $prompt .= "Savol: {$question}\n\n";

        if (!empty($context)) {
            $prompt .= "Kontekst:\n" . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n\n";
        }

        $prompt .= "O'zbek tilida aniq va amaliy maslahat bering.";

        return $this->generateText($prompt, $context);
    }

    /**
     * Build Dream Buyer analysis prompt
     */
    protected function buildDreamBuyerPrompt(array $data): string
    {
        $prompt = "Siz professional marketing mutaxassisi sifatida quyidagi 'Dream Buyer' (ideal mijoz) ma'lumotlarini tahlil qiling:\n\n";

        if (isset($data['name'])) {
            $prompt .= "Nom: {$data['name']}\n";
        }
        if (isset($data['demographics'])) {
            $prompt .= "Demografiya: {$data['demographics']}\n";
        }
        if (isset($data['psychographics'])) {
            $prompt .= "Psixografiya: {$data['psychographics']}\n";
        }
        if (isset($data['goals'])) {
            $prompt .= "Maqsadlar: {$data['goals']}\n";
        }
        if (isset($data['challenges'])) {
            $prompt .= "Muammolar: {$data['challenges']}\n";
        }
        if (isset($data['values'])) {
            $prompt .= "Qadriyatlar: {$data['values']}\n";
        }

        $prompt .= "\nQuyidagilarni taqdim eting:\n";
        $prompt .= "1. Ushbu mijoz profili haqida batafsil tahlil\n";
        $prompt .= "2. Marketing strategiyasi tavsiyalari\n";
        $prompt .= "3. Muloqot kanallari va uslublari\n";
        $prompt .= "4. Mahsulot/xizmat taklif qilish yo'llari\n\n";
        $prompt .= "Javobni o'zbek tilida, aniq va amaliy qilib bering.";

        return $prompt;
    }

    /**
     * Build marketing content generation prompt
     */
    protected function buildMarketingContentPrompt(string $contentType, array $context): string
    {
        $prompt = "Siz professional kontent yozuvchi sifatida quyidagi turdagi kontent yarating:\n\n";
        $prompt .= "Kontent turi: {$contentType}\n\n";

        if (isset($context['target_audience'])) {
            $prompt .= "Maqsadli auditoriya: {$context['target_audience']}\n";
        }
        if (isset($context['topic'])) {
            $prompt .= "Mavzu: {$context['topic']}\n";
        }
        if (isset($context['tone'])) {
            $prompt .= "Ohang: {$context['tone']}\n";
        }
        if (isset($context['keywords'])) {
            $prompt .= "Kalit so'zlar: " . implode(', ', $context['keywords']) . "\n";
        }

        $prompt .= "\nKontent professional, qiziqarli va maqsadli auditoriyaga mos bo'lishi kerak.";
        $prompt .= "\nO'zbek tilida yozing.";

        return $prompt;
    }

    /**
     * Build competitor analysis prompt
     */
    protected function buildCompetitorAnalysisPrompt(array $data): string
    {
        $prompt = "Siz biznes tahlilchi sifatida quyidagi raqobatchi haqida SWOT tahlil qiling:\n\n";

        if (isset($data['name'])) {
            $prompt .= "Raqobatchi: {$data['name']}\n";
        }
        if (isset($data['website'])) {
            $prompt .= "Veb-sayt: {$data['website']}\n";
        }
        if (isset($data['products'])) {
            $prompt .= "Mahsulotlar: " . implode(', ', $data['products']) . "\n";
        }
        if (isset($data['pricing'])) {
            $prompt .= "Narxlash: " . implode(', ', $data['pricing']) . "\n";
        }
        if (isset($data['marketing_strategies'])) {
            $prompt .= "Marketing strategiyalari: " . implode(', ', $data['marketing_strategies']) . "\n";
        }

        $prompt .= "\nQuyidagilarni taqdim eting:\n";
        $prompt .= "1. Kuchli tomonlar (Strengths)\n";
        $prompt .= "2. Zaif tomonlar (Weaknesses)\n";
        $prompt .= "3. Imkoniyatlar (Opportunities)\n";
        $prompt .= "4. Tahdidlar (Threats)\n";
        $prompt .= "5. Raqobatdan ajralib turish bo'yicha tavsiyalar\n\n";
        $prompt .= "O'zbek tilida, aniq va strategik javob bering.";

        return $prompt;
    }

    /**
     * Build offer optimization prompt
     */
    protected function buildOfferOptimizationPrompt(array $data): string
    {
        $prompt = "Siz marketing va sales mutaxassisi sifatida quyidagi taklifni optimallashtiring:\n\n";

        if (isset($data['name'])) {
            $prompt .= "Taklif: {$data['name']}\n";
        }
        if (isset($data['value_proposition'])) {
            $prompt .= "Qiymat taklifi (USP): {$data['value_proposition']}\n";
        }
        if (isset($data['pricing'])) {
            $prompt .= "Narx: {$data['pricing']}\n";
        }
        if (isset($data['target_audience'])) {
            $prompt .= "Maqsadli auditoriya: {$data['target_audience']}\n";
        }

        $prompt .= "\nQuyidagilarni taqdim eting:\n";
        $prompt .= "1. USP (Unique Selling Proposition) yaxshilash tavsiyalari\n";
        $prompt .= "2. Narxlash strategiyasi tavsiyalari\n";
        $prompt .= "3. Kafolatlar va bonuslar qo'shish g'oyalari\n";
        $prompt .= "4. Kamlik va shoshilinchlik elementlari\n";
        $prompt .= "5. Konversiyani oshirish bo'yicha tavsiyalar\n\n";
        $prompt .= "O'zbek tilida, amaliy va konkret javob bering.";

        return $prompt;
    }

    /**
     * Extract recommendations from AI response
     */
    protected function extractRecommendations(string $response): array
    {
        // Simple extraction - can be enhanced with more sophisticated parsing
        $recommendations = [];
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            if (preg_match('/^[0-9\-\*]\s+(.+)/', trim($line), $matches)) {
                $recommendations[] = trim($matches[1]);
            }
        }

        return $recommendations;
    }

    /**
     * Extract SWOT suggestions from AI response
     */
    protected function extractSWOTSuggestions(string $response): array
    {
        return [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];
    }

    /**
     * Extract USP suggestions from AI response
     */
    protected function extractUSPSuggestions(string $response): array
    {
        return $this->extractRecommendations($response);
    }
}
