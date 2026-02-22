<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Video Extractor Configuration
    |--------------------------------------------------------------------------
    */
    'extractor' => [
        'binary' => env('YTDLP_BINARY', 'yt-dlp'),
        'ffmpeg_location' => env('FFMPEG_LOCATION'),
        'timeout' => 120, // seconds
        'max_duration' => 1800, // 30 minutes max
        'audio_format' => 'mp3',
        'audio_quality' => '64k',
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Analysis (Claude) Configuration
    |--------------------------------------------------------------------------
    */
    'analysis' => [
        'model' => env('VIDEO_ANALYSIS_MODEL', 'claude-haiku-4-5-20251001'),
        'max_tokens' => 2000,
        'temperature' => 0.3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('VIDEO_CONTENT_QUEUE_CONNECTION', 'redis'),
        'queue' => env('VIDEO_CONTENT_QUEUE_NAME', 'video-content'),
        'timeout' => 600, // 10 minutes max per job
        'tries' => 2,
        'backoff' => [60, 120],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'temp_path' => 'video-content/temp',
        'cleanup_after_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Platforms
    |--------------------------------------------------------------------------
    */
    'platforms' => [
        'youtube' => [
            'enabled' => true,
            'url_patterns' => [
                '/youtube\.com\/watch/',
                '/youtu\.be\//',
                '/youtube\.com\/shorts\//',
            ],
        ],
        'instagram' => [
            'enabled' => true,
            'url_patterns' => [
                '/instagram\.com\/reel\//',
                '/instagram\.com\/p\//',
            ],
        ],
        'tiktok' => [
            'enabled' => true,
            'url_patterns' => [
                '/tiktok\.com\/@.*\/video\//',
                '/tiktok\.com\/t\//',
            ],
        ],
    ],
];
