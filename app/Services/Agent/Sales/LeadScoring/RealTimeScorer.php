<?php

namespace App\Services\Agent\Sales\LeadScoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Real vaqtda lead baholash (qoidaga asoslangan, bepul).
 * Har bir xabardan keyin lead ballini qayta hisoblaydi.
 */
class RealTimeScorer
{
    // Harakat uchun ball o'zgarishi
    private const SCORE_MAP = [
        'first_message' => 5,
        'product_inquiry' => 10,
        'price_inquiry' => 15,
        'payment_question' => 20,
        'free_material' => 10,
        'hesitation' => -5,      // "o'ylab ko'raman"
        'price_objection' => -3, // "qimmat"
        'continued_after_objection' => 10,
        'order_placed' => 30,
        'greeting_only' => 2,
    ];

    /**
     * Xabar turiga qarab ball o'zgartirish
     *
     * @return array{new_score: int, change: int, action: string|null}
     */
    public function scoreMessage(
        string $businessId,
        ?string $leadId,
        string $messageType,
        ?string $objectionType = null,
        int $currentScore = 0,
    ): array {
        // Ball o'zgarishni aniqlash
        $change = match ($messageType) {
            'greeting' => self::SCORE_MAP['greeting_only'],
            'product' => self::SCORE_MAP['product_inquiry'],
            'price' => self::SCORE_MAP['price_inquiry'],
            'order' => self::SCORE_MAP['order_placed'],
            'objection' => $this->getObjectionScore($objectionType),
            'complex' => self::SCORE_MAP['product_inquiry'], // murakkab savol = qiziqish
            default => 0,
        };

        $newScore = max(0, min(100, $currentScore + $change));

        // Ball bo'yicha harakat
        $action = $this->determineAction($newScore);

        // Agar lead mavjud bo'lsa — ballni yangilash
        if ($leadId) {
            $this->updateLeadScore($businessId, $leadId, $currentScore, $newScore, $messageType);
        }

        return [
            'new_score' => $newScore,
            'change' => $change,
            'action' => $action,
        ];
    }

    /**
     * E'tiroz turga qarab ball
     */
    private function getObjectionScore(?string $objectionType): int
    {
        return match ($objectionType) {
            'price' => self::SCORE_MAP['price_objection'],
            'timing' => self::SCORE_MAP['hesitation'],
            'trust' => self::SCORE_MAP['hesitation'],
            'need' => -8,
            default => self::SCORE_MAP['hesitation'],
        };
    }

    /**
     * Ball bo'yicha qanday harakat qilish kerak
     */
    private function determineAction(int $score): ?string
    {
        return match (true) {
            $score >= 76 => 'notify_operator_hot',   // Issiq — operatorga darhol xabar
            $score >= 51 => 'intensify_sales',        // Qiziq — sotuv yondashuvini kuchaytirish
            $score >= 26 => 'send_targeted_content',  // Iliq — maqsadli kontent
            default => 'nurture_sequence',            // Sovuq — qayta aloqa ketma-ketligi
        };
    }

    /**
     * Lead balini bazada yangilash va tarix saqlash
     */
    private function updateLeadScore(string $businessId, string $leadId, int $before, int $after, string $reason): void
    {
        try {
            // Lead jadvalida ballni yangilash (agar lead_score ustuni bo'lsa)
            DB::table('leads')
                ->where('id', $leadId)
                ->where('business_id', $businessId)
                ->update(['score' => $after, 'updated_at' => now()]);
        } catch (\Exception $e) {
            // Lead yangilash xatosi asosiy jarayonni to'xtatmasligi kerak
            Log::warning('RealTimeScorer: lead yangilash xatosi', [
                'lead_id' => $leadId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
