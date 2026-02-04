<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
        'app_secret' => env('META_APP_SECRET'), // For webhook signature verification
    ],

    'instagram' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', 'biznespilot_webhook_token'),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'redirect_uri' => env('META_REDIRECT_URI', '/business/meta-ads/callback'),
        'ad_library_token' => env('META_AD_LIBRARY_TOKEN'),
        'api_version' => env('META_API_VERSION', 'v21.0'),
        // OAuth scopes — Meta Ads integratsiya uchun kerakli permission lar
        // MUHIM: Live mode da faqat App Review dan o'tgan scope lar ishlaydi!
        // Development mode da barcha scope lar developer/tester larga ishlaydi.
        //
        // Kerakli scope lar va ularni ishlatadigan endpoint lar:
        //   ads_read            → GET /me/adaccounts, /{account}/campaigns, insights
        //   ads_management      → POST campaign/adset/ad yaratish va tahrirlash
        //   pages_read_engagement → GET /me/accounts (Facebook Pages ro'yxati)
        //
        // Live mode ga o'tkazishdan oldin Facebook App Review orqali
        // ads_read, ads_management, pages_read_engagement ni approve qildiring.
        'scopes' => [
            'public_profile',
            'email',
            'ads_read',
            'ads_management',
            'pages_read_engagement',
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'google_ads' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'redirect' => env('GOOGLE_ADS_REDIRECT_URI'),
    ],

    'yandex' => [
        'client_id' => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
    ],

    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'biznespilot_whatsapp_verify_token'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    ],

    'analytics' => [
        'ga4_id' => env('GA4_MEASUREMENT_ID'),
        'yandex_id' => env('YANDEX_METRIKA_ID'),
        'meta_pixel_id' => env('META_PIXEL_ID'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | BiznesPilot System Bot (Dual Bot Strategy)
    |--------------------------------------------------------------------------
    |
    | System Bot - BiznesPilot dan Business Owner larga notification yuborish.
    | Bu bot Tenant Bot lardan alohida va faqat system-to-admin xabarlar uchun.
    |
    */
    'telegram' => [
        'system_bot_token' => env('TELEGRAM_SYSTEM_BOT_TOKEN'),
        'system_bot_username' => env('TELEGRAM_SYSTEM_BOT_USERNAME', 'BiznesPilotBot'),
        'webhook_secret' => env('TELEGRAM_SYSTEM_WEBHOOK_SECRET'),
        'webhook_url' => env('TELEGRAM_SYSTEM_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Apify - TrendSee (Viral Content Hunter)
    |--------------------------------------------------------------------------
    |
    | Apify Instagram Scraper for fetching viral Instagram Reels.
    | Uses synchronous endpoint for instant results.
    |
    | Get your token at: https://console.apify.com/account/integrations
    |
    */
    'apify' => [
        'token' => env('APIFY_TOKEN'),
    ],

];
