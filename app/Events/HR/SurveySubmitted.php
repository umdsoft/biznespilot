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
 * So'rovnoma javoblari yuborilganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Engagement ballini yangilash
 * - eNPS hisoblash
 * - Trend tahlilini yangilash
 * - Ogohlantirish (past ball bo'lsa)
 */
class SurveySubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // So'rovnoma turlari
    public const TYPE_Q12 = 'q12';                     // Gallup Q12
    public const TYPE_PULSE = 'pulse';                 // Haftalik pulse
    public const TYPE_ENPS = 'enps';                   // Employee NPS
    public const TYPE_ONBOARDING = 'onboarding';       // Onboarding feedback
    public const TYPE_EXIT = 'exit';                   // Exit interview
    public const TYPE_STAY = 'stay';                   // Stay interview
    public const TYPE_360 = '360';                     // 360° baholash
    public const TYPE_CUSTOM = 'custom';               // Maxsus so'rovnoma

    public function __construct(
        public User $employee,
        public Business $business,
        public string $surveyId,
        public string $surveyType,
        public ?string $surveyTitle = null,
        public ?float $overallScore = null,
        public ?array $responses = null,
        public ?bool $isAnonymous = false
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hr.' . $this->business->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.survey-submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'survey_id' => $this->surveyId,
            'survey_type' => $this->surveyType,
            'survey_type_label' => $this->getSurveyTypeLabel(),
            'overall_score' => $this->overallScore,
            'score_level' => $this->getScoreLevel(),
            'is_anonymous' => $this->isAnonymous,
            'requires_attention' => $this->requiresAttention(),
        ];
    }

    /**
     * So'rovnoma turini o'zbek tilida olish
     */
    public function getSurveyTypeLabel(): string
    {
        return match($this->surveyType) {
            self::TYPE_Q12 => "Gallup Q12",
            self::TYPE_PULSE => "Haftalik so'rovnoma",
            self::TYPE_ENPS => "Tavsiya darajasi (eNPS)",
            self::TYPE_ONBOARDING => "Onboarding baholash",
            self::TYPE_EXIT => "Ishdan ketish suhbati",
            self::TYPE_STAY => "Qolish suhbati",
            self::TYPE_360 => "360° baholash",
            self::TYPE_CUSTOM => $this->surveyTitle ?? "Maxsus so'rovnoma",
            default => "So'rovnoma",
        };
    }

    /**
     * Ball darajasini aniqlash
     */
    public function getScoreLevel(): string
    {
        if ($this->overallScore === null) {
            return 'unknown';
        }

        return match(true) {
            $this->overallScore >= 4.5 => 'excellent',
            $this->overallScore >= 3.5 => 'good',
            $this->overallScore >= 2.5 => 'average',
            $this->overallScore >= 1.5 => 'low',
            default => 'critical',
        };
    }

    /**
     * Ball darajasini o'zbek tilida olish
     */
    public function getScoreLevelLabel(): string
    {
        return match($this->getScoreLevel()) {
            'excellent' => "A'lo",
            'good' => "Yaxshi",
            'average' => "O'rtacha",
            'low' => "Past",
            'critical' => "Jiddiy past",
            default => "Noma'lum",
        };
    }

    /**
     * E'tibor talab qiladimi?
     */
    public function requiresAttention(): bool
    {
        return $this->overallScore !== null && $this->overallScore < 3.0;
    }

    /**
     * eNPS kategoriyasini aniqlash (0-10 shkala uchun)
     */
    public function getEnpsCategory(): ?string
    {
        if ($this->surveyType !== self::TYPE_ENPS || $this->overallScore === null) {
            return null;
        }

        return match(true) {
            $this->overallScore >= 9 => 'promoter',
            $this->overallScore >= 7 => 'passive',
            default => 'detractor',
        };
    }

    /**
     * eNPS kategoriyasini o'zbek tilida olish
     */
    public function getEnpsCategoryLabel(): ?string
    {
        return match($this->getEnpsCategory()) {
            'promoter' => "Tarafdor",
            'passive' => "Neytral",
            'detractor' => "Tanqidchi",
            default => null,
        };
    }
}
