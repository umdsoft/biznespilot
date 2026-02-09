<?php

namespace App\Services\ContentAI;

use App\Models\Business;
use App\Models\CustdevSurvey;
use App\Models\PainPointContentMap;
use Illuminate\Support\Facades\Log;

/**
 * Survey → Content Bridge
 *
 * CustdevSurvey javoblaridan og'riq nuqtalarni ajratib,
 * har biri uchun individual kontent mavzulari va hooklarni taklif qiladi.
 *
 * Bu 100% ichki algoritm — AI API chaqirilMAYDI.
 */
class SurveyContentBridge
{
    /**
     * Og'riq nuqtalari asosida kontent tavsiyalarini olish
     *
     * @return array<int, array{category: string, pain_text: string, topics: array, hooks: array, content_types: array, relevance: float}>
     */
    public function getContentRecommendationsFromPainPoints(string $businessId, int $limit = 10): array
    {
        try {
            $maps = PainPointContentMap::where('business_id', $businessId)
                ->active()
                ->topRelevant($limit)
                ->get();

            if ($maps->isEmpty()) {
                // Agar mapping bo'lmasa, avval yaratish kerak
                $this->buildPainPointMaps($businessId);
                $maps = PainPointContentMap::where('business_id', $businessId)
                    ->active()
                    ->topRelevant($limit)
                    ->get();
            }

            return $maps->map(fn (PainPointContentMap $map) => [
                'id' => $map->id,
                'category' => $map->pain_point_category,
                'category_label' => PainPointContentMap::CATEGORIES[$map->pain_point_category] ?? $map->pain_point_category,
                'pain_text' => $map->pain_point_text,
                'keywords' => $map->extracted_keywords ?? [],
                'topics' => $map->suggested_topics ?? [],
                'hooks' => $map->suggested_hooks ?? [],
                'content_types' => $map->suggested_content_types ?? [],
                'relevance' => (float) $map->relevance_score,
                'times_used' => $map->times_used,
                'avg_engagement' => (float) $map->avg_engagement_rate,
            ])->toArray();
        } catch (\Throwable $e) {
            Log::error('SurveyContentBridge: getRecommendations failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Biznes uchun pain point → content mapping yaratish.
     * CustdevSurvey javoblarini tahlil qilib, PainPointContentMap jadvaliga yozadi.
     */
    public function buildPainPointMaps(string $businessId): int
    {
        $created = 0;

        try {
            $business = Business::withoutGlobalScopes()->find($businessId);
            if (! $business) {
                return 0;
            }

            // Biznesning so'rovnoma javoblarini olish
            $surveys = CustdevSurvey::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->with(['responses' => function ($q) {
                    $q->where('status', 'completed')->with('answers');
                }])
                ->get();

            if ($surveys->isEmpty()) {
                return 0;
            }

            // Avvalgi mapping larni tozalash
            PainPointContentMap::where('business_id', $businessId)->delete();

            // Har bir javobdan og'riqlarni ajratish
            $painPointsByCategory = $this->extractPainPoints($surveys);

            foreach ($painPointsByCategory as $category => $painTexts) {
                foreach ($painTexts as $painText) {
                    if (mb_strlen($painText) < 5) {
                        continue;
                    }

                    $keywords = $this->extractKeywords($painText);
                    $topics = $this->generateTopicsFromPainPoint($category, $painText, $keywords, $business);
                    $hooks = $this->generateHooksFromPainPoint($category, $painText, $keywords);
                    $contentTypes = $this->suggestContentTypes($category);
                    $relevance = $this->calculateRelevance($painText, $painTexts);

                    PainPointContentMap::create([
                        'business_id' => $businessId,
                        'pain_point_category' => $category,
                        'pain_point_text' => $painText,
                        'extracted_keywords' => $keywords,
                        'suggested_topics' => $topics,
                        'suggested_hooks' => $hooks,
                        'suggested_content_types' => $contentTypes,
                        'relevance_score' => $relevance,
                    ]);

                    $created++;
                }
            }
        } catch (\Throwable $e) {
            Log::error('SurveyContentBridge: buildPainPointMaps failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
        }

        return $created;
    }

    /**
     * So'rovnoma javoblaridan og'riq nuqtalarni ajratish
     */
    private function extractPainPoints($surveys): array
    {
        $painPoints = [];

        // Kategoriya → savol turi xaritasi
        $categoryMap = [
            'frustrations' => 'frustrations',
            'fears' => 'fears',
            'dreams' => 'dreams',
            'daily_routine' => 'daily_routine',
            'happiness_triggers' => 'happiness_triggers',
        ];

        foreach ($surveys as $survey) {
            foreach ($survey->responses as $response) {
                foreach ($response->answers ?? [] as $answer) {
                    $question = $answer->question ?? null;
                    if (! $question) {
                        continue;
                    }

                    $questionCategory = $question->category ?? null;
                    if (! $questionCategory || ! isset($categoryMap[$questionCategory])) {
                        continue;
                    }

                    $category = $categoryMap[$questionCategory];
                    $answerText = is_array($answer->answer) ? implode(', ', $answer->answer) : ($answer->answer ?? '');

                    if (mb_strlen($answerText) < 5) {
                        continue;
                    }

                    // Javobni jumlalarga bo'lish
                    $sentences = preg_split('/[.!?\n]+/', $answerText, -1, PREG_SPLIT_NO_EMPTY);

                    foreach ($sentences as $sentence) {
                        $sentence = trim($sentence);
                        if (mb_strlen($sentence) >= 5) {
                            $painPoints[$category][] = $sentence;
                        }
                    }
                }
            }
        }

        // Dublikatlarni olib tashlash
        foreach ($painPoints as $category => $texts) {
            $painPoints[$category] = array_values(array_unique($texts));
        }

        return $painPoints;
    }

    /**
     * Matndan kalit so'zlarni ajratish (oddiy NLP)
     */
    private function extractKeywords(string $text): array
    {
        // Stop words (O'zbek tilida)
        $stopWords = ['va', 'yoki', 'bilan', 'uchun', 'dan', 'ga', 'da', 'bu',
            'men', 'siz', 'u', 'biz', 'ular', 'har', 'bir', 'ham', 'lekin',
            'chunki', 'agar', 'kerak', 'bo\'lsa', 'bo\'ladi', 'emas', 'yo\'q',
            'bor', 'juda', 'ko\'p', 'kam', 'katta', 'kichik', 'yangi', 'eski',
            'и', 'или', 'но', 'а', 'в', 'на', 'за', 'от', 'не', 'что', 'это'];

        $words = preg_split('/[\s,;:!?\-()]+/u', mb_strtolower($text));
        $keywords = [];

        foreach ($words as $word) {
            $word = trim($word, '."\'');
            if (mb_strlen($word) >= 3 && ! in_array($word, $stopWords, true)) {
                $keywords[] = $word;
            }
        }

        // Eng ko'p takrorlanganlarni oldingi qatorga
        $counted = array_count_values($keywords);
        arsort($counted);

        return array_slice(array_keys($counted), 0, 10);
    }

    /**
     * Og'riq nuqtasi asosida kontent mavzularini generatsiya qilish
     */
    private function generateTopicsFromPainPoint(string $category, string $painText, array $keywords, Business $business): array
    {
        $businessName = $business->name ?? 'Biznes';
        $topics = [];

        switch ($category) {
            case 'frustrations':
                $topics[] = "Mijozlarning eng keng tarqalgan muammosi va uni qanday hal qilish mumkin";
                if (! empty($keywords[0])) {
                    $topics[] = "'{$keywords[0]}' muammosining 5 ta oson yechimi";
                    $topics[] = "Nima uchun ko'pchilik '{$keywords[0]}' da xato qiladi?";
                }
                $topics[] = 'Biz bu muammoni qanday hal qilamiz — real tajriba';
                break;

            case 'fears':
                $topics[] = 'Xarid qilishdan oldin eng ko\'p beriladigan 5 ta savol';
                if (! empty($keywords[0])) {
                    $topics[] = "'{$keywords[0]}' haqidagi 3 ta noto'g'ri tushuncha";
                }
                $topics[] = "Mijoz fikri: \"Avval qo'rqardim, endi esa...\"";
                $topics[] = "Ishonch kafolati — biz nimani va'da qilamiz";
                break;

            case 'dreams':
                $topics[] = "Maqsadingizga erishish uchun birinchi qadam";
                if (! empty($keywords[0])) {
                    $topics[] = "'{$keywords[0]}' ga qanday erishish mumkin — amaliy qo'llanma";
                }
                $topics[] = "Mijozlarimiz qanday natijaga erishdi — raqamlar bilan";
                $keyword = $keywords[0] ?? 'natijaga';
                $topics[] = "30 kunda '{$keyword}' erishish rejasi";
                break;

            case 'daily_routine':
                $topics[] = 'Kundalik hayotingizni osonlashtiradigan maslahatlar';
                $topics[] = "Vaqtni tejash bo'yicha professional maslahat";
                if (! empty($keywords[0])) {
                    $topics[] = "'{$keywords[0]}' uchun eng yaxshi vaqtni qanday tanlash";
                }
                break;

            case 'happiness_triggers':
                $topics[] = "Mijozlarimiz bizni nima uchun tanlaydi";
                $topics[] = 'Sizning orzuingiz — bizning maqsadimiz';
                if (! empty($keywords[0])) {
                    $topics[] = "'{$keywords[0]}' ga erishish yo'lida biz sizga qanday yordam beramiz";
                }
                break;
        }

        return array_slice($topics, 0, 5);
    }

    /**
     * Hook (e'tibor tortuvchi birinchi jumla) generatsiya qilish
     */
    private function generateHooksFromPainPoint(string $category, string $painText, array $keywords): array
    {
        $hooks = [];
        $keyword = $keywords[0] ?? 'bu';

        switch ($category) {
            case 'frustrations':
                $hooks[] = "Siz ham '{$keyword}' bilan qiynalasizmi? Biz yechim topdik!";
                $hooks[] = "'{$keyword}' — eng katta muammo, lekin yechimi oddiy";
                $hooks[] = "90% odam '{$keyword}' da xato qiladi. Siz-chi?";
                break;

            case 'fears':
                $hooks[] = "'{$keyword}' dan qo'rqasizmi? Bu mifni buzamiz!";
                $hooks[] = "Haqiqat: '{$keyword}' haqida bilishingiz kerak bo'lgan 3 narsa";
                $hooks[] = "Ishonmaysiz, lekin '{$keyword}' aslida...";
                break;

            case 'dreams':
                $hooks[] = "Tasavvur qiling: '{$keyword}' sizning yangi haqiqatingiz";
                $hooks[] = "'{$keyword}' — bu orzu emas, bu reja!";
                $hooks[] = "1 oy ichida '{$keyword}' — bu mumkin!";
                break;

            default:
                $hooks[] = "Bu sizga ham tegishlimi? O'qing!";
                $hooks[] = "Bilsangiz hayron bo'lasiz!";
                break;
        }

        return array_slice($hooks, 0, 3);
    }

    /**
     * Kategoriyaga qarab kontent turini tavsiya qilish
     */
    private function suggestContentTypes(string $category): array
    {
        return match ($category) {
            'frustrations' => ['reel', 'carousel', 'post'],
            'fears' => ['carousel', 'post', 'story'],
            'dreams' => ['reel', 'post', 'story'],
            'daily_routine' => ['story', 'reel', 'post'],
            'happiness_triggers' => ['post', 'carousel', 'story'],
            default => ['post', 'reel'],
        };
    }

    /**
     * Relevance (ahamiyat) ballini hisoblash
     * Nechta respondent shu muammoni eslatganiga qarab
     */
    private function calculateRelevance(string $painText, array $allTexts): float
    {
        // Shu og'riq qancha marta takrorlangan (o'xshash matnlar)
        $keywords = $this->extractKeywords($painText);
        $matchCount = 0;

        foreach ($allTexts as $text) {
            $textLower = mb_strtolower($text);
            $matches = 0;
            foreach ($keywords as $kw) {
                if (str_contains($textLower, $kw)) {
                    $matches++;
                }
            }
            if ($matches >= 2) {
                $matchCount++;
            }
        }

        $totalTexts = max(count($allTexts), 1);
        $frequency = $matchCount / $totalTexts;

        // Score: 0-100 (frequency * 60 + text_length_factor * 40)
        $lengthFactor = min(mb_strlen($painText) / 100, 1.0);
        $score = ($frequency * 60) + ($lengthFactor * 40);

        return round(min($score, 100), 2);
    }
}
