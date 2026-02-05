<?php

/**
 * Algorithm Configuration
 *
 * Configuration for the diagnostic algorithm system.
 * Handles caching, rate limiting, and performance settings.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Cache driver (null = default from config/cache.php)
        'driver' => env('ALGORITHM_CACHE_DRIVER', null),

        // Cache TTL in seconds
        'ttl' => [
            'diagnostic' => env('ALGORITHM_CACHE_DIAGNOSTIC_TTL', 1800),  // 30 min
            'algorithm' => env('ALGORITHM_CACHE_ALGORITHM_TTL', 1800),   // 30 min
            'metrics' => env('ALGORITHM_CACHE_METRICS_TTL', 3600),       // 1 hour
            'benchmark' => env('ALGORITHM_CACHE_BENCHMARK_TTL', 86400),  // 24 hours
        ],

        // Enable request-level caching
        'request_cache' => env('ALGORITHM_REQUEST_CACHE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        // Max parallel algorithm executions
        'max_parallel' => env('ALGORITHM_MAX_PARALLEL', 2),

        // Execution timeout in seconds
        'timeout' => env('ALGORITHM_TIMEOUT', 15),

        // Batch processing size
        'batch_size' => env('ALGORITHM_BATCH_SIZE', 10),

        // Enable lazy loading of algorithms
        'lazy_loading' => env('ALGORITHM_LAZY_LOADING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration - Optimized for 500+ concurrent requests
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        // Diagnostic requests per minute
        'diagnostic' => [
            'requests' => env('ALGORITHM_RATE_DIAGNOSTIC', 600),
            'window' => 60,
            'burst' => 100,
        ],

        // Single algorithm requests per minute
        'algorithm' => [
            'requests' => env('ALGORITHM_RATE_SINGLE', 2000),
            'window' => 60,
            'burst' => 200,
        ],

        // Batch requests per minute
        'batch' => [
            'requests' => env('ALGORITHM_RATE_BATCH', 50),
            'window' => 60,
            'burst' => 10,
        ],

        // Global system limit
        'global' => [
            'requests' => env('ALGORITHM_RATE_GLOBAL', 3000),
            'window' => 60,
            'burst' => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        // Queue connection (null = default)
        'connection' => env('ALGORITHM_QUEUE_CONNECTION', null),

        // Queue names by priority
        'queues' => [
            'high' => 'diagnostic-high',
            'default' => 'diagnostic',
            'low' => 'diagnostic-low',
        ],

        // Max retries for failed jobs
        'max_retries' => env('ALGORITHM_QUEUE_RETRIES', 3),

        // Retry delays in seconds
        'retry_delays' => [5, 15, 30],
    ],

    /*
    |--------------------------------------------------------------------------
    | Algorithm Weights
    |--------------------------------------------------------------------------
    |
    | Weights for calculating overall score from individual algorithms.
    | Must sum to 1.0
    |
    */
    'weights' => [
        'health_score' => 0.25,
        'dream_buyer_analysis' => 0.20,
        'offer_strength' => 0.15,
        'funnel_analysis' => 0.20,
        'engagement_metrics' => 0.10,
        'content_optimization' => 0.10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Benchmarks
    |--------------------------------------------------------------------------
    |
    | Default industry benchmarks when no specific data is available.
    |
    */
    'default_benchmarks' => [
        'conversion_rate' => 2.5,
        'engagement_rate' => 3.0,
        'response_time_hours' => 2,
        'customer_retention' => 70,
        'cac_ltv_ratio' => 3.0,
        'repeat_purchase_rate' => 25,
        'funnel_conversion' => [
            'awareness_to_interest' => 30,
            'interest_to_consideration' => 50,
            'consideration_to_intent' => 40,
            'intent_to_purchase' => 25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Score Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for categorizing scores.
    |
    */
    'thresholds' => [
        'excellent' => 80,
        'good' => 60,
        'average' => 40,
        'weak' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        // Enable detailed logging
        'enabled' => env('ALGORITHM_LOGGING', true),

        // Log slow computations (in ms)
        'slow_threshold' => env('ALGORITHM_SLOW_THRESHOLD', 500),

        // Log channel
        'channel' => env('ALGORITHM_LOG_CHANNEL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    */
    'debug' => [
        // Include execution times in response
        'include_times' => env('ALGORITHM_DEBUG_TIMES', true),

        // Include raw data in response
        'include_raw' => env('ALGORITHM_DEBUG_RAW', false),
    ],
];
