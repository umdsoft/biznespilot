<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HRSurveyResponse Model
 *
 * HR so'rovnoma javoblari uchun model.
 */
class HRSurveyResponse extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    protected $table = 'hr_survey_responses';

    protected $fillable = [
        'business_id',
        'survey_id',
        'user_id',
        'answers',
        'completed_at',
        'time_spent_seconds',
        'is_complete',
        'metadata',
    ];

    protected $casts = [
        'answers' => 'array',
        'completed_at' => 'datetime',
        'is_complete' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Survey
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(HRSurvey::class, 'survey_id');
    }

    /**
     * Respondent (agar anonim bo'lmasa)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Javobni to'ldirish
     */
    public function submitAnswer(string $questionKey, mixed $answer): void
    {
        $answers = $this->answers ?? [];
        $answers[$questionKey] = $answer;
        $this->update(['answers' => $answers]);
    }

    /**
     * Survey ni yakunlash
     */
    public function complete(): void
    {
        $this->update([
            'is_complete' => true,
            'completed_at' => now(),
        ]);

        // Survey response count ni yangilash
        $this->survey->increment('response_count');
    }

    /**
     * O'rtacha ball hisoblash (scale/rating savollar uchun)
     */
    public function getAverageScore(): float
    {
        $answers = $this->answers ?? [];
        $survey = $this->survey;
        $questions = $survey->questions ?? [];

        $scores = [];

        foreach ($questions as $index => $question) {
            if (in_array($question['type'] ?? '', ['scale', 'rating'])) {
                $key = "q_{$index}";
                if (isset($answers[$key]) && is_numeric($answers[$key])) {
                    $scores[] = (float) $answers[$key];
                }
            }
        }

        if (empty($scores)) {
            return 0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Response vaqtini formatlash
     */
    public function getFormattedTimeSpent(): string
    {
        $seconds = $this->time_spent_seconds ?? 0;

        if ($seconds < 60) {
            return "{$seconds} soniya";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes} daqiqa {$remainingSeconds} soniya";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours} soat {$remainingMinutes} daqiqa";
    }
}
