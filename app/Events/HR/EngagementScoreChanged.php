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
 * Hodim engagement balli o'zgarganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Ball pastlasa - HR ga ogohlantirish
 * - Ball oshsa - rag'batlantirish
 * - Team engagement yangilash
 * - Retention tahlilini yangilash
 */
class EngagementScoreChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Engagement darajalari
    public const LEVEL_CRITICAL = 'critical';     // 0-30
    public const LEVEL_LOW = 'low';               // 31-50
    public const LEVEL_MODERATE = 'moderate';     // 51-70
    public const LEVEL_HIGH = 'high';             // 71-85
    public const LEVEL_EXCELLENT = 'excellent';   // 86-100

    public function __construct(
        public User $employee,
        public Business $business,
        public float $oldScore,
        public float $newScore,
        public ?string $source = null,  // survey, pulse, activity
        public ?array $scoreBreakdown = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hr.' . $this->business->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.engagement-score-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'old_score' => $this->oldScore,
            'new_score' => $this->newScore,
            'change' => $this->getScoreChange(),
            'level' => $this->getLevel(),
            'level_label' => $this->getLevelLabel(),
            'requires_attention' => $this->requiresAttention(),
        ];
    }

    /**
     * Ball o'zgarishini hisoblash
     */
    public function getScoreChange(): float
    {
        return round($this->newScore - $this->oldScore, 2);
    }

    /**
     * Joriy darajani aniqlash
     */
    public function getLevel(): string
    {
        return match(true) {
            $this->newScore <= 30 => self::LEVEL_CRITICAL,
            $this->newScore <= 50 => self::LEVEL_LOW,
            $this->newScore <= 70 => self::LEVEL_MODERATE,
            $this->newScore <= 85 => self::LEVEL_HIGH,
            default => self::LEVEL_EXCELLENT,
        };
    }

    /**
     * Darajani o'zbek tilida olish
     */
    public function getLevelLabel(): string
    {
        return match($this->getLevel()) {
            self::LEVEL_CRITICAL => "Jiddiy past",
            self::LEVEL_LOW => "Past",
            self::LEVEL_MODERATE => "O'rtacha",
            self::LEVEL_HIGH => "Yuqori",
            self::LEVEL_EXCELLENT => "A'lo",
        };
    }

    /**
     * E'tibor talab qiladimi?
     */
    public function requiresAttention(): bool
    {
        // Ball juda past yoki keskin tushgan bo'lsa
        return $this->newScore <= 50 || $this->getScoreChange() <= -15;
    }

    /**
     * Ball oshganmi?
     */
    public function isImprovement(): bool
    {
        return $this->newScore > $this->oldScore;
    }

    /**
     * Daraja o'zgarganmi?
     */
    public function isLevelChanged(): bool
    {
        $oldLevel = match(true) {
            $this->oldScore <= 30 => self::LEVEL_CRITICAL,
            $this->oldScore <= 50 => self::LEVEL_LOW,
            $this->oldScore <= 70 => self::LEVEL_MODERATE,
            $this->oldScore <= 85 => self::LEVEL_HIGH,
            default => self::LEVEL_EXCELLENT,
        };

        return $oldLevel !== $this->getLevel();
    }
}
