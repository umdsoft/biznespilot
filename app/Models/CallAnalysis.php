<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallAnalysis extends Model
{
    use HasUuid;

    protected $table = 'call_analyses';

    protected $fillable = [
        // Bog'lanishlar
        'call_log_id', 'business_id', 'operator_id', 'lead_id', 'script_id',

        // Transcript
        'transcript', 'formatted_transcript',

        // Scoring
        'overall_score', 'stage_scores', 'anti_patterns',
        'recommendations', 'strengths', 'weaknesses',

        // Outcome va bashorat
        'outcome', 'predicted_outcome', 'win_probability',

        // Sentiment
        'sentiment_customer', 'sentiment_operator',

        // Talk ratios
        'talk_ratio_operator', 'operator_words', 'customer_words',

        // Script compliance
        'script_compliance_score', 'required_phrases_detected', 'forbidden_phrases_detected',

        // Emotional moments
        'emotional_moments',

        // Xarajatlar va metadata
        'stt_cost', 'analysis_cost', 'input_tokens', 'output_tokens',
        'stt_model', 'analysis_model', 'processing_time_ms', 'temp_audio_path',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'stage_scores' => 'array',
        'anti_patterns' => 'array',
        'recommendations' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',

        'win_probability' => 'decimal:2',
        'talk_ratio_operator' => 'decimal:2',
        'operator_words' => 'integer',
        'customer_words' => 'integer',
        'script_compliance_score' => 'decimal:2',
        'required_phrases_detected' => 'array',
        'forbidden_phrases_detected' => 'array',
        'emotional_moments' => 'array',

        'stt_cost' => 'decimal:6',
        'analysis_cost' => 'decimal:6',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'processing_time_ms' => 'integer',
    ];

    /**
     * Scoring stage weights (sum = 1.0)
     */
    public const STAGE_WEIGHTS = [
        'greeting' => 0.10,           // Salomlashish
        'discovery' => 0.20,          // Ehtiyoj aniqlash
        'presentation' => 0.20,       // Taqdimot
        'objection_handling' => 0.15, // E'tirozlar
        'closing' => 0.15,            // Yopish
        'rapport' => 0.10,            // Munosabat
        'cta' => 0.10,                // Keyingi qadam
    ];

    /**
     * Anti-pattern severities
     */
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_LOW = 'low';

    /**
     * Get the call log this analysis belongs to
     */
    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(SalesScript::class, 'script_id');
    }

    // Scoping helpers
    public function scopeForBusiness($query, string $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForOperator($query, string $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    public function scopeLostLeads($query)
    {
        return $query->where('outcome', 'lost');
    }

    public function scopeLowScore($query, float $threshold = 60)
    {
        return $query->where('overall_score', '<', $threshold);
    }

    /**
     * Get total cost (STT + Analysis)
     */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->stt_cost + (float) $this->analysis_cost;
    }

    /**
     * Get total cost in UZS (approximate)
     */
    public function getTotalCostUzsAttribute(): float
    {
        // 1 USD ≈ 12,800 UZS (approximate rate)
        return $this->total_cost * 12800;
    }

    /**
     * Get formatted total cost
     */
    public function getFormattedCostAttribute(): string
    {
        return number_format($this->total_cost_uzs, 0, '.', ' ') . ' so\'m';
    }

    /**
     * Get score color based on value
     */
    public function getScoreColorAttribute(): string
    {
        if ($this->overall_score === null) {
            return 'gray';
        }

        return match (true) {
            $this->overall_score >= 80 => 'green',
            $this->overall_score >= 60 => 'yellow',
            $this->overall_score >= 40 => 'orange',
            default => 'red',
        };
    }

    /**
     * Get score label
     */
    public function getScoreLabelAttribute(): string
    {
        if ($this->overall_score === null) {
            return 'Baholanmagan';
        }

        return match (true) {
            $this->overall_score >= 80 => 'A\'lo',
            $this->overall_score >= 60 => 'Yaxshi',
            $this->overall_score >= 40 => 'O\'rtacha',
            default => 'Yomon',
        };
    }

    /**
     * Get stage score with label
     */
    public function getStageWithLabel(string $stage): array
    {
        $labels = [
            'greeting' => 'Salomlashish',
            'discovery' => 'Ehtiyoj aniqlash',
            'presentation' => 'Taqdimot',
            'objection_handling' => 'E\'tirozlarni hal qilish',
            'closing' => 'Yopish',
            'rapport' => 'Munosabat qurish',
            'cta' => 'Keyingi qadam',
        ];

        $score = $this->stage_scores[$stage] ?? null;

        return [
            'key' => $stage,
            'label' => $labels[$stage] ?? $stage,
            'score' => $score,
            'weight' => self::STAGE_WEIGHTS[$stage] ?? 0,
            'color' => $this->getColorForScore($score),
        ];
    }

    /**
     * Get all stages with labels and scores
     */
    public function getStagesWithLabelsAttribute(): array
    {
        $stages = [];
        foreach (array_keys(self::STAGE_WEIGHTS) as $stage) {
            $stages[] = $this->getStageWithLabel($stage);
        }

        return $stages;
    }

    /**
     * Get color for a score value
     */
    protected function getColorForScore(?float $score): string
    {
        if ($score === null) {
            return 'gray';
        }

        return match (true) {
            $score >= 80 => 'green',
            $score >= 60 => 'yellow',
            $score >= 40 => 'orange',
            default => 'red',
        };
    }

    /**
     * Get critical anti-patterns
     */
    public function getCriticalPatternsAttribute(): array
    {
        return collect($this->anti_patterns ?? [])
            ->filter(fn($p) => ($p['severity'] ?? '') === self::SEVERITY_CRITICAL)
            ->values()
            ->toArray();
    }

    /**
     * Get high severity anti-patterns
     */
    public function getHighSeverityPatternsAttribute(): array
    {
        return collect($this->anti_patterns ?? [])
            ->filter(fn($p) => in_array($p['severity'] ?? '', [self::SEVERITY_CRITICAL, self::SEVERITY_HIGH]))
            ->values()
            ->toArray();
    }

    /**
     * Get anti-patterns count by severity
     */
    public function getPatternCountBySeverityAttribute(): array
    {
        $patterns = collect($this->anti_patterns ?? []);

        return [
            'critical' => $patterns->filter(fn($p) => ($p['severity'] ?? '') === self::SEVERITY_CRITICAL)->count(),
            'high' => $patterns->filter(fn($p) => ($p['severity'] ?? '') === self::SEVERITY_HIGH)->count(),
            'medium' => $patterns->filter(fn($p) => ($p['severity'] ?? '') === self::SEVERITY_MEDIUM)->count(),
            'low' => $patterns->filter(fn($p) => ($p['severity'] ?? '') === self::SEVERITY_LOW)->count(),
        ];
    }

    /**
     * Calculate weighted score from stage scores
     */
    public function calculateOverallScore(): float
    {
        if (empty($this->stage_scores)) {
            return 0;
        }

        $totalWeight = 0;
        $weightedSum = 0;

        foreach (self::STAGE_WEIGHTS as $stage => $weight) {
            if (isset($this->stage_scores[$stage])) {
                $weightedSum += $this->stage_scores[$stage] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;
    }

    /**
     * Get weakest stages (below 60)
     */
    public function getWeakStagesAttribute(): array
    {
        if (empty($this->stage_scores)) {
            return [];
        }

        return collect($this->stage_scores)
            ->filter(fn($score) => $score < 60)
            ->sortBy(fn($score) => $score)
            ->keys()
            ->toArray();
    }

    /**
     * Get strongest stages (80 and above)
     */
    public function getStrongStagesAttribute(): array
    {
        if (empty($this->stage_scores)) {
            return [];
        }

        return collect($this->stage_scores)
            ->filter(fn($score) => $score >= 80)
            ->sortByDesc(fn($score) => $score)
            ->keys()
            ->toArray();
    }
}
