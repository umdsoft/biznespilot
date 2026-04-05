<?php

namespace App\Services\Agent\Access;

use App\Models\Business;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Agent ruxsatnoma xizmati — DRY markaziy tekshiruv nuqtasi.
 *
 * Barcha agent so'rovlari shu xizmat orqali o'tadi.
 * Tekshiradi:
 * 1. Foydalanuvchi roli → qaysi agentga ruxsat
 * 2. Bo'limi → qo'shimcha ruxsatlar
 * 3. Tarif limiti → kunlik/oylik cheklov
 * 4. Ma'lumot doirasi → nima ko'rishi mumkin
 *
 * Boshqa xizmatlar faqat shu orqali tekshiradi — DRY.
 */
class AgentAccessService
{
    public function __construct(
        private PlanLimitService $planLimitService,
    ) {}

    /**
     * Foydalanuvchi agent tizimiga kira oladimi — to'liq tekshiruv.
     *
     * @return array{allowed: bool, reason: string|null, context: array}
     */
    public function checkAccess(User $user, Business $business, string $action = 'ask'): array
    {
        // 1. Foydalanuvchi bu biznesga tegishlimi
        $userContext = $this->getUserContext($user, $business);

        if (!$userContext['has_access']) {
            return $this->denied('Bu biznesga kirishga ruxsat yo\'q.');
        }

        // 2. Tarif tekshiruvi — obuna bormi
        if (!$business->hasActiveSubscription()) {
            return $this->denied('Faol obuna yo\'q. AI Agent uchun obuna kerak.');
        }

        // 3. Harakat ruxsati — bu rol bu amalni bajara oladimi
        $roleAccess = AgentPermissionMap::ROLE_AGENT_ACCESS[$userContext['role']] ?? null;
        if (!$roleAccess || !in_array($action, $roleAccess['actions'])) {
            return $this->denied("Sizning rolingiz ({$userContext['role']}) bu amalni bajara olmaydi.");
        }

        // 4. Kunlik savol limiti (tarif + rol cheklovi)
        $dailyLimit = $this->getDailyQuestionLimit($business, $userContext['role']);
        if ($dailyLimit !== null) {
            $todayCount = $this->getTodayQuestionCount($business->id, $user->id);
            if ($todayCount >= $dailyLimit) {
                return $this->denied("Kunlik savol limiti tugadi ({$dailyLimit} ta). Ertaga qayta urinib ko'ring.");
            }
        }

        return [
            'allowed' => true,
            'reason' => null,
            'context' => $userContext,
        ];
    }

    /**
     * Foydalanuvchi ma'lum bir agentga so'rov yuborishi mumkinmi.
     */
    public function canAccessAgent(User $user, Business $business, string $agentType): bool
    {
        $userContext = $this->getUserContext($user, $business);
        $allowedAgents = $this->getAllowedAgents($userContext);

        return in_array($agentType, $allowedAgents);
    }

    /**
     * Foydalanuvchi uchun ruxsat etilgan agentlar ro'yxati.
     */
    public function getAllowedAgents(array $userContext): array
    {
        $role = $userContext['role'];
        $department = $userContext['department'];

        // Roldan kelgan agentlar
        $roleAgents = AgentPermissionMap::ROLE_AGENT_ACCESS[$role]['agents'] ?? ['orchestrator'];

        // Bo'limdan kelgan qo'shimcha agentlar
        $deptBoost = AgentPermissionMap::DEPARTMENT_AGENT_BOOST[$department] ?? null;
        $deptAgents = $deptBoost['extra_agents'] ?? [];

        // Birlashtirish va takrorlanishlarni olib tashlash
        return array_values(array_unique(array_merge($roleAgents, $deptAgents)));
    }

    /**
     * Foydalanuvchi qo'shimcha modulga kira oladimi.
     */
    public function canAccessModule(User $user, Business $business, string $module): bool
    {
        $userContext = $this->getUserContext($user, $business);
        $allowedRoles = AgentPermissionMap::MODULE_ACCESS[$module] ?? [];

        return in_array($userContext['role'], $allowedRoles);
    }

