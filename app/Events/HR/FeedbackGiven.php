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
 * Feedback berilganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Qabul qiluvchiga xabar yuborish
 * - Feedback tarixini saqlash
 * - Engagement ball hisoblash
 * - 360° feedback aggregatsiya
 */
class FeedbackGiven implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Feedback turlari
    public const TYPE_PRAISE = 'praise';           // Maqtov
    public const TYPE_CONSTRUCTIVE = 'constructive'; // Konstruktiv
    public const TYPE_SUGGESTION = 'suggestion';   // Taklif
    public const TYPE_CONCERN = 'concern';         // Tashvish

    // Feedback yo'nalishlari
    public const DIRECTION_MANAGER_TO_EMPLOYEE = 'manager_to_employee';
    public const DIRECTION_EMPLOYEE_TO_MANAGER = 'employee_to_manager';
    public const DIRECTION_PEER_TO_PEER = 'peer_to_peer';

    public function __construct(
        public User $giver,
        public User $receiver,
        public Business $business,
        public string $type,
        public string $direction,
        public string $content,
        public ?string $category = null,
        public ?bool $isAnonymous = false,
        public ?string $context = null  // project, behavior, skill
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hr.' . $this->business->id),
            new PrivateChannel('user.' . $this->receiver->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.feedback-given';
    }

    public function broadcastWith(): array
    {
        return [
            'receiver_id' => $this->receiver->id,
            'receiver_name' => $this->receiver->name,
            'giver_name' => $this->isAnonymous ? 'Anonim' : $this->giver->name,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'direction' => $this->direction,
            'is_anonymous' => $this->isAnonymous,
            'is_positive' => $this->isPositive(),
        ];
    }

    /**
     * Feedback turini o'zbek tilida olish
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_PRAISE => "Maqtov",
            self::TYPE_CONSTRUCTIVE => "Konstruktiv tanqid",
            self::TYPE_SUGGESTION => "Taklif",
            self::TYPE_CONCERN => "Tashvish",
            default => "Boshqa",
        };
    }

    /**
     * Yo'nalishni o'zbek tilida olish
     */
    public function getDirectionLabel(): string
    {
        return match($this->direction) {
            self::DIRECTION_MANAGER_TO_EMPLOYEE => "Rahbardan hodimga",
            self::DIRECTION_EMPLOYEE_TO_MANAGER => "Hodimdan rahbarga",
            self::DIRECTION_PEER_TO_PEER => "Hamkasbdan hamkasbga",
            default => "Boshqa",
        };
    }

    /**
     * Ijobiy feedbackmi?
     */
    public function isPositive(): bool
    {
        return $this->type === self::TYPE_PRAISE;
    }

    /**
     * Konstruktiv feedbackmi?
     */
    public function isConstructive(): bool
    {
        return $this->type === self::TYPE_CONSTRUCTIVE;
    }

    /**
     * 360° feedback uchun mos yo'nalishmi?
     */
    public function isFor360(): bool
    {
        return in_array($this->direction, [
            self::DIRECTION_MANAGER_TO_EMPLOYEE,
            self::DIRECTION_EMPLOYEE_TO_MANAGER,
            self::DIRECTION_PEER_TO_PEER,
        ]);
    }
}
