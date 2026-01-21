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
 * Hodim davomat naqshida o'zgarish aniqlanganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Salbiy trend - HR ga ogohlantirish
 * - Flight risk ballini yangilash
 * - Manager ga xabar yuborish
 * - Engagement tahlilini yangilash
 */
class AttendancePatternChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Naqsh turlari
    public const PATTERN_LATE_ARRIVALS = 'late_arrivals';       // Ko'p kechikish
    public const PATTERN_EARLY_LEAVES = 'early_leaves';         // Erta ketish
    public const PATTERN_FREQUENT_ABSENCES = 'frequent_absences'; // Ko'p yo'qlik
    public const PATTERN_MONDAY_SYNDROME = 'monday_syndrome';   // Dushanba sindromi
    public const PATTERN_FRIDAY_SYNDROME = 'friday_syndrome';   // Juma sindromi
    public const PATTERN_IMPROVED = 'improved';                 // Yaxshilangan

    public function __construct(
        public User $employee,
        public Business $business,
        public string $patternType,
        public ?float $severity = null,  // 1-10
        public ?array $patternData = null,
        public ?string $period = null  // last_week, last_month, last_quarter
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hr.' . $this->business->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.attendance-pattern-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'pattern_type' => $this->patternType,
            'pattern_label' => $this->getPatternLabel(),
            'severity' => $this->severity,
            'severity_label' => $this->getSeverityLabel(),
            'is_concerning' => $this->isConcerning(),
            'period' => $this->period,
        ];
    }

    /**
     * Naqsh turini o'zbek tilida olish
     */
    public function getPatternLabel(): string
    {
        return match($this->patternType) {
            self::PATTERN_LATE_ARRIVALS => "Tez-tez kechikish",
            self::PATTERN_EARLY_LEAVES => "Erta ketish",
            self::PATTERN_FREQUENT_ABSENCES => "Ko'p yo'qlik",
            self::PATTERN_MONDAY_SYNDROME => "Dushanba sindromi",
            self::PATTERN_FRIDAY_SYNDROME => "Juma sindromi",
            self::PATTERN_IMPROVED => "Davomat yaxshilangan",
            default => "Noma'lum naqsh",
        };
    }

    /**
     * Jiddiylik darajasini o'zbek tilida olish
     */
    public function getSeverityLabel(): string
    {
        return match(true) {
            $this->severity >= 8 => "Jiddiy",
            $this->severity >= 5 => "O'rtacha",
            $this->severity >= 3 => "Yengil",
            default => "Past",
        };
    }

    /**
     * Tashvishli holatmi?
     */
    public function isConcerning(): bool
    {
        return $this->patternType !== self::PATTERN_IMPROVED
            && $this->severity !== null
            && $this->severity >= 5;
    }

    /**
     * Ijobiy o'zgarishmi?
     */
    public function isPositive(): bool
    {
        return $this->patternType === self::PATTERN_IMPROVED;
    }

    /**
     * Tavsiya qilinadigan harakatlar
     */
    public function getRecommendedActions(): array
    {
        if ($this->isPositive()) {
            return [
                'acknowledge' => "Yaxshilanishni e'tirof etish",
            ];
        }

        $actions = [];

        if ($this->severity >= 8) {
            $actions['immediate_meeting'] = "Zudlik bilan suhbat";
            $actions['hr_review'] = "HR ko'rib chiqishi";
        } elseif ($this->severity >= 5) {
            $actions['one_on_one'] = "1-on-1 suhbat";
            $actions['check_wellbeing'] = "Sog'lik holatini so'rash";
        } else {
            $actions['monitor'] = "Kuzatishda davom etish";
        }

        return $actions;
    }
}
