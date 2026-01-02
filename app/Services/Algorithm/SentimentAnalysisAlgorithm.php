<?php

namespace App\Services\Algorithm;

use App\Services\Algorithm\Lexicons\SentimentLexicon;

/**
 * Sentiment Analysis Algorithm - AI-Free Text Sentiment Detection
 *
 * Analyzes text sentiment (positive/negative/neutral) using lexicon-based
 * approach without AI. Based on VADER (Valence Aware Dictionary and
 * sEntiment Reasoner) methodology.
 *
 * Research Sources:
 * - VADER: Hutto & Gilbert (2014) - 96% human agreement
 * - SentiWordNet: Baccianella et al. (2010)
 * - AFINN: Nielsen (2011)
 *
 * Features:
 * - Bilingual support (Uzbek + English)
 * - Intensity modifiers (juda, very)
 * - Negation handling (emas, not)
 * - Emoji/emoticon detection
 * - Context awareness
 *
 * Sentiment Scale:
 * -1.0 (very negative) to +1.0 (very positive)
 * -1.0 to -0.6: Very Negative
 * -0.6 to -0.2: Negative
 * -0.2 to +0.2: Neutral
 * +0.2 to +0.6: Positive
 * +0.6 to +1.0: Very Positive
 *
 * @version 1.0.0
 * @package App\Services\Algorithm
 */
class SentimentAnalysisAlgorithm extends AlgorithmEngine
{
    protected string $cachePrefix = 'sentiment_';
    protected int $cacheTTL = 900; // 15 minutes

    /**
     * Sentiment thresholds
     */
    protected array $thresholds = [
        'very_positive' => 0.6,
        'positive' => 0.2,
        'neutral' => -0.2,
        'negative' => -0.6,
        // Below -0.6 = very negative
    ];