    /**
     * Foydalanuvchi ma'lumot doirasi — qancha ma'lumot ko'rishi mumkin.
     */
    public function getDataScope(User $user, Business $business): string
    {
        $userContext = $this->getUserContext($user, $business);
        return AgentPermissionMap::DATA_SCOPE[$userContext['role']] ?? 'summary';
    }

    /**
     * Foydalanuvchining biznes ichidagi kontekstini olish.
     * Keshlanadi — har so'rovda bazaga murojaat qilmaymiz.
     */
    public function getUserContext(User $user, Business $business): array
    {
        $cacheKey = "agent_user_ctx:{$business->id}:{$user->id}";

        // Session keshdan olish (tez)
        $cached = session($cacheKey);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        // Biznes egasimi
        $isOwner = $business->user_id === $user->id;

        if ($isOwner) {
            $context = [
                'has_access' => true,
                'role' => 'owner',
                'department' => null,
                'is_owner' => true,
                'user_id' => $user->id,
                'business_id' => $business->id,
            ];
        } else {
            // Jamoa a'zosi — pivot jadvaldan
            $pivot = DB::table('business_user')
                ->where('business_id', $business->id)
                ->where('user_id', $user->id)
                ->first(['role', 'department']);

            if (!$pivot) {
                // Super admin tekshiruvi
                if ($user->hasRole('super_admin')) {
                    $context = [
                        'has_access' => true,
                        'role' => 'admin',
                        'department' => null,
                        'is_owner' => false,
                        'user_id' => $user->id,
                        'business_id' => $business->id,
                    ];
                } else {
                    return ['has_access' => false, 'role' => null, 'department' => null, 'is_owner' => false, 'user_id' => $user->id, 'business_id' => $business->id];
                }
            } else {
                $context = [
                    'has_access' => true,
                    'role' => $pivot->role ?? 'member',
                    'department' => $pivot->department,
                    'is_owner' => false,
                    'user_id' => $user->id,
                    'business_id' => $business->id,
                ];
            }
        }

        // Session ga keshlash
        session([$cacheKey => $context]);

        return $context;
    }

    /**
     * Kunlik savol limiti — tarif + rol birgalikda.
     * null = cheksiz
     */
    private function getDailyQuestionLimit(Business $business, string $role): ?int
    {
        // Tarif limiti
        $planLimit = null;
        try {
            $planLimit = $this->planLimitService->getRemainingQuota($business, 'agent_questions_daily');
            // null = cheksiz (tarif bo'yicha)
        } catch (\Exception $e) {
            // Limit tizimi ishlamasa — cheksiz
        }

        // Rol limiti
        $roleLimit = AgentPermissionMap::ROLE_AGENT_ACCESS[$role]['max_daily_questions'] ?? null;

        // Ikkalasining eng kichigini olish
        if ($planLimit === null && $roleLimit === null) return null;
        if ($planLimit === null) return $roleLimit;
        if ($roleLimit === null) return $planLimit;

        return min($planLimit, $roleLimit);
    }

    /**
     * Bugungi savol soni (foydalanuvchi bo'yicha)
     */
    private function getTodayQuestionCount(string $businessId, string $userId): int
    {
        return DB::table('agent_messages')
            ->where('business_id', $businessId)
            ->where('role', 'user')
            ->whereRaw("EXISTS (
                SELECT 1 FROM agent_conversations
                WHERE agent_conversations.id = agent_messages.conversation_id
                AND agent_conversations.user_id = ?
            )", [$userId])
            ->whereDate('agent_messages.created_at', now()->toDateString())
            ->count();
    }

    /**
     * Ruxsat berilmadi javob formati
     */
    private function denied(string $reason): array
    {
        return [
            'allowed' => false,
            'reason' => $reason,
            'context' => [],
        ];
    }
}
