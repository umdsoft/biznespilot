<?php

/**
 * Viral Niches Configuration
 *
 * Maps business categories to Instagram hashtags for TrendSee module.
 * Used for "Instant Viral Feed" - auto-seeding relevant content based on user's business.
 *
 * Structure:
 * - Key: Internal business category (lowercase, from Business model)
 * - Value: Array of Instagram hashtags to fetch (without #, will be added automatically)
 */

return [
    // Education & Training
    'education' => [
        'educationuz', 'ilmuz', 'studentlife', 'najottalim', 'onlinecourse',
        'talim', 'oquv', 'kursuz', 'teacheruz', 'ustoz',
    ],

    // Retail & E-commerce
    'retail' => [
        'savdo', 'fashionuz', 'ozbekistamoda', 'shopping', 'magazinuz',
        'onlinesavdo', 'kiyimuz', 'branduz', 'shopuz', 'dokonuz',
    ],

    // Food & Restaurant
    'food' => [
        'fooduz', 'restoran', 'mazzali', 'tashkentfood', 'cafeuz',
        'uzbekfood', 'oshxona', 'yemakuz', 'deliveryuz', 'fastfooduz',
    ],

    // Beauty & Cosmetics
    'beauty' => [
        'beautyuz', 'gozellik', 'salonuz', 'makeupuz', 'kosmetika',
        'skincare', 'barbershopuz', 'sartaroshxona', 'nailsuz', 'lashesuz',
    ],

    // Health & Fitness
    'health' => [
        'fitnessuz', 'gymuz', 'sportuz', 'salomatlik', 'yogauz',
        'healthyuz', 'dietauz', 'treneruz', 'workoutuz', 'zaltashkent',
    ],

    // IT & Technology
    'technology' => [
        'ituz', 'programminguz', 'techuz', 'developeruz', 'startupuz',
        'smmuz', 'digitaluz', 'webdesignuz', 'mobileappuz', 'aiuz',
    ],

    // Real Estate
    'realestate' => [
        'kvartirauz', 'uyuz', 'realestateuz', 'novruzrealty', 'tashkentkv',
        'ijara', 'sotiladi', 'yangiuylar', 'kvartiralar', 'domuz',
    ],

    // Auto & Transport
    'auto' => [
        'avtouz', 'mashina', 'carsuz', 'autosalon', 'avtomobil',
        'chevroletuz', 'bmwuz', 'mercedesuz', 'avtoservis', 'tireuz',
    ],

    // Services
    'services' => [
        'xizmatuz', 'serviceuz', 'masteruz', 'ustalar', 'remontuz',
        'cleaninguz', 'deliveryuz', 'kurieruz', 'freelanceruz', 'konsalting',
    ],

    // Marketing & Advertising
    'marketing' => [
        'marketinguz', 'smmuz', 'targetuz', 'reklamauz', 'brandinguz',
        'contentuz', 'socialuz', 'instagramuz', 'digitalmarketing', 'adsuz',
    ],

    // Finance & Consulting
    'finance' => [
        'moliya', 'financeuz', 'bankuz', 'kredituz', 'investuz',
        'biznesuz', 'accountinguz', 'hisobchi', 'soliq', 'audituz',
    ],

    // Events & Entertainment
    'events' => [
        'toylartashkent', 'eventuz', 'prazdnikuz', 'weddinguz', 'organizator',
        'tamada', 'dj', 'musicuz', 'showuz', 'concertuz',
    ],

    // Construction & Repair
    'construction' => [
        'qurilish', 'remontuz', 'stroyka', 'arxitektoruz', 'dizayninterier',
        'mebeluz', 'santexnik', 'elektruz', 'evroremontuz', 'tamirlash',
    ],

    // Tourism & Travel
    'tourism' => [
        'tourismuz', 'sayohat', 'traveluz', 'uzbekistantravel', 'touruz',
        'vizauz', 'hoteluz', 'samarkand', 'buxoro', 'xiva',
    ],

    // Agriculture
    'agriculture' => [
        'qishloqxojaligi', 'fermuz', 'agro', 'dehqonchilik', 'chorvachilik',
        'mevalar', 'sabzavotlar', 'organicuz', 'issiqxona', 'parranda',
    ],

    // General Business (fallback)
    'general' => [
        'businessuz', 'tadbirkor', 'marketinguz', 'trenduz', 'startupuz',
        'uzbekistanbusiness', 'smmuz', 'motivationuz', 'successuz', 'ceouz',
    ],

    // Default (when category not found)
    'default' => [
        'businessuz', 'trenduz', 'viraluz', 'uzbekistan', 'tashkent',
    ],
];
