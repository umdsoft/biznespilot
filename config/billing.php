<?php

/**
 * Billing Configuration - Payme va Click Merchant Integratsiyasi
 *
 * MUHIM: .env faylida quyidagi o'zgaruvchilarni sozlang:
 *
 * # Payme Merchant
 * PAYME_MERCHANT_ID=your_merchant_id
 * PAYME_MERCHANT_KEY=your_secret_key
 * PAYME_TEST_MODE=true
 *
 * # Click Merchant
 * CLICK_SERVICE_ID=your_service_id
 * CLICK_MERCHANT_ID=your_merchant_id
 * CLICK_SECRET_KEY=your_secret_key
 * CLICK_MERCHANT_USER_ID=your_user_id
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | Default to'lov tizimi. 'payme' yoki 'click'.
    |
    */
    'default' => env('BILLING_DEFAULT_PROVIDER', 'payme'),

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    */
    'currency' => [
        'code' => 'UZS',
        'name' => "O'zbek so'mi",
        // Minimal to'lov summasi (so'mda)
        'min_amount' => env('BILLING_MIN_AMOUNT', 1000),
        // Maksimal to'lov summasi (so'mda)
        'max_amount' => env('BILLING_MAX_AMOUNT', 100000000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payme Configuration
    |--------------------------------------------------------------------------
    |
    | Payme Merchant API sozlamalari.
    | Hujjatlar: https://developer.payme.uz
    |
    */
    'payme' => [
        // Merchant credentials
        'merchant_id' => env('PAYME_MERCHANT_ID'),
        'merchant_key' => env('PAYME_MERCHANT_KEY'),

        // Test mode (sandbox)
        'test_mode' => env('PAYME_TEST_MODE', true),

        // API endpoints
        'checkout_url' => env('PAYME_CHECKOUT_URL', 'https://checkout.payme.uz'),
        'test_checkout_url' => env('PAYME_TEST_CHECKOUT_URL', 'https://test.payme.uz'),

        // Callback URL (webhook endpoint)
        'callback_url' => env('PAYME_CALLBACK_URL', '/api/billing/payme'),

        // Vaqt limitlari (milliseconds)
        'timeout' => [
            // Tranzaksiya yaratilgandan keyin to'lov uchun vaqt (12 soat)
            'create_transaction' => env('PAYME_CREATE_TIMEOUT', 43200000),
            // Tranzaksiya muddati (24 soat)
            'transaction_timeout' => env('PAYME_TRANSACTION_TIMEOUT', 86400000),
        ],

        // Allowed IPs (Payme serverlar IP'lari)
        'allowed_ips' => array_filter(explode(',', env('PAYME_ALLOWED_IPS', ''))),

        // Min/Max amounts (so'mda)
        'min_amount' => env('PAYME_MIN_AMOUNT', 1000),
        'max_amount' => env('PAYME_MAX_AMOUNT', 100000000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Click Configuration
    |--------------------------------------------------------------------------
    |
    | Click Merchant API sozlamalari.
    | Hujjatlar: https://docs.click.uz
    |
    */
    'click' => [
        // Merchant credentials
        'service_id' => env('CLICK_SERVICE_ID'),
        'merchant_id' => env('CLICK_MERCHANT_ID'),
        'merchant_user_id' => env('CLICK_MERCHANT_USER_ID'),
        'secret_key' => env('CLICK_SECRET_KEY'),

        // Test mode
        'test_mode' => env('CLICK_TEST_MODE', true),

        // API endpoints
        'checkout_url' => env('CLICK_CHECKOUT_URL', 'https://my.click.uz/services/pay'),
        'test_checkout_url' => env('CLICK_TEST_CHECKOUT_URL', 'https://test.click.uz/services/pay'),

        // Callback URL
        'prepare_url' => env('CLICK_PREPARE_URL', '/api/billing/click/prepare'),
        'complete_url' => env('CLICK_COMPLETE_URL', '/api/billing/click/complete'),

        // Vaqt limitlari
        'timeout' => [
            'transaction_timeout' => env('CLICK_TRANSACTION_TIMEOUT', 86400), // seconds
        ],

        // Allowed IPs
        'allowed_ips' => array_filter(explode(',', env('CLICK_ALLOWED_IPS', ''))),

        // Min/Max amounts
        'min_amount' => env('CLICK_MIN_AMOUNT', 1000),
        'max_amount' => env('CLICK_MAX_AMOUNT', 100000000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    */
    'transaction' => [
        // Order ID prefix
        'order_prefix' => env('BILLING_ORDER_PREFIX', 'BP'),

        // Default expiration time (hours)
        'expiration_hours' => env('BILLING_EXPIRATION_HOURS', 24),

        // Retry settings
        'max_retries' => env('BILLING_MAX_RETRIES', 3),
        'retry_delay' => env('BILLING_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        // Log all requests
        'log_requests' => env('BILLING_LOG_REQUESTS', true),

        // Verify IP addresses
        'verify_ip' => env('BILLING_VERIFY_IP', true),

        // Log channel
        'log_channel' => env('BILLING_LOG_CHANNEL', 'billing'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Return URLs
    |--------------------------------------------------------------------------
    |
    | To'lovdan keyin foydalanuvchi qaytariladigan sahifalar.
    |
    */
    'urls' => [
        'success' => env('BILLING_SUCCESS_URL', '/billing/success'),
        'cancel' => env('BILLING_CANCEL_URL', '/billing/cancel'),
        'fail' => env('BILLING_FAIL_URL', '/billing/fail'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        // Email notifications
        'email' => [
            'enabled' => env('BILLING_EMAIL_NOTIFICATIONS', true),
            'admin_email' => env('BILLING_ADMIN_EMAIL'),
        ],

        // Telegram notifications
        'telegram' => [
            'enabled' => env('BILLING_TELEGRAM_NOTIFICATIONS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Messages (Uzbek)
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'transaction_not_found' => 'Tranzaksiya topilmadi',
        'invalid_amount' => 'Noto\'g\'ri summa',
        'order_not_found' => 'Buyurtma topilmadi',
        'already_paid' => 'Bu buyurtma allaqachon to\'langan',
        'transaction_cancelled' => 'Tranzaksiya bekor qilingan',
        'transaction_expired' => 'Tranzaksiya muddati tugagan',
        'invalid_state' => 'Tranzaksiya holati noto\'g\'ri',
        'cannot_cancel' => 'Bu tranzaksiyani bekor qilib bo\'lmaydi',
        'sign_check_failed' => 'Imzo tekshiruvi muvaffaqiyatsiz',
        'system_error' => 'Tizim xatoligi. Keyinroq urinib ko\'ring',
        'payment_success' => 'To\'lov muvaffaqiyatli amalga oshirildi',
        'payment_cancelled' => 'To\'lov bekor qilindi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payme Error Codes
    |--------------------------------------------------------------------------
    */
    'payme_errors' => [
        -31001 => 'Noto\'g\'ri summa',
        -31003 => 'Tranzaksiya topilmadi',
        -31004 => 'Tranzaksiyani yaratib bo\'lmaydi',
        -31005 => 'Buyurtma topilmadi',
        -31007 => 'Tranzaksiyani bajarib bo\'lmaydi',
        -31008 => 'Tranzaksiya vaqti tugagan',
        -31050 => 'Noma\'lum xatolik',
        -31099 => 'Autentifikatsiya muvaffaqiyatsiz',
    ],

    /*
    |--------------------------------------------------------------------------
    | Click Error Codes
    |--------------------------------------------------------------------------
    */
    'click_errors' => [
        0 => 'Muvaffaqiyat',
        -1 => 'Imzo tekshiruvi muvaffaqiyatsiz',
        -2 => 'Noto\'g\'ri summa',
        -3 => 'Tranzaksiya yaratilmadi',
        -4 => 'Tranzaksiya allaqachon mavjud',
        -5 => 'Buyurtma topilmadi',
        -6 => 'Tranzaksiya bekor qilingan',
        -7 => 'To\'lov qabul qilinmadi',
        -8 => 'Tranzaksiya bajarilmadi',
        -9 => 'Noma\'lum xatolik',
    ],
];
