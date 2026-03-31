<?php

namespace App\Services;

/**
 * Markazlashtirilgan tarif limit va feature konfiguratsiyasi.
 * PlanLimitService va SubscriptionGate uchun yagona manba.
 */
class PlanConfig
{
    /**
     * Limit konfiguratsiasi — barcha limitlar shu yerda aniqlanadi.
     */
    public static function limits(): array
    {
        return [
            'users' => [
                'label' => 'Foydalanuvchilar soni',
                'method' => 'getUsersCount',
                'icon' => 'users',
                'suffix' => 'ta',
                'unlimited_value' => -1,
                'legacy_column' => 'team_member_limit',
            ],
            'team_members' => [
                'label' => 'Team member limit',
                'method' => 'getTeamMembersCount',
                'icon' => 'users',
                'suffix' => 'ta',
                'unlimited_value' => -1,
                'legacy_column' => 'team_member_limit',
            ],
            'branches' => [
                'label' => 'Filiallar soni',
                'method' => 'getBranchesCount',
                'icon' => 'building',
                'suffix' => 'ta',
                'unlimited_value' => -1,
            ],
            'instagram_accounts' => [
                'label' => 'Instagram akkauntlar',
                'method' => 'getInstagramAccountsCount',
                'icon' => 'instagram',
                'suffix' => 'ta',
                'unlimited_value' => -1,
            ],
            'monthly_leads' => [
                'label' => 'Oylik lidlar',
                'method' => 'getMonthlyLeadsCount',
                'icon' => 'user-plus',
                'suffix' => 'ta',
                'unlimited_value' => -1,
                'legacy_column' => 'lead_limit',
            ],
            'ai_call_minutes' => [
                'label' => 'Qo\'ng\'iroqlar AI tahlili',
                'method' => 'getAiCallMinutesUsed',
                'icon' => 'phone',
                'suffix' => 'daq',
                'unlimited_value' => -1,
                'legacy_column' => 'audio_minutes_limit',
            ],
            'chatbot_channels' => [
                'label' => 'Chatbot kanallari',
                'method' => 'getChatbotChannelsCount',
                'icon' => 'chat',
                'suffix' => 'ta',
                'unlimited_value' => -1,
                'legacy_column' => 'chatbot_channel_limit',
            ],
            'telegram_bots' => [
                'label' => 'Telegram botlar',
                'method' => 'getTelegramBotsCount',
                'icon' => 'telegram',
                'suffix' => 'ta',
                'unlimited_value' => -1,
                'legacy_column' => 'telegram_bot_limit',
            ],
            'ai_requests' => [
                'label' => 'AI so\'rovlar',
                'method' => 'getAiRequestsCount',
                'icon' => 'sparkles',
                'suffix' => 'ta',
                'unlimited_value' => -1,
                'legacy_column' => 'ai_requests_limit',
            ],
            'storage_mb' => [
                'label' => 'Saqlash hajmi',
                'method' => 'getStorageUsedMb',
                'icon' => 'database',
                'suffix' => 'MB',
                'unlimited_value' => -1,
                'legacy_column' => 'storage_limit_mb',
            ],
        ];
    }

    /**
     * Feature konfiguratsiasi — barcha featurelar shu yerda aniqlanadi.
     */
    public static function features(): array
    {
        return [
            'hr_tasks' => [
                'label' => 'HR vazifalar',
                'description' => 'Vazifalar va loyihalar boshqaruvi',
            ],
            'hr_bot' => [
                'label' => 'Ishga olish boti',
                'description' => 'Avtomatlashtirilgan HR chatbot',
            ],
            'anti_fraud' => [
                'label' => 'SMS ogohlantirish',
                'description' => 'Fraud aniqlash va ogohlantirish',
            ],
            'onboarding' => [
                'label' => 'Onboarding',
                'description' => 'Yangi mijoz uchun onboarding yordam',
            ],
            'personal_manager' => [
                'label' => 'Shaxsiy menejer',
                'description' => 'Shaxsiy menejer tayinlash',
            ],
        ];
    }
}
