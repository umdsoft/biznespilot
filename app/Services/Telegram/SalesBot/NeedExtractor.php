<?php

namespace App\Services\Telegram\SalesBot;

use App\Models\CustomerNeedProfile;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * NeedExtractor — har foydalanuvchi xabaridan AI orqali ehtiyojni "extract" qiladi.
 *
 * Vazifasi:
 *   - Mijozning so'zidan: nima izlashi, byudjet, o'lcham, brand preferences
 *   - Hozirgi profile bilan qo'shib, profile_completeness'ni hisoblaydi
 *   - Faqat O'ZGARGAN maydonlarni qaytaradi (token tejash)
 *
 * AI: Claude Haiku — tezkor, arzon (~$0.0005 per call).
 */
class NeedExtractor
{
    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Mijoz xabarini tahlil qilib, profile yangilanishini qaytaradi.
     *
     * @param  string                 $message       Foydalanuvchi xabari
     * @param  CustomerNeedProfile    $profile       Mavjud profile
     * @return array                  Yangilangan maydonlar (massiv)
     */
    public function extract(string $message, CustomerNeedProfile $profile): array
    {
        $existing = json_encode([
            'primary_intent' => $profile->primary_intent,
            'use_case' => $profile->use_case,
            'constraints' => $profile->constraints ?? [],
            'info_completeness' => (float) $profile->info_completeness,
        ], JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<'TXT'
Sen mijoz xabarlarini tahlil qiluvchi AI'san. Vazifang — har xabardan
mijoz EHTIYOJI haqida ma'lumot extract qilish.

QAYTARISH FORMATI: Faqat JSON (boshqa hech narsa yo'q):
{
  "primary_intent": "string yoki null",
  "use_case": "string yoki null",
  "constraints": {
    "budget_min": number yoki null,
    "budget_max": number yoki null,
    "size": "string yoki null",
    "color": "string yoki null",
    "brand": "string yoki null",
    "preferences": ["yengil","qulay","..."],
    "avoid": ["..."]
  },
  "info_completeness": 0.0-1.0,
  "ready_to_buy": boolean,
  "objection": "string yoki null"
}

QOIDALAR:
- Faqat mijoz O'Z so'zidan kelgan ma'lumotni yoz. Taxmin qilma.
- Agar yangi ma'lumot bo'lmasa, mavjudini o'zgartirmasdan qaytar.
- info_completeness = qancha ma'lumot to'planganini baholash:
  * 0.0 — hech narsa
  * 0.3 — primary intent bor
  * 0.5 — intent + 1 ta cheklov (use_case yoki budget)
  * 0.7 — intent + 2-3 ta cheklov
  * 0.9 — yetarli
- Agar mijoz "shu mahsulotni xohlayman" desa: ready_to_buy = true.
- Agar e'tiroz (narx qimmat, ishonmayman) bo'lsa — objection ga yoz.
- Uzbek tilida yozilgan bo'lsa — values ham uzbek.
TXT;

        $prompt = <<<TXT
HOZIRGI PROFIL:
{$existing}

MIJOZ YANGI XABARI:
"{$message}"

Yangilangan profilni JSON shaklida qaytar.
TXT;

        try {
            $response = $this->aiService->ask(
                prompt: $prompt,
                systemPrompt: $systemPrompt,
                preferredModel: 'haiku',
                maxTokens: 400,
                businessId: $profile->business_id,
                agentType: 'sales_bot_need_extractor',
            );

            if (! $response->success) {
                Log::warning('NeedExtractor: AI fail', ['error' => $response->error]);
                return [];
            }

            $parsed = $this->parseJson($response->content);
            return $parsed ?? [];

        } catch (\Throwable $e) {
            Log::warning('NeedExtractor: Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Mijoz xabaridan e'tiroz turini aniqlash (narx, ishonch, yetkazib berish).
     */
    public function detectObjection(string $message): ?string
    {
        $lowered = mb_strtolower($message);
        $patterns = [
            'narx' => ['qimmat', 'narx baland', 'arzonroq', 'pul yo\'q'],
            'ishonch' => ['ishon', 'haqiqiymi', 'asl', 'kafolat'],
            'yetkazish' => ['qachon yetkaz', 'kechikish', 'tez kerak'],
            'razmer' => ['razmer', 'o\'lcham', 'mosmasmi'],
        ];

        foreach ($patterns as $type => $words) {
            foreach ($words as $w) {
                if (str_contains($lowered, $w)) return $type;
            }
        }

        return null;
    }

    /**
     * AI javobidan JSON ni parse qilish (markdown wrap bo'lsa ham).
     */
    private function parseJson(string $raw): ?array
    {
        $clean = trim($raw);

        // ```json ... ``` wrap'ni olib tashlash
        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $clean, $m)) {
            $clean = $m[1];
        }

        $decoded = json_decode($clean, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // {} brackets'ni topish
        if (preg_match('/\{.*\}/s', $clean, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }
}
