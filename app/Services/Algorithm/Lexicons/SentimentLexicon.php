<?php

namespace App\Services\Algorithm\Lexicons;

/**
 * Sentiment Lexicon - Uzbek & English Words
 *
 * Research-based sentiment dictionary for both languages.
 * Based on VADER sentiment analysis methodology.
 *
 * @version 1.0.0
 */
class SentimentLexicon
{
    /**
     * Positive words with intensity scores
     * Score range: 1.0 (mild positive) to 3.0 (very positive)
     */
    public static function getPositiveWords(): array
    {
        return [
            // Uzbek positive words
            'ajoyib' => 3.0,
            'zo\'r' => 2.5,
            'yaxshi' => 2.0,
            'mukammal' => 3.0,
            'a\'lo' => 2.8,
            'go\'zal' => 2.5,
            'chiroyli' => 2.3,
            'yoqimli' => 2.0,
            'qiziq' => 1.8,
            'foydali' => 2.2,
            'sifatli' => 2.4,
            'arzon' => 1.5,
            'tez' => 1.5,
            'oson' => 1.8,
            'qulay' => 2.0,
            'ishonchli' => 2.5,
            'professional' => 2.3,
            'sog\'lom' => 2.0,
            'mazali' => 2.2,
            'yangi' => 1.8,
            'eng yaxshi' => 3.0,
            'eng zo\'r' => 3.0,
            'juda yaxshi' => 2.8,
            'barakalla' => 2.5,
            'rahmat' => 2.0,
            'minnatdor' => 2.3,
            'mamnun' => 2.5,
            'xursand' => 2.7,
            'sevimli' => 2.4,
            'top' => 2.5,
            'super' => 2.8,
            'bomba' => 2.6,
            'ideal' => 2.7,
            'eng sara' => 2.9,

            // English positive words
            'amazing' => 3.0,
            'awesome' => 2.8,
            'excellent' => 2.8,
            'perfect' => 2.9,
            'great' => 2.5,
            'good' => 2.0,
            'fantastic' => 2.9,
            'wonderful' => 2.7,
            'beautiful' => 2.5,
            'nice' => 1.8,
            'love' => 2.8,
            'best' => 2.9,
            'happy' => 2.4,
            'thank' => 2.0,
            'thanks' => 2.0,
            'wow' => 2.3,
            'cool' => 2.0,
            'delicious' => 2.5,
            'tasty' => 2.3,
            'fresh' => 1.9,
            'quality' => 2.2,
            'recommend' => 2.4,
            'incredible' => 2.9,
            'outstanding' => 2.8,
            'brilliant' => 2.7,
            'superb' => 2.7,
            'divine' => 2.6,
            'fab' => 2.4,
            'fabulous' => 2.6,
            'magnificent' => 2.8,
        ];
    }

    /**
     * Negative words with intensity scores
     * Score range: -1.0 (mild negative) to -3.0 (very negative)
     */
    public static function getNegativeWords(): array
    {
        return [
            // Uzbek negative words
            'yomon' => -2.5,
            'juda yomon' => -3.0,
            'qo\'rqinchli' => -2.8,
            'dahshatli' => -2.9,
            'jirkanch' => -2.7,
            'iflos' => -2.3,
            'noqulay' => -1.8,
            'qimmat' => -1.5,
            'sekin' => -1.3,
            'qiyin' => -1.7,
            'murakkab' => -1.6,
            'ishonchsiz' => -2.4,
            'soxta' => -2.8,
            'yolg\'on' => -2.9,
            'aldash' => -2.9,
            'o\'g\'ri' => -3.0,
            'firibgar' => -2.9,
            'buzilgan' => -2.2,
            'eskirgan' => -1.8,
            'zaif' => -1.9,
            'past' => -2.0,
            'sifatsiz' => -2.5,
            'hafsala pir' => -2.3,
            'xafa' => -2.1,
            'g\'azablangan' => -2.4,
            'norozi' => -2.2,
            'achishli' => -1.9,
            'befarq' => -1.7,
            'unchalik' => -1.2,
            'afsuski' => -1.8,

            // English negative words
            'bad' => -2.5,
            'terrible' => -2.9,
            'horrible' => -2.8,
            'awful' => -2.7,
            'worst' => -3.0,
            'disgusting' => -2.8,
            'hate' => -2.9,
            'poor' => -2.0,
            'disappointing' => -2.3,
            'disappointed' => -2.4,
            'slow' => -1.5,
            'expensive' => -1.4,
            'overpriced' => -2.0,
            'waste' => -2.5,
            'scam' => -3.0,
            'fraud' => -3.0,
            'fake' => -2.7,
            'lie' => -2.8,
            'liar' => -2.9,
            'cheat' => -2.8,
            'rip-off' => -2.7,
            'rubbish' => -2.4,
            'trash' => -2.5,
            'garbage' => -2.5,
            'useless' => -2.6,
            'worthless' => -2.7,
            'pathetic' => -2.6,
            'ridiculous' => -2.3,
            'annoying' => -2.1,
            'frustrating' => -2.2,
            'broken' => -2.0,
            'damaged' => -2.1,
        ];
    }

