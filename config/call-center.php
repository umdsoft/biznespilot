<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Speech-to-Text Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the speech-to-text service used to transcribe calls.
    | Currently supports Groq Whisper.
    |
    */
    'stt' => [
        'provider' => env('CALL_CENTER_STT_PROVIDER', 'groq'),

        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'api_url' => 'https://api.groq.com/openai/v1/audio/transcriptions',
            'model' => env('GROQ_WHISPER_MODEL', 'whisper-large-v3-turbo'),
            'language' => env('GROQ_WHISPER_LANGUAGE', 'uz'),
            'response_format' => 'json',
            'timeout' => 120, // seconds
        ],

        // Groq Whisper narxi: $0.04 per hour
        'pricing' => [
            'per_hour' => 0.04, // USD
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI analysis service. Uses Claude Haiku for
    | analyzing call transcripts and providing scores.
    |
    */
    'analysis' => [
        'model' => env('CALL_CENTER_ANALYSIS_MODEL', 'claude-3-5-haiku-20241022'),
        'max_tokens' => 2000,
        'temperature' => 0.3,

        // Claude Haiku pricing (per million tokens)
        'pricing' => [
            'input_per_million' => 0.25,   // $0.25 per 1M input tokens
            'output_per_million' => 1.25,  // $1.25 per 1M output tokens
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for storing temporary audio files during processing.
    | Files are stored temporarily and deleted after analysis.
    |
    */
    'storage' => [
        'disk' => env('CALL_CENTER_STORAGE_DISK', 'local'),
        'temp_path' => 'call-center/temp',
        'cleanup_after_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for audio file processing including duration limits
    | and compression settings.
    |
    */
    'audio' => [
        'max_duration' => 3600,      // Maximum 1 hour
        'min_duration' => 30,        // Minimum 30 seconds

        // FFmpeg compression settings
        'compress' => [
            'enabled' => env('CALL_CENTER_COMPRESS_AUDIO', false),
            'format' => 'mp3',
            'bitrate' => '64k',
            'sample_rate' => 16000,
            'channels' => 1, // mono
        ],

        // Supported audio formats
        'supported_formats' => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'webm'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the queue jobs that process call analysis.
    |
    */
    'queue' => [
        'connection' => env('CALL_CENTER_QUEUE_CONNECTION', 'redis'),
        'queue' => env('CALL_CENTER_QUEUE_NAME', 'call-center'),
        'timeout' => 300,  // 5 minutes max per job
        'tries' => 3,
        'backoff' => [30, 60, 120], // Retry delays in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for call scoring including stage weights and thresholds.
    |
    */
    'scoring' => [
        // Stage weights (must sum to 1.0)
        'weights' => [
            'greeting' => 0.10,           // Salomlashish
            'discovery' => 0.20,          // Ehtiyoj aniqlash
            'presentation' => 0.20,       // Taqdimot
            'objection_handling' => 0.15, // E'tirozlar
            'closing' => 0.15,            // Yopish
            'rapport' => 0.10,            // Munosabat
            'cta' => 0.10,                // Keyingi qadam
        ],

        // Score thresholds
        'thresholds' => [
            'excellent' => 80, // 80+ = A'lo
            'good' => 60,      // 60-79 = Yaxshi
            'average' => 40,   // 40-59 = O'rtacha
            // Below 40 = Yomon
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Anti-patterns Configuration
    |--------------------------------------------------------------------------
    |
    | Common sales call anti-patterns and their penalties.
    |
    */
    'anti_patterns' => [
        'no_discovery' => [
            'severity' => 'critical',
            'penalty' => 15,
            'label_uz' => 'Savolsiz sotish',
            'description_uz' => 'Mijoz ehtiyojlari aniqlanmadi',
        ],
        'price_early' => [
            'severity' => 'high',
            'penalty' => 10,
            'label_uz' => 'Narx erta aytildi',
            'description_uz' => 'Qiymat ko\'rsatilmasdan narx aytildi',
        ],
        'weak_closing' => [
            'severity' => 'high',
            'penalty' => 10,
            'label_uz' => 'Zaif yopish',
            'description_uz' => 'Aniq yopish so\'ralmadi',
        ],
        'no_objection_handle' => [
            'severity' => 'high',
            'penalty' => 10,
            'label_uz' => 'E\'tiroz javobsiz',
            'description_uz' => 'Mijoz e\'tiroziga javob berilmadi',
        ],
        'interruption' => [
            'severity' => 'medium',
            'penalty' => 5,
            'label_uz' => 'Gapni bo\'lish',
            'description_uz' => 'Mijoz gapi bo\'lindi',
        ],
        'monologue' => [
            'severity' => 'medium',
            'penalty' => 5,
            'label_uz' => 'Uzun monolog',
            'description_uz' => '60+ sekund to\'xtovsiz gapirish',
        ],
        'no_followup' => [
            'severity' => 'medium',
            'penalty' => 5,
            'label_uz' => 'Keyingi qadam yo\'q',
            'description_uz' => 'Aniq keyingi qadam belgilanmadi',
        ],
        'negative_language' => [
            'severity' => 'medium',
            'penalty' => 5,
            'label_uz' => 'Salbiy til',
            'description_uz' => 'Salbiy so\'zlar ishlatildi',
        ],
        'rushing' => [
            'severity' => 'medium',
            'penalty' => 5,
            'label_uz' => 'Shoshilish',
            'description_uz' => 'Mijozga vaqt berilmadi',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Exchange rate for displaying costs in local currency.
    |
    */
    'currency' => [
        'usd_to_uzs' => env('USD_TO_UZS_RATE', 12800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting for analysis requests to prevent abuse.
    |
    */
    'rate_limits' => [
        'per_business_per_hour' => 50,
        'bulk_max_calls' => 10, // Maximum calls in a single bulk request
    ],
];
