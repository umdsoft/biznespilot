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
 * Hodimga minnatdorchilik bildirilganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Recognition feedga qo'shish
 * - Engagement ball oshirish
 * - Achievement hisoblash
 * - Gamification ball berish
 */
class RecognitionGiven implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Recognition turlari
    public const TYPE_KUDOS = 'kudos';           // Oddiy rahmat
    public const TYPE_AWARD = 'award';           // Mukofot
    public const TYPE_SPOTLIGHT = 'spotlight';   // Alohida e'tirof
    public const TYPE_MILESTONE = 'milestone';   // Yutuq

    // Qiymatlar (kompaniya values)
    public const VALUE_TEAMWORK = 'teamwork';
    public const VALUE_INNOVATION = 'innovation';
    public const VALUE_CUSTOMER_FOCUS = 'customer_focus';
    public const VALUE_EXCELLENCE = 'excellence';
    public const VALUE_INTEGRITY = 'integrity';

    public function __construct(
        public User $giver,
        public User $receiver,
        public Business $business,
        public string $type,
        public string $message,
        public ?string $value = null,
        public ?int $points = null,
        public ?bool $isPublic = true
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('hr.' . $this->business->id),
            new PrivateChannel('user.' . $this->receiver->id),
        ];

        // Agar public bo'lsa - team kanaliga ham
        if ($this->isPublic) {
            $channels[] = new PrivateChannel('recognition.' . $this->business->id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'hr.recognition-given';
    }

    public function broadcastWith(): array
    {
        return [
            'receiver_id' => $this->receiver->id,
            'receiver_name' => $this->receiver->name,
            'giver_id' => $this->giver->id,
            'giver_name' => $this->giver->name,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'message' => $this->message,
            'value' => $this->value,
            'value_label' => $this->getValueLabel(),
            'points' => $this->points,
            'is_public' => $this->isPublic,
        ];
    }

    /**
     * Recognition turini o'zbek tilida olish
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_KUDOS => "Rahmat",
            self::TYPE_AWARD => "Mukofot",
            self::TYPE_SPOTLIGHT => "Oyning yulduzi",
            self::TYPE_MILESTONE => "Yutuq",
            default => "E'tirof",
        };
    }

    /**
     * Qiymatni o'zbek tilida olish
     */
    public function getValueLabel(): ?string
    {
        if (!$this->value) {
            return null;
        }

        return match($this->value) {
            self::VALUE_TEAMWORK => "Jamoaviylik",
            self::VALUE_INNOVATION => "Innovatsiya",
            self::VALUE_CUSTOMER_FOCUS => "Mijozga yo'nalganlik",
            self::VALUE_EXCELLENCE => "A'lochilik",
            self::VALUE_INTEGRITY => "Halollik",
            default => $this->value,
        };
    }

    /**
     * Ball olish kerakmi?
     */
    public function hasPoints(): bool
    {
        return $this->points !== null && $this->points > 0;
    }

    /**
     * Emoji olish
     */
    public function getEmoji(): string
    {
        return match($this->type) {
            self::TYPE_KUDOS => "👏",
            self::TYPE_AWARD => "🏆",
            self::TYPE_SPOTLIGHT => "⭐",
            self::TYPE_MILESTONE => "🎯",
            default => "🙌",
        };
    }
}