    /**
     * Intensity modifiers (amplifiers and dampeners)
     */
    public static function getIntensityModifiers(): array
    {
        return [
            // Amplifiers (increase intensity)
            'amplifiers' => [
                'juda' => 1.5,
                'eng' => 1.4,
                'ancha' => 1.3,
                'ko\'p' => 1.2,
                'nihoyatda' => 1.6,
                'very' => 1.5,
                'extremely' => 1.6,
                'super' => 1.4,
                'really' => 1.3,
                'so' => 1.3,
                'too' => 1.3,
                'absolutely' => 1.5,
                'completely' => 1.4,
                'totally' => 1.4,
                'utterly' => 1.5,
            ],

            // Dampeners (decrease intensity)
            'dampeners' => [
                'biroz' => 0.7,
                'ozgina' => 0.6,
                'unchalik' => 0.5,
                'kam' => 0.6,
                'slightly' => 0.7,
                'somewhat' => 0.6,
                'barely' => 0.5,
                'hardly' => 0.5,
                'scarcely' => 0.5,
                'kinda' => 0.7,
                'sort of' => 0.6,
                'kind of' => 0.6,
            ],
        ];
    }

    /**
     * Negation words (flip polarity)
     */
    public static function getNegationWords(): array
    {
        return [
            'emas',
            'yo\'q',
            'not',
            'no',
            'never',
            'neither',
            'nobody',
            'nothing',
            'nowhere',
            'hardly',
            'barely',
            'none',
            'without',
        ];
    }

    /**
     * Emoticons and emojis with sentiment scores
     */
    public static function getEmoticons(): array
    {
        return [
            // Positive emoticons
            ':)' => 1.5,
            ':D' => 2.0,
            '=)' => 1.5,
            ':-)' => 1.5,
            '😊' => 2.0,
            '😃' => 2.2,
            '😄' => 2.3,
            '😁' => 2.1,
            '🙂' => 1.8,
            '😍' => 2.8,
            '🥰' => 2.7,
            '😘' => 2.5,
            '❤️' => 2.8,
            '💕' => 2.6,
            '💖' => 2.7,
            '👍' => 2.0,
            '👏' => 2.2,
            '🙌' => 2.3,
            '✨' => 1.9,
            '⭐' => 2.0,
            '🌟' => 2.1,
            '🔥' => 2.4,
            '💯' => 2.5,

            // Negative emoticons
            ':(' => -1.8,
            ':-(' => -1.8,
            ':/' => -1.3,
            '😞' => -2.0,
            '😔' => -2.1,
            '😢' => -2.3,
            '😭' => -2.5,
            '😠' => -2.4,
            '😡' => -2.7,
            '🤬' => -2.9,
            '😤' => -2.2,
            '💔' => -2.6,
            '👎' => -2.0,
            '😒' => -1.9,
            '🙄' => -1.7,
            '😑' => -1.5,
        ];
    }

    /**
     * Question words (increase engagement, slight positive)
     */
    public static function getQuestionWords(): array
    {
        return [
            'nima' => 0.3,
            'qanday' => 0.3,
            'qachon' => 0.2,
            'kim' => 0.2,
            'qayerda' => 0.2,
            'nega' => 0.1,
            'what' => 0.3,
            'how' => 0.3,
            'when' => 0.2,
            'where' => 0.2,
            'who' => 0.2,
            'why' => 0.1,
        ];
    }

    /**
     * Get all positive words (keys only)
     */
    public static function getPositiveWordsList(): array
    {
        return array_keys(self::getPositiveWords());
    }

    /**
     * Get all negative words (keys only)
     */
    public static function getNegativeWordsList(): array
    {
        return array_keys(self::getNegativeWords());
    }

    /**
     * Get word score
     */
    public static function getWordScore(string $word): float
    {
        $word = mb_strtolower(trim($word));

        $positive = self::getPositiveWords();
        if (isset($positive[$word])) {
            return $positive[$word];
        }

        $negative = self::getNegativeWords();
        if (isset($negative[$word])) {
            return $negative[$word];
        }

        $emoticons = self::getEmoticons();
        if (isset($emoticons[$word])) {
            return $emoticons[$word];
        }

        $questions = self::getQuestionWords();
        if (isset($questions[$word])) {
            return $questions[$word];
        }

        return 0.0;
    }

    /**
     * Check if word is positive
     */
    public static function isPositive(string $word): bool
    {
        $word = mb_strtolower(trim($word));
        return isset(self::getPositiveWords()[$word]);
    }

    /**
     * Check if word is negative
     */
    public static function isNegative(string $word): bool
    {
        $word = mb_strtolower(trim($word));
        return isset(self::getNegativeWords()[$word]);
    }

    /**
     * Check if word is negation
     */
    public static function isNegation(string $word): bool
    {
        $word = mb_strtolower(trim($word));
        return in_array($word, self::getNegationWords());
    }
}
