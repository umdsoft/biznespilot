<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeadQualificationChanged - Lead qualification o'zgarganda
 * MQL, SQL, Disqualified o'tishlarini kuzatish uchun
 */
class LeadQualificationChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Lead $lead,
        public string $fromStatus,
        public string $toStatus,
        public ?User $qualifiedBy = null
    ) {}

    /**
     * Check if this is a promotion (new -> mql -> sql).
     */
    public function isPromotion(): bool
    {
        $order = ['new' => 1, 'mql' => 2, 'sql' => 3, 'disqualified' => 0];

        return ($order[$this->toStatus] ?? 0) > ($order[$this->fromStatus] ?? 0);
    }

    /**
     * Check if this is a demotion/disqualification.
     */
    public function isDemotion(): bool
    {
        return $this->toStatus === 'disqualified' && $this->fromStatus !== 'disqualified';
    }

    /**
     * Check if became MQL.
     */
    public function becameMql(): bool
    {
        return $this->toStatus === 'mql' && $this->fromStatus !== 'mql';
    }

    /**
     * Check if became SQL.
     */
    public function becameSql(): bool
    {
        return $this->toStatus === 'sql' && $this->fromStatus !== 'sql';
    }

    /**
     * Get change description.
     */
    public function getDescription(): string
    {
        $labels = [
            'new' => 'Yangi',
            'mql' => 'MQL',
            'sql' => 'SQL',
            'disqualified' => 'Rad etildi',
        ];

        $from = $labels[$this->fromStatus] ?? $this->fromStatus;
        $to = $labels[$this->toStatus] ?? $this->toStatus;

        return "Lead qualification: {$from} → {$to}";
    }
}
