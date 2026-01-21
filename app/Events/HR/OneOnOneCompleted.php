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
 * 1-on-1 uchrashuv yakunlanganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Keyingi uchrashuvni rejalashtirish
 * - Action itemlarni vazifa sifatida yaratish
 * - Manager-hodim munosabatlarini tracking
 * - Engagement ball hisoblash
 */
class OneOnOneCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $employee,
        public User $manager,
        public Business $business,
        public string $meetingId,
        public ?int $duration = null,  // Daqiqalarda
        public ?array $actionItems = null,
        public ?int $sentiment = null,  // 1-5
        public ?array $topicsDiscussed = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hr.' . $this->business->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.one-on-one-completed';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'manager_id' => $this->manager->id,
            'manager_name' => $this->manager->name,
            'meeting_id' => $this->meetingId,
            'duration' => $this->duration,
            'action_items_count' => count($this->actionItems ?? []),
            'sentiment' => $this->sentiment,
            'sentiment_label' => $this->getSentimentLabel(),
        ];
    }

    /**
     * Kayfiyat labelini o'zbek tilida olish
     */
    public function getSentimentLabel(): string
    {
        return match($this->sentiment) {
            1 => "Juda yomon",
            2 => "Yomon",
            3 => "O'rtacha",
            4 => "Yaxshi",
            5 => "A'lo",
            default => "Baholanmagan",
        };
    }

    /**
     * Ijobiy uchrashuvmi?
     */
    public function isPositive(): bool
    {
        return $this->sentiment !== null && $this->sentiment >= 4;
    }

    /**
     * Salbiy uchrashuvmi?
     */
    public function isNegative(): bool
    {
        return $this->sentiment !== null && $this->sentiment <= 2;
    }

    /**
     * Action itemlar bormi?
     */
    public function hasActionItems(): bool
    {
        return !empty($this->actionItems);
    }

    /**
     * Muhokama qilingan mavzularni olish
     */
    public function getTopics(): array
    {
        return $this->topicsDiscussed ?? [];
    }
}