    /**
     * Analyze sentiment of text
     *
     * @param string $text Text to analyze
     * @param array $options Additional options
     * @return array Sentiment analysis results
     */
    public function analyze(string $text, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            if (empty(trim($text))) {
                return $this->getEmptyResult();
            }

            // 1. Tokenize text into words
            $tokens = $this->tokenize($text);

            // 2. Calculate sentiment scores
            $scores = $this->calculateSentimentScores($tokens, $text);

            // 3. Normalize to -1 to +1 scale
            $normalizedScore = $this->normalizeSentiment($scores);

            // 4. Classify sentiment
            $classification = $this->classifySentiment($normalizedScore);

            // 5. Extract sentiment features
            $features = $this->extractFeatures($text, $tokens, $scores);

            // 6. Calculate confidence
            $confidence = $this->calculateConfidence($scores, $features);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'sentiment_score' => round($normalizedScore, 3),
                'classification' => $classification,
                'confidence' => $confidence,
                'scores' => [
                    'positive_score' => round($scores['positive'], 3),
                    'negative_score' => round($scores['negative'], 3),
                    'neutral_score' => round($scores['neutral'], 3),
                    'compound_score' => round($normalizedScore, 3),
                ],
                'features' => $features,
                'emotions' => $this->detectEmotions($text, $normalizedScore, $features),
                'metadata' => [
                    'text_length' => mb_strlen($text),
                    'word_count' => count($tokens),
                    'execution_time_ms' => $executionTime,
                    'version' => '1.0.0',
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sentiment_score' => 0,
            ];
        }
    }

    /**
     * Tokenize text into words
     */
    protected function tokenize(string $text): array
    {
        // Convert to lowercase
        $text = mb_strtolower($text);

        // Split by whitespace and punctuation (keep emoticons)
        $tokens = preg_split('/[\s,\.!;]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return $tokens;
    }

    /**
     * Calculate sentiment scores using lexicon
     */
    protected function calculateSentimentScores(array $tokens, string $originalText): array
    {
        $positiveSum = 0;
        $negativeSum = 0;
        $neutralCount = 0;

        $modifiers = SentimentLexicon::getIntensityModifiers();
        $amplifiers = $modifiers['amplifiers'];
        $dampeners = $modifiers['dampeners'];

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $score = SentimentLexicon::getWordScore($token);

            if ($score == 0) {
                $neutralCount++;
                continue;
            }

            // Check for negation in previous 3 words
            $negated = $this->isNegated($tokens, $i);

            // Check for intensity modifiers in previous 2 words
            $intensity = $this->getIntensity($tokens, $i, $amplifiers, $dampeners);

            // Apply intensity and negation
            $adjustedScore = $score * $intensity;
            if ($negated) {
                $adjustedScore = -$adjustedScore * 0.7; // Flip and reduce
            }

            // Add to appropriate sum
            if ($adjustedScore > 0) {
                $positiveSum += $adjustedScore;
            } else {
                $negativeSum += abs($adjustedScore);
            }
        }

        // Check for emoticons separately
        $emoticonScore = $this->analyzeEmoticons($originalText);
        if ($emoticonScore > 0) {
            $positiveSum += $emoticonScore;
        } else {
            $negativeSum += abs($emoticonScore);
        }

        // Check for ALL CAPS (shouting - increases intensity)
        if ($this->hasAllCaps($originalText)) {
            $positiveSum *= 1.2;
            $negativeSum *= 1.2;
        }

        // Check for exclamation marks (increases intensity)
        $exclamationBoost = min(3, substr_count($originalText, '!')) * 0.1;
        $positiveSum *= (1 + $exclamationBoost);
        $negativeSum *= (1 + $exclamationBoost);

        return [
            'positive' => $positiveSum,
            'negative' => $negativeSum,
            'neutral' => $neutralCount,
        ];
    }

    /**
     * Check if word is negated by previous words
     */
    protected function isNegated(array $tokens, int $index): bool
    {
        // Check 3 words before
        $lookback = min(3, $index);

        for ($i = 1; $i <= $lookback; $i++) {
            $prevToken = $tokens[$index - $i];
            if (SentimentLexicon::isNegation($prevToken)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get intensity from modifiers
     */
    protected function getIntensity(array $tokens, int $index, array $amplifiers, array $dampeners): float
    {
        $intensity = 1.0;
        $lookback = min(2, $index);

        for ($i = 1; $i <= $lookback; $i++) {
            $prevToken = $tokens[$index - $i];

            if (isset($amplifiers[$prevToken])) {
                $intensity *= $amplifiers[$prevToken];
            } elseif (isset($dampeners[$prevToken])) {
                $intensity *= $dampeners[$prevToken];
            }
        }

        return $intensity;
    }

    /**
     * Analyze emoticons in text
     */
    protected function analyzeEmoticons(string $text): float
    {
        $emoticons = SentimentLexicon::getEmoticons();
        $totalScore = 0;

        foreach ($emoticons as $emoticon => $score) {
            $count = substr_count($text, $emoticon);
            $totalScore += $count * $score;
        }

        return $totalScore;
    }

    /**
     * Check if text has ALL CAPS words
     */
    protected function hasAllCaps(string $text): bool
    {
        // Check if there are words with 3+ consecutive uppercase letters
        return preg_match('/[A-Z]{3,}/', $text) === 1;
    }

    /**
     * Normalize sentiment to -1 to +1 scale
     */
    protected function normalizeSentiment(array $scores): float
    {
        $positive = $scores['positive'];
        $negative = $scores['negative'];
        $neutral = $scores['neutral'];

        $total = $positive + $negative;

        if ($total == 0) {
            return 0.0; // Neutral
        }

        // Calculate compound score using VADER's normalization
        $compound = ($positive - $negative) / sqrt($total);

        // Normalize to -1 to +1 using tanh-like function
        $normalized = $compound / (1 + abs($compound));

        return max(-1.0, min(1.0, $normalized));
    }

    /**
     * Classify sentiment into categories
     */
    protected function classifySentiment(float $score): array
    {
        if ($score >= $this->thresholds['very_positive']) {
            return [
                'label' => 'very_positive',
                'label_uz' => 'Juda ijobiy',
                'label_en' => 'Very Positive',
                'emoji' => '😍',
                'color' => 'green',
                'description' => 'Mijoz juda xursand va mamnun',
            ];
        }

        if ($score >= $this->thresholds['positive']) {
            return [
                'label' => 'positive',
                'label_uz' => 'Ijobiy',
                'label_en' => 'Positive',
                'emoji' => '😊',
                'color' => 'light-green',
                'description' => 'Ijobiy fikr, mijoz mamnun',
            ];
        }

        if ($score >= $this->thresholds['neutral']) {
            return [
                'label' => 'neutral',
                'label_uz' => 'Neytral',
                'label_en' => 'Neutral',
                'emoji' => '😐',
                'color' => 'gray',
                'description' => 'Hech qanday aniq his-tuyg\'u yo\'q',
            ];
        }

        if ($score >= $this->thresholds['negative']) {
            return [
                'label' => 'negative',
                'label_uz' => 'Salbiy',
                'label_en' => 'Negative',
                'emoji' => '😞',
                'color' => 'orange',
                'description' => 'Salbiy fikr, mijoz norozi',
            ];
        }

        return [
            'label' => 'very_negative',
            'label_uz' => 'Juda salbiy',
            'label_en' => 'Very Negative',
            'emoji' => '😡',
            'color' => 'red',
            'description' => 'Juda salbiy, mijoz g\'azablangan',
        ];
    }

    /**
     * Extract sentiment features
     */
    protected function extractFeatures(string $text, array $tokens, array $scores): array
    {
        $features = [];

        // Positive word count
        $positiveWords = [];
        $negativeWords = [];

        foreach ($tokens as $token) {
            if (SentimentLexicon::isPositive($token)) {
                $positiveWords[] = $token;
            } elseif (SentimentLexicon::isNegative($token)) {
                $negativeWords[] = $token;
            }
        }

        $features['positive_word_count'] = count($positiveWords);
        $features['negative_word_count'] = count($negativeWords);
        $features['positive_words'] = array_unique($positiveWords);
        $features['negative_words'] = array_unique($negativeWords);

        // Emoticon count
        $emoticonCount = 0;
        foreach (SentimentLexicon::getEmoticons() as $emoticon => $score) {
            $emoticonCount += substr_count($text, $emoticon);
        }
        $features['emoticon_count'] = $emoticonCount;

        // Exclamation marks
        $features['exclamation_count'] = substr_count($text, '!');

        // Question marks
        $features['question_count'] = substr_count($text, '?');

        // Negation words
        $negationCount = 0;
        foreach ($tokens as $token) {
            if (SentimentLexicon::isNegation($token)) {
                $negationCount++;
            }
        }
        $features['negation_count'] = $negationCount;

        // ALL CAPS
        $features['has_caps'] = $this->hasAllCaps($text);

        return $features;
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence(array $scores, array $features): array
    {
        // Base confidence on:
        // 1. Score magnitude (stronger = more confident)
        // 2. Number of sentiment words found
        // 3. Consistency (not many negations)

        $total = $scores['positive'] + $scores['negative'];
        $sentimentWordCount = $features['positive_word_count'] + $features['negative_word_count'];

        // Magnitude confidence (0-50 points)
        $magnitudeConf = min(50, $total * 10);

        // Word count confidence (0-30 points)
        $wordCountConf = min(30, $sentimentWordCount * 6);

        // Consistency confidence (0-20 points)
        $consistencyConf = 20 - ($features['negation_count'] * 5);
        $consistencyConf = max(0, $consistencyConf);

        $totalConfidence = $magnitudeConf + $wordCountConf + $consistencyConf;

        $level = 'low';
        if ($totalConfidence >= 70) {
            $level = 'high';
        } elseif ($totalConfidence >= 40) {
            $level = 'medium';
        }

        return [
            'score' => round($totalConfidence, 1),
            'level' => $level,
            'factors' => [
                'magnitude' => round($magnitudeConf, 1),
                'word_count' => round($wordCountConf, 1),
                'consistency' => round($consistencyConf, 1),
            ],
        ];
    }

    /**
     * Detect specific emotions
     */
    protected function detectEmotions(string $text, float $sentimentScore, array $features): array
    {
        $emotions = [];

        $textLower = mb_strtolower($text);

        // Joy/Happiness
        if ($sentimentScore > 0.5) {
            $joyWords = ['xursand', 'baxtli', 'quvnoq', 'happy', 'joy', 'delighted'];
            foreach ($joyWords as $word) {
                if (mb_strpos($textLower, $word) !== false) {
                    $emotions['joy'] = 0.8;
                    break;
                }
            }
        }

        // Love
        $loveWords = ['sevaman', 'yoqtiraman', 'love', 'adore'];
        foreach ($loveWords as $word) {
            if (mb_strpos($textLower, $word) !== false) {
                $emotions['love'] = 0.9;
                break;
            }
        }

        // Anger
        if ($sentimentScore < -0.5) {
            $angerWords = ['g\'azab', 'jahil', 'angry', 'furious', 'mad'];
            foreach ($angerWords as $word) {
                if (mb_strpos($textLower, $word) !== false) {
                    $emotions['anger'] = 0.8;
                    break;
                }
            }
        }

        // Sadness
        $sadWords = ['qayg\'u', 'g\'amgin', 'sad', 'depressed', 'unhappy'];
        foreach ($sadWords as $word) {
            if (mb_strpos($textLower, $word) !== false) {
                $emotions['sadness'] = 0.7;
                break;
            }
        }

        // Surprise
        $surpriseWords = ['hayratda', 'surprise', 'shocked', 'wow'];
        foreach ($surpriseWords as $word) {
            if (mb_strpos($textLower, $word) !== false) {
                $emotions['surprise'] = 0.6;
                break;
            }
        }

        // Fear
        $fearWords = ['qo\'rqaman', 'xavotir', 'scared', 'afraid', 'fear'];
        foreach ($fearWords as $word) {
            if (mb_strpos($textLower, $word) !== false) {
                $emotions['fear'] = 0.7;
                break;
            }
        }

        // Disgust
        if ($sentimentScore < -0.6) {
            $disgustWords = ['jirkanch', 'yomon', 'disgusting', 'terrible', 'awful'];
            foreach ($disgustWords as $word) {
                if (mb_strpos($textLower, $word) !== false) {
                    $emotions['disgust'] = 0.75;
                    break;
                }
            }
        }

        return $emotions;
    }

    /**
     * Get empty result for empty text
     */
    protected function getEmptyResult(): array
    {
        return [
            'success' => true,
            'sentiment_score' => 0,
            'classification' => [
                'label' => 'neutral',
                'label_uz' => 'Neytral',
                'emoji' => '😐',
                'color' => 'gray',
            ],
            'confidence' => ['score' => 0, 'level' => 'low'],
            'scores' => [
                'positive_score' => 0,
                'negative_score' => 0,
                'neutral_score' => 0,
                'compound_score' => 0,
            ],
        ];
    }

    /**
     * Batch analyze multiple texts
     */
    public function batchAnalyze(array $texts): array
    {
        $results = [];

        foreach ($texts as $id => $text) {
            $results[$id] = $this->analyze($text);
        }

        // Calculate aggregate statistics
        $scores = array_column($results, 'sentiment_score');
        $avgSentiment = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        $positive = count(array_filter($scores, fn($s) => $s > 0.2));
        $negative = count(array_filter($scores, fn($s) => $s < -0.2));
        $neutral = count($scores) - $positive - $negative;

        return [
            'results' => $results,
            'aggregate' => [
                'average_sentiment' => round($avgSentiment, 3),
                'total_analyzed' => count($texts),
                'positive_count' => $positive,
                'negative_count' => $negative,
                'neutral_count' => $neutral,
                'positive_percentage' => round(($positive / count($texts)) * 100, 1),
                'negative_percentage' => round(($negative / count($texts)) * 100, 1),
                'overall_mood' => $this->getOverallMood($avgSentiment),
            ],
        ];
    }

    /**
     * Get overall mood classification
     */
    protected function getOverallMood(float $avgScore): string
    {
        if ($avgScore > 0.3) return 'Mostly Positive';
        if ($avgScore > 0) return 'Slightly Positive';
        if ($avgScore > -0.3) return 'Neutral/Mixed';
        return 'Negative';
    }
}
