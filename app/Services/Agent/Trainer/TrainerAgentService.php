<?php

namespace App\Services\Agent\Trainer;

use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI xodim o'qituvchi — sotuv xodimlarini sun'iy aql orqali mashq qildirish.
 * Agent mijoz rolini o'ynaydi, xodim sotuvchi bo'lib mashq qiladi.
 *
 * Gibrid: 60% qoidaga asoslangan baholash + 40% Haiku
 */
class TrainerAgentService
{
    // Qoidaga asoslangan baholash mezonlari
    private const RULE_SCORES = [
        'mentioned_price_first' => -5,      // Narxni birinchi aytdi — yomon
        'asked_question' => 3,              // Savol berdi — yaxshi
        'said_think_about_it' => -10,       // "O'ylab ko'ring" dedi — juda yomon
        'mentioned_guarantee' => 5,          // Kafolat aytdi — yaxshi
        'used_customer_name' => 3,          // Mijoz ismini ishlatdi — yaxshi
        'showed_value_before_price' => 5,   // Qiymatni narxdan oldin ko'rsatdi
        'offered_alternatives' => 4,         // Alternativa taklif qildi
    ];

    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Yangi mashq sessiyasini boshlash
     */
    public function startSession(string $businessId, string $traineeUserId, string $sessionType): array
    {
        try {
            $industry = DB::table('businesses')->where('id', $businessId)->value('industry_code') ?? 'general';

            // Birinchi xabar — agent mijoz rolida
            $firstMessage = $this->getOpeningMessage($sessionType, $industry);

            $sessionId = Str::uuid()->toString();
            DB::table('training_sessions')->insert([
                'id' => $sessionId,
                'business_id' => $businessId,
                'trainee_user_id' => $traineeUserId,
                'session_type' => $sessionType,
                'status' => 'active',
                'messages' => json_encode([
                    ['role' => 'customer', 'content' => $firstMessage, 'timestamp' => now()->toISOString()],
                ]),
                'ai_tokens_used' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'customer_message' => $firstMessage,
                'session_type' => $sessionType,
            ];

        } catch (\Exception $e) {
            Log::error('Trainer: sessiya boshlash xatosi', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Xodim javobini qayta ishlash
     */
    public function processTraineeResponse(string $sessionId, string $response): array
    {
        try {
            $session = DB::table('training_sessions')->where('id', $sessionId)->first();
            if (!$session || $session->status !== 'active') {
                return ['success' => false, 'error' => 'Sessiya topilmadi yoki tugagan'];
            }

            $messages = json_decode($session->messages, true) ?? [];

            // 1. Qoidaga asoslangan baholash (bepul)
            $ruleScore = $this->evaluateWithRules($response);

            // 2. AI bilan baholash va keyingi qadam
            $aiResult = $this->evaluateAndRespond($session, $messages, $response);

            // 3. Xabarlarni yangilash
            $messages[] = ['role' => 'trainee', 'content' => $response, 'score' => $ruleScore, 'timestamp' => now()->toISOString()];
            $messages[] = ['role' => 'customer', 'content' => $aiResult['next_message'], 'feedback' => $aiResult['feedback'], 'timestamp' => now()->toISOString()];

            DB::table('training_sessions')->where('id', $sessionId)->update([
                'messages' => json_encode($messages),
                'ai_tokens_used' => $session->ai_tokens_used + ($aiResult['tokens'] ?? 0),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'feedback' => $aiResult['feedback'],
                'score' => $ruleScore,
                'next_message' => $aiResult['next_message'],
                'should_continue' => $aiResult['should_continue'],
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Mashq sessiyasini yakunlash
     */
    public function endSession(string $sessionId): array
    {
        try {
            $session = DB::table('training_sessions')->where('id', $sessionId)->first();
            if (!$session) return ['success' => false, 'error' => 'Sessiya topilmadi'];

            $messages = json_decode($session->messages, true) ?? [];
            $traineeMessages = array_filter($messages, fn ($m) => ($m['role'] ?? '') === 'trainee');

            // Umumiy ball hisoblash
            $scores = array_column($traineeMessages, 'score');
            $avgScore = count($scores) > 0 ? (int) round(array_sum($scores) / count($scores)) : 0;
            $overallScore = min(100, max(0, 50 + $avgScore)); // 50 + o'rtacha ball

            // Yakuniy hisobot — Haiku
            $report = $this->generateReport($session, $messages, $overallScore);

            DB::table('training_sessions')->where('id', $sessionId)->update([
                'status' => 'completed',
                'overall_score' => $overallScore,
                'strengths' => json_encode($report['strengths'] ?? []),
                'improvements' => json_encode($report['improvements'] ?? []),
                'recommended_next_session' => $report['recommended_next'] ?? null,
                'duration_seconds' => now()->diffInSeconds($session->created_at),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            // Xodim progressini yangilash
            $this->updateProgress($session->business_id, $session->trainee_user_id, $overallScore);

            return [
                'success' => true,
                'overall_score' => $overallScore,
                'report' => $report,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Qoidaga asoslangan baholash (bepul)
     */
    private function evaluateWithRules(string $response): int
    {
        $normalized = mb_strtolower($response);
        $score = 0;

        if (preg_match('/\d+\s*(so\'m|sum|ming|million)/', $normalized)) $score += self::RULE_SCORES['mentioned_price_first'];
        if (str_contains($normalized, '?')) $score += self::RULE_SCORES['asked_question'];
        if (str_contains($normalized, "o'ylab ko'ring") || str_contains($normalized, 'подумайте')) $score += self::RULE_SCORES['said_think_about_it'];
        if ($this->containsAny($normalized, ['kafolat', 'garantiya', 'гарантия'])) $score += self::RULE_SCORES['mentioned_guarantee'];
        if ($this->containsAny($normalized, ['qiymat', 'foyda', 'natija', 'результат'])) $score += self::RULE_SCORES['showed_value_before_price'];

        return $score;
    }

    /**
     * AI bilan baholash va javob yaratish
     */
    private function evaluateAndRespond(object $session, array $messages, string $response): array
    {
        $lastMessages = array_slice($messages, -4);
        $context = implode("\n", array_map(fn ($m) => ($m['role'] ?? '') . ": " . ($m['content'] ?? ''), $lastMessages));

        $aiResponse = $this->aiService->ask(
            prompt: "Mashq turi: {$session->session_type}\nSuhbat:\n{$context}\n\nXodim javobi: {$response}\n\n"
                . "1) Javobni 1-2 gap bilan bahola\n2) Mijoz sifatida keyingi gapingni yoz (suhbat davom etsin)\n3) Suhbat davom etsinmi? (ha/yo'q)\n\n"
                . "JSON: {\"feedback\":\"...\",\"next_message\":\"...\",\"should_continue\":true/false}",
            systemPrompt: "Sen sotuv mashq agentisan. Mijoz rolini o'ynaysan. Xodimning javobini bahola va suhbatni davom ettir. O'zbek tilida. JSON formatda javob ber.",
            preferredModel: 'haiku',
            maxTokens: 300,
            businessId: $session->business_id,
            agentType: 'trainer',
        );

        if (!$aiResponse->success) {
            return ['feedback' => 'Baholash vaqtinchalik mavjud emas', 'next_message' => 'Davom eting...', 'should_continue' => true, 'tokens' => 0];
        }

        $parsed = $this->parseJson($aiResponse->content);

        return [
            'feedback' => $parsed['feedback'] ?? 'Yaxshi javob!',
            'next_message' => $parsed['next_message'] ?? 'Tushundim, davom eting.',
            'should_continue' => $parsed['should_continue'] ?? true,
            'tokens' => $aiResponse->tokensInput + $aiResponse->tokensOutput,
        ];
    }

    /**
     * Yakuniy hisobot yaratish
     */
    private function generateReport(object $session, array $messages, int $overallScore): array
    {
        return [
            'overall_score' => $overallScore,
            'strengths' => $overallScore > 60 ? ['Suhbatni davom ettirish', 'Javob tezligi'] : [],
            'improvements' => $overallScore < 70 ? ["E'tiroz bartaraf qilish", 'Yakunlash texnikasi'] : [],
            'recommended_next' => $overallScore < 50 ? 'objection' : ($overallScore < 70 ? 'closing' : 'full'),
            'total_messages' => count($messages),
        ];
    }

    /**
     * Xodim progressini yangilash
     */
    private function updateProgress(string $businessId, string $userId, int $score): void
    {
        $existing = DB::table('trainee_progress')
            ->where('business_id', $businessId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $newTotal = $existing->total_sessions + 1;
            $newAvg = (($existing->avg_score * $existing->total_sessions) + $score) / $newTotal;

            DB::table('trainee_progress')->where('id', $existing->id)->update([
                'total_sessions' => $newTotal,
                'avg_score' => round($newAvg, 2),
                'best_score' => max($existing->best_score, $score),
                'ready_for_live' => $newAvg >= 70 && $newTotal >= 5,
                'last_session_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('trainee_progress')->insert([
                'id' => Str::uuid()->toString(),
                'business_id' => $businessId,
                'user_id' => $userId,
                'total_sessions' => 1,
                'avg_score' => $score,
                'best_score' => $score,
                'ready_for_live' => false,
                'last_session_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Mashq turi bo'yicha birinchi xabar
     */
    private function getOpeningMessage(string $type, string $industry): string
    {
        return match ($type) {
            'greeting' => 'Salom! Sizning xizmatlaringiz haqida eshitdim. Aytib bersangiz.',
            'presentation' => 'Men sizning kurslaringiz haqida bilmoqchiman. Nimalar o\'rgatiladi?',
            'objection' => 'Hmm, bu juda qimmat ekan. Boshqa arzonroq variant yo\'qmi?',
            'closing' => 'Yaxshi, qiziq ekan. Lekin hozir qaror qila olmayman, keyinroq qo\'ng\'iroq qilaman.',
            'full' => 'Assalomu alaykum! Instagram da ko\'rdim sizlarni. Nimalar bilan shug\'ullanasizlar?',
            default => 'Salom! Sizning mahsulotlaringiz haqida bilmoqchiman.',
        };
    }

    private function parseJson(string $content): array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false) {
            $parsed = json_decode(substr($content, $start, $end - $start + 1), true);
            if (is_array($parsed)) return $parsed;
        }
        return [];
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $k) { if (str_contains($text, $k)) return true; }
        return false;
    }
}
