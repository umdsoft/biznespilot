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
 * Hodim ish yilligi kelganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Tabriklov xabari yuborish
 * - Recognition yaratish
 * - Milestone badge berish
 * - Retention tracking yangilash
 */
class WorkAnniversary implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $employee,
        public Business $business,
        public int $years,
        public ?\DateTime $hireDate = null,
        public ?bool $isMilestone = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.' . $this->business->id),
            new PrivateChannel('hr.' . $this->business->id),
            new PrivateChannel('user.' . $this->employee->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.work-anniversary';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'years' => $this->years,
            'years_label' => $this->getYearsLabel(),
            'is_milestone' => $this->isMilestone(),
            'milestone_type' => $this->getMilestoneType(),
            'celebration_message' => $this->getCelebrationMessage(),
        ];
    }

    /**
     * Yillar labelini o'zbek tilida olish
     */
    public function getYearsLabel(): string
    {
        if ($this->years === 1) {
            return "1 yil";
        }
        return "{$this->years} yil";
    }

    /**
     * Milestone ekanligini aniqlash
     */
    public function isMilestone(): bool
    {
        if ($this->isMilestone !== null) {
            return $this->isMilestone;
        }

        // 1, 3, 5, 10, 15, 20, 25... yillar milestone
        return in_array($this->years, [1, 3, 5, 10, 15, 20, 25, 30, 35, 40])
            || ($this->years > 10 && $this->years % 5 === 0);
    }

    /**
     * Milestone turini aniqlash
     */
    public function getMilestoneType(): ?string
    {
        if (!$this->isMilestone()) {
            return null;
        }

        return match(true) {
            $this->years >= 20 => 'legendary',
            $this->years >= 10 => 'veteran',
            $this->years >= 5 => 'senior',
            $this->years >= 3 => 'established',
            $this->years >= 1 => 'first_year',
            default => null,
        };
    }

    /**
     * Milestone turini o'zbek tilida olish
     */
    public function getMilestoneTypeLabel(): ?string
    {
        return match($this->getMilestoneType()) {
            'legendary' => "Afsonavi xodim",
            'veteran' => "Veteran",
            'senior' => "Tajribali xodim",
            'established' => "O'z o'rnini topgan",
            'first_year' => "Birinchi yil",
            default => null,
        };
    }

    /**
     * Tabriklov xabarini olish
     */
    public function getCelebrationMessage(): string
    {
        $name = $this->employee->name;

        return match($this->getMilestoneType()) {
            'legendary' => "🏆 {$name} {$this->years} yillik tajribasi bilan afsonaga aylandi! Sizning sadoqatingiz bebaho!",
            'veteran' => "🌟 {$name} {$this->years} yillik xizmati bilan veteran darajasiga erishdi! Rahmat!",
            'senior' => "⭐ {$name} {$this->years} yillik tajribasi bilan jamoamizning ustuni!",
            'established' => "🎉 {$name} {$this->years} yillik yubileyingiz bilan tabriklaymiz!",
            'first_year' => "🎊 {$name} birinchi ish yilingiz bilan! Sizni jamoamizda ko'rganimizdan xursandmiz!",
            default => "🎂 {$name} {$this->years} yillik ish yubileyingiz bilan tabriklaymiz!",
        };
    }

    /**
     * Bonus koeffitsienti
     */
    public function getBonusMultiplier(): float
    {
        return match($this->getMilestoneType()) {
            'legendary' => 3.0,
            'veteran' => 2.0,
            'senior' => 1.5,
            'established' => 1.2,
            'first_year' => 1.0,
            default => 0,
        };
    }
}
