<?php

namespace App\Events\HR;

use App\Models\User;
use App\Models\Business;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Hodim ketish xavfi darajasi o'zgarganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Yuqori xavf - HR ga zudlik bilan xabar
 * - Stay interview taklif qilish
 * - Retention strategiyasini boshlash
 * - Manager ga ogohlantirish
 */
class FlightRiskLevelChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Ketish xavfi darajalari
    public const LEVEL_LOW = 'low';           // 0-25%
    public const LEVEL_MODERATE = 'moderate'; // 26-50%
    public const LEVEL_HIGH = 'high';         // 51-75%
    public const LEVEL_CRITICAL = 'critical'; // 76-100%

    public function __construct(
        public User $employee,
        public Business $business,
        public string $oldLevel,
        public string $newLevel,
        public float $riskScore,
        public ?array $riskFactors = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hr.' . $this->business->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.flight-risk-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'old_level' => $this->oldLevel,
            'new_level' => $this->newLevel,
            'risk_score' => $this->riskScore,
            'level_label' => $this->getLevelLabel(),
            'requires_immediate_action' => $this->requiresImmediateAction(),
            'top_risk_factors' => $this->getTopRiskFactors(),
        ];
    }

    /**
     * Darajani o'zbek tilida olish
     */
    public function getLevelLabel(): string
    {
        return match($this->newLevel) {
            self::LEVEL_LOW => "Past xavf",
            self::LEVEL_MODERATE => "O'rtacha xavf",
            self::LEVEL_HIGH => "Yuqori xavf",
            self::LEVEL_CRITICAL => "Jiddiy xavf",
            default => "Noma'lum",
        };
    }

    /**
     * Zudlik bilan harakat kerakmi?
     */
    public function requiresImmediateAction(): bool
    {
        return in_array($this->newLevel, [self::LEVEL_HIGH, self::LEVEL_CRITICAL]);
    }

    /**
     * Eng muhim xavf omillarini olish
     */
    public function getTopRiskFactors(int $limit = 3): array
    {
        if (empty($this->riskFactors)) {
            return [];
        }

        // Xavf omillarini ta'sir darajasi bo'yicha saralash
        $sorted = collect($this->riskFactors)
            ->sortByDesc('impact')
            ->take($limit)
            ->values()
            ->toArray();

        return $sorted;
    }

    /**
     * Xavf darajasi oshganmi?
     */
    public function isRiskIncreased(): bool
    {
        $levels = [
            self::LEVEL_LOW => 1,
            self::LEVEL_MODERATE => 2,
            self::LEVEL_HIGH => 3,
            self::LEVEL_CRITICAL => 4,
        ];

        return ($levels[$this->newLevel] ?? 0) > ($levels[$this->oldLevel] ?? 0);
    }

    /**
     * Tavsiya qilinadigan harakatlarni olish
     */
    public function getRecommendedActions(): array
    {
        return match($this->newLevel) {
            self::LEVEL_CRITICAL => [
                'stay_interview' => "Zudlik bilan suhbat o'tkazish",
                'salary_review' => "Maosh ko'rib chiqish",
                'manager_meeting' => "Rahbar bilan uchrashuv",
            ],
            self::LEVEL_HIGH => [
                'one_on_one' => "1-on-1 suhbat tayinlash",
                'career_discussion' => "Karyera rivojlanishini muhokama qilish",
                'engagement_check' => "Engagement holatini tekshirish",
            ],
            self::LEVEL_MODERATE => [
                'pulse_check' => "Qisqa so'rovnoma yuborish",
                'recognition' => "Minnatdorchilik bildirish",
            ],
            default => [],
        };
    }
}
