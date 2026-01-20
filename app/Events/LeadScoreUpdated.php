<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadScoreUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public int $oldScore,
        public int $newScore,
        public ?string $oldCategory,
        public ?string $newCategory
    ) {}

    /**
     * Score o'sganmi
     */
    public function isScoreIncreased(): bool
    {
        return $this->newScore > $this->oldScore;
    }

    /**
     * Score tushganmi
     */
    public function isScoreDecreased(): bool
    {
        return $this->newScore < $this->oldScore;
    }

    /**
     * Kategoriya o'zgarganmi
     */
    public function isCategoryChanged(): bool
    {
        return $this->oldCategory !== $this->newCategory;
    }

    /**
     * O'zgarish miqdori
     */
    public function getChangeAmount(): int
    {
        return $this->newScore - $this->oldScore;
    }
}
