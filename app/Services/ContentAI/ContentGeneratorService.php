<?php

namespace App\Services\ContentAI;

use App\Models\ContentGeneration;
use App\Models\ContentStyleGuide;
use App\Models\ContentTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ContentGeneratorService - AI orqali kontent generatsiya qilish
 *
 * Bu service biznes style guide va namuna postlarga asoslanib,
 * Claude Haiku orqali yangi kontent yaratadi.
 */
class ContentGeneratorService
{
    protected string $apiKey;
    protected string $model = 'claude-3-haiku-20240307';
    protected string $apiUrl = 'https://api.anthropic.com/v1/messages';

    protected ContentStyleGuideService $styleGuideService;

    public function __construct(ContentStyleGuideService $styleGuideService)
    {
        $this->apiKey = config('services.anthropic.api_key', '');
        $this->styleGuideService = $styleGuideService;
    }

    /**
     * Yangi kontent generatsiya qilish
     */
    public function generate(
        string $businessId,
        string $userId,
        string $topic,
        string $contentType = 'post',
        string $purpose = 'engage',
        ?string $targetChannel = null,
        ?string $additionalPrompt = null
    ): ContentGeneration {
        // Generation record yaratish
        $generation = ContentGeneration::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'topic' => $topic,
            'prompt' => $additionalPrompt,
            'content_type' => $contentType,
            'purpose' => $purpose,
            'target_channel' => $targetChannel,
            'status' => 'generating',
            'ai_model' => $this->model,
        ]);

        try {
            // Style guide olish
            $styleGuide = ContentStyleGuide::getOrCreate($businessId);

            // Namuna postlarni topish
            $referenceTemplates = $this->findReferenceTemplates($businessId, $purpose, $targetChannel);

            // Prompt yaratish
            $prompt = $this->buildGenerationPrompt(
                $styleGuide,
                $topic,
                $contentType,
                $purpose,
                $targetChannel,
                $referenceTemplates,
                $additionalPrompt
            );

            // AI dan javob olish
            $response = $this->callClaudeApi($prompt, $styleGuide->creativity_level, 1500);

            // Javobni parse qilish
            $result = $this->parseGenerationResponse($response);

            // Generation ni yangilash
            $generation->update([
                'status' => 'completed',
                'generated_content' => $result['content'],
                'generated_hashtags' => $result['hashtags'] ?? [],
                'generated_variations' => $result['variations'] ?? [],
                'input_tokens' => $result['usage']['input_tokens'] ?? 0,
                'output_tokens' => $result['usage']['output_tokens'] ?? 0,
                'reference_template_ids' => $referenceTemplates->pluck('id')->toArray(),
            ]);

            $generation->updateCost();

            Log::info('ContentGeneratorService: Content generated successfully', [
                'generation_id' => $generation->id,
                'business_id' => $businessId,
                'tokens' => $generation->total_tokens,
                'cost' => $generation->cost_usd,
            ]);

            return $generation;

        } catch (\Exception $e) {
            Log::error('ContentGeneratorService: Generation failed', [
                'generation_id' => $generation->id,
                'error' => $e->getMessage(),
            ]);

            $generation->markFailed($e->getMessage());

            return $generation;
        }
    }

    /**
     * A/B test variantlarini generatsiya qilish
     */
    public function generateVariations(
        string $businessId,
        string $userId,
        string $topic,
        int $variationsCount = 3,
        ?string $targetChannel = null
    ): ContentGeneration {
        $generation = ContentGeneration::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'topic' => $topic,
            'content_type' => 'post',
            'purpose' => 'engage',
            'target_channel' => $targetChannel,
            'status' => 'generating',
            'ai_model' => $this->model,
        ]);

        try {
            $styleGuide = ContentStyleGuide::getOrCreate($businessId);

            $prompt = $this->buildVariationsPrompt($styleGuide, $topic, $variationsCount, $targetChannel);

            $response = $this->callClaudeApi($prompt, 0.9, 2000); // Higher temperature for variety

            $result = $this->parseVariationsResponse($response);

            $generation->update([
                'status' => 'completed',
                'generated_content' => $result['variations'][0]['content'] ?? '',
                'generated_variations' => $result['variations'],
                'input_tokens' => $result['usage']['input_tokens'] ?? 0,
                'output_tokens' => $result['usage']['output_tokens'] ?? 0,
            ]);

            $generation->updateCost();

            return $generation;

        } catch (\Exception $e) {
            $generation->markFailed($e->getMessage());
            return $generation;
        }
    }

    /**
     * Mavjud kontentni qayta yozish
     */
    public function rewrite(
        string $businessId,
        string $userId,
        string $originalContent,
        string $style = 'improve',
        ?string $targetChannel = null
    ): ContentGeneration {
        $generation = ContentGeneration::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'topic' => 'Rewrite: ' . mb_substr($originalContent, 0, 50),
            'prompt' => $originalContent,
            'content_type' => 'post',
            'purpose' => 'engage',
            'target_channel' => $targetChannel,
            'status' => 'generating',
            'ai_model' => $this->model,
        ]);

        try {
            $styleGuide = ContentStyleGuide::getOrCreate($businessId);

            $prompt = $this->buildRewritePrompt($styleGuide, $originalContent, $style, $targetChannel);

            $response = $this->callClaudeApi($prompt, $styleGuide->creativity_level, 1000);

            $result = $this->parseGenerationResponse($response);

            $generation->update([
                'status' => 'completed',
                'generated_content' => $result['content'],
                'generated_hashtags' => $result['hashtags'] ?? [],
                'input_tokens' => $result['usage']['input_tokens'] ?? 0,
                'output_tokens' => $result['usage']['output_tokens'] ?? 0,
            ]);

            $generation->updateCost();

            return $generation;

        } catch (\Exception $e) {
            $generation->markFailed($e->getMessage());
            return $generation;
        }
    }

    /**
     * Hashtag generatsiya qilish
     */
    public function generateHashtags(string $businessId, string $content, int $count = 10): array
    {
        $styleGuide = ContentStyleGuide::getOrCreate($businessId);

        $existingHashtags = $styleGuide->common_hashtags ?? [];

        $prompt = <<<PROMPT
Quyidagi kontent uchun {$count} ta hashtag tavsiya qil.

KONTENT:
{$content}

MAVJUD BRANDLI HASHTAGLAR (imkon bo'lsa ishlatilsin):
{$this->formatArray($existingHashtags)}

Faqat hashtaglarni ro'yxat qilib ber (har biri # bilan):
PROMPT;

        try {
            $response = $this->callClaudeApi($prompt, 0.5, 300);

            preg_match_all('/#[\w\d_]+/u', $response, $matches);

            return array_slice($matches[0], 0, $count);

        } catch (\Exception $e) {
            return array_slice($existingHashtags, 0, $count);
        }
    }

    /**
     * Reference templatelarni topish
     */
    protected function findReferenceTemplates(
        string $businessId,
        string $purpose,
        ?string $channel
    ): \Illuminate\Support\Collection {
        $query = ContentTemplate::where('business_id', $businessId)
            ->usable()
            ->orderByPerformance();

        // Purpose bo'yicha filter
        if ($purpose) {
            $query->where(function ($q) use ($purpose) {
                $q->where('purpose', $purpose)
                    ->orWhere('is_top_performer', true);
            });
        }

        // Channel bo'yicha filter
        if ($channel) {
            $query->where(function ($q) use ($channel) {
                $q->where('target_channel', $channel)
                    ->orWhereNull('target_channel');
            });
        }

        return $query->limit(5)->get();
    }

    /**
     * Generation prompt yaratish
     */
    protected function buildGenerationPrompt(
        ContentStyleGuide $styleGuide,
        string $topic,
        string $contentType,
        string $purpose,
        ?string $channel,
        $referenceTemplates,
        ?string $additionalPrompt
    ): string {
        $styleContext = $styleGuide->buildPromptContext();

        $channelGuidelines = $this->getChannelGuidelines($channel);

        $purposeGuidelines = $this->getPurposeGuidelines($purpose);

        $examples = '';
        foreach ($referenceTemplates as $template) {
            $examples .= $template->buildContextForGeneration() . "\n\n";
        }

        $prompt = <<<PROMPT
Sen professional marketing kontent yozuvchisisisan. Quyidagi ko'rsatmalarga asoslanib {$contentType} yozib ber.

{$styleContext}

MAVZU: {$topic}

MAQSAD: {$purposeGuidelines}

KANAL: {$channelGuidelines}

NAMUNA POSTLAR (shu uslubda yoz):
{$examples}

QOSHIMCHA KO'RSATMALAR:
{$additionalPrompt}

TALABLAR:
1. Brand style guide ga mos ton va uslubda yoz
2. Post uzunligi: {$styleGuide->min_post_length}-{$styleGuide->max_post_length} belgi
3. Emoji ishlatish: {$styleGuide->emoji_frequency}
4. Oxirida CTA bo'lsin ({$styleGuide->cta_style} uslubda)
5. O'zbekcha yoz

Faqat kontent yoz, boshqa izoh kerak emas.
PROMPT;

        return $prompt;
    }

    /**
     * Variations prompt yaratish
     */
    protected function buildVariationsPrompt(
        ContentStyleGuide $styleGuide,
        string $topic,
        int $count,
        ?string $channel
    ): string {
        $styleContext = $styleGuide->buildPromptContext();
        $channelGuidelines = $this->getChannelGuidelines($channel);

        return <<<PROMPT
Sen professional marketing kontent yozuvchisisisan.

{$styleContext}

MAVZU: {$topic}
KANAL: {$channelGuidelines}

{$count} ta turli xil post varianti yoz. Har bir variant turli hook bilan boshlansin:
1. Savol bilan
2. Statistika bilan
3. Hikoya bilan

Har bir variant uchun JSON formatda javob ber:
[
  {"hook_type": "question", "content": "..."},
  {"hook_type": "statistic", "content": "..."},
  {"hook_type": "story", "content": "..."}
]

Faqat JSON array qaytaring.
PROMPT;
    }

    /**
     * Rewrite prompt yaratish
     */
    protected function buildRewritePrompt(
        ContentStyleGuide $styleGuide,
        string $originalContent,
        string $style,
        ?string $channel
    ): string {
        $styleContext = $styleGuide->buildPromptContext();

        $styleInstruction = match ($style) {
            'improve' => 'Yaxshiroq va professional qilib qayta yoz',
            'shorter' => 'Qisqaroq va aniqroq qilib qayta yoz',
            'longer' => 'Batafsilroq va kengaytirilgan qilib qayta yoz',
            'engaging' => 'Ko\'proq engagement oladigan qilib qayta yoz',
            'formal' => 'Rasmiyroq va professional qilib qayta yoz',
            'casual' => 'Oddiyroq va do\'stona qilib qayta yoz',
            default => 'Yaxshiroq qilib qayta yoz',
        };

        return <<<PROMPT
{$styleContext}

ORIGINAL KONTENT:
{$originalContent}

VAZIFA: {$styleInstruction}

Faqat qayta yozilgan kontentni ber, boshqa izoh kerak emas.
PROMPT;
    }

    /**
     * Claude API ga so'rov yuborish
     */
    protected function callClaudeApi(string $prompt, float $temperature = 0.7, int $maxTokens = 1000): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post($this->apiUrl, [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Claude API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'text' => $data['content'][0]['text'] ?? '',
            'usage' => $data['usage'] ?? [],
        ];
    }

    /**
     * Generation javobini parse qilish
     */
    protected function parseGenerationResponse(array $response): array
    {
        $text = $response['text'];

        // Hashtaglarni ajratish
        preg_match_all('/#[\w\d_]+/u', $text, $hashtagMatches);
        $hashtags = $hashtagMatches[0] ?? [];

        // Hashtaglarni kontentdan olib tashlash (oxiridagi)
        $content = preg_replace('/(\s*#[\w\d_]+)+\s*$/u', '', $text);
        $content = trim($content);

        return [
            'content' => $content,
            'hashtags' => $hashtags,
            'usage' => $response['usage'],
        ];
    }

    /**
     * Variations javobini parse qilish
     */
    protected function parseVariationsResponse(array $response): array
    {
        $text = $response['text'];

        // JSON array topish
        preg_match('/\[[\s\S]*\]/m', $text, $matches);

        $variations = [];

        if (!empty($matches[0])) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                $variations = $decoded;
            }
        }

        // Agar parse qilinmasa - oddiy matn sifatida
        if (empty($variations)) {
            $variations = [
                ['hook_type' => 'general', 'content' => $text]
            ];
        }

        return [
            'variations' => $variations,
            'usage' => $response['usage'],
        ];
    }

    /**
     * Kanal bo'yicha ko'rsatmalar
     */
    protected function getChannelGuidelines(?string $channel): string
    {
        return match ($channel) {
            'instagram' => 'Instagram post uchun: visual ta\'rifli, emoji ishlatilsin, 2200 belgidan oshmasin, 5-30 hashtag',
            'telegram' => 'Telegram post uchun: formatting (bold, italic), link preview, emoji, uzunroq bo\'lishi mumkin',
            'facebook' => 'Facebook post uchun: share qilinadigan, discussion boshlydigan, 1-3 hashtag',
            'tiktok' => 'TikTok caption uchun: juda qisqa, trending, emoji ko\'p, 3-5 hashtag',
            default => 'Umumiy ijtimoiy tarmoq posti uchun',
        };
    }

    /**
     * Maqsad bo'yicha ko'rsatmalar
     */
    protected function getPurposeGuidelines(string $purpose): string
    {
        return match ($purpose) {
            'educate' => 'O\'quvchiga foydali ma\'lumot berish, o\'rgatish, tips ulashish',
            'inspire' => 'Ilhomlantirish, motivatsiya berish, hikoya ulashish',
            'sell' => 'Mahsulot/xizmatni sotish, CTA kuchli bo\'lsin, foyda ta\'kidlansin',
            'engage' => 'Faollik oshirish, savol berish, fikr so\'rash, muhokama boshlash',
            'announce' => 'E\'lon qilish, yangilik ulashish, muhim ma\'lumot berish',
            'entertain' => 'Ko\'ngil ochish, qiziqarli, kulguli, viral bo\'ladigan',
            default => 'Umumiy maqsad - auditoriya bilan bog\'lanish',
        };
    }

    /**
     * Array ni formatlash
     */
    protected function formatArray(array $items): string
    {
        return implode(', ', array_slice($items, 0, 10));
    }
}
