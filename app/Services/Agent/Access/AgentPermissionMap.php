<?php

namespace App\Services\Agent\Access;

/**
 * Agent ruxsatnoma matritsasi — DRY markaziy konfiguratsiya.
 *
 * Qaysi rol/bo'lim qaysi agentlarga va vositalarga ruxsat oladi.
 * Barcha cheklovlar shu bitta faylda — o'zgartirish oson.
 *
 * Tuzilma:
 * - business_role: owner/admin/manager/member/viewer (business_user.role)
 * - department: sales_head/marketing/sales_operator/hr/finance (business_user.department)
 * - spatie_role: super_admin/operator/... (global Spatie rol)
 */
class AgentPermissionMap
{
    /**
     * Biznes rollari → ruxsat etilgan agentlar va harakatlar.
     * owner va admin barcha agentlarga kiradi.
     */
    public const ROLE_AGENT_ACCESS = [
        'owner' => [
            'agents' => ['analytics', 'marketing', 'sales', 'call_center', 'orchestrator'],
            'actions' => ['ask', 'view_conversations', 'view_reports', 'manage_settings', 'view_usage'],
            'max_daily_questions' => null, // tarif bo'yicha
        ],
        'admin' => [
            'agents' => ['analytics', 'marketing', 'sales', 'call_center', 'orchestrator'],
            'actions' => ['ask', 'view_conversations', 'view_reports', 'view_usage'],
            'max_daily_questions' => null,
        ],
        'manager' => [
            'agents' => ['analytics', 'marketing', 'sales', 'orchestrator'],
            'actions' => ['ask', 'view_conversations', 'view_reports'],
            'max_daily_questions' => null,
        ],
        'member' => [
            'agents' => ['analytics', 'sales', 'orchestrator'],
            'actions' => ['ask', 'view_conversations'],
            'max_daily_questions' => 20, // member uchun qo'shimcha limit
        ],
        'viewer' => [
            'agents' => ['analytics', 'orchestrator'],
            'actions' => ['ask', 'view_conversations'],
            'max_daily_questions' => 5, // viewer uchun minimal
        ],
    ];

    /**
     * Bo'lim → qo'shimcha ruxsat etilgan agentlar.
     * Bo'lim xodimi o'z sohasiga oid agentga to'liq kiradi.
     */
    public const DEPARTMENT_AGENT_BOOST = [
        'sales_head' => [
            'extra_agents' => ['sales', 'call_center', 'analytics'],
            'extra_actions' => ['view_reports', 'manage_team_training'],
        ],
        'sales_operator' => [
            'extra_agents' => ['sales'],
            'extra_actions' => ['handle_chat'],
        ],
        'marketing' => [
            'extra_agents' => ['marketing', 'analytics'],
            'extra_actions' => ['view_reports'],
        ],
        'finance' => [
            'extra_agents' => ['analytics'],
            'extra_actions' => ['view_reports', 'view_usage'],
        ],
        'hr' => [
            'extra_agents' => [],
            'extra_actions' => ['manage_team_training'],
        ],
    ];

    /**
     * Qo'shimcha modullar — rol bo'yicha ruxsat.
     * owner va admin barchaga kiradi, boshqalar cheklangan.
     */
    public const MODULE_ACCESS = [
        'health_monitor' => ['owner', 'admin'],
        'lifecycle' => ['owner', 'admin', 'manager'],
        'seasonal_planner' => ['owner', 'admin', 'manager'],
        'cash_flow' => ['owner', 'admin'],             // moliyaviy — faqat yuqori rollar
        'reputation' => ['owner', 'admin', 'manager'],
        'voice' => ['owner', 'admin', 'manager'],
        'trainer' => ['owner', 'admin', 'manager'],    // trener — member lar faqat mashq qiladi
    ];

    /**
     * Agent savollari ichida ko'rib bo'ladigan ma'lumotlar scope.
     * Har bir rol faqat o'z doirasidagi ma'lumotlarni ko'radi.
     */
    public const DATA_SCOPE = [
        'owner' => 'all',           // barcha ma'lumotlar
        'admin' => 'all',           // barcha ma'lumotlar
        'manager' => 'department',  // o'z bo'limi ma'lumotlari
        'member' => 'own',          // faqat o'z ma'lumotlari
        'viewer' => 'summary',      // faqat umumiy ko'rsatkichlar
    ];
}
