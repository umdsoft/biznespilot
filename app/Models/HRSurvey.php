<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HRSurvey Model
 *
 * HR so'rovnomalari uchun model.
 * Engagement, Pulse, Exit survey va boshqalar.
 */
class HRSurvey extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'hr_surveys';

    // Survey types
    public const TYPE_ENGAGEMENT = 'engagement';
    public const TYPE_PULSE = 'pulse';
    public const TYPE_EXIT = 'exit';
    public const TYPE_ONBOARDING = 'onboarding';
    public const TYPE_360_FEEDBACK = '360_feedback';
    public const TYPE_CUSTOM = 'custom';

    // Survey statuses
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'business_id',
        'title',
        'description',
        'type',
        'status',
        'questions',
        'target_audience',
        'is_anonymous',
        'start_date',
        'end_date',
        'response_count',
        'created_by',
        'settings',
    ];

    protected $casts = [
        'questions' => 'array',
        'target_audience' => 'array',
        'is_anonymous' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Biznes
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Yaratuvchi
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Javoblar
     */
    public function responses(): HasMany
    {
        return $this->hasMany(HRSurveyResponse::class, 'survey_id');
    }

    /**
     * Survey faolmi?
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Response rate hisoblash
     */
    public function getResponseRate(): float
    {
        $targetCount = $this->getTargetAudienceCount();
        if ($targetCount === 0) {
            return 0;
        }

        return round(($this->response_count / $targetCount) * 100, 1);
    }

    /**
     * Target audience soni
     */
    public function getTargetAudienceCount(): int
    {
        $audience = $this->target_audience ?? ['type' => 'all'];

        return match ($audience['type'] ?? 'all') {
            'all' => BusinessUser::where('business_id', $this->business_id)
                ->whereNotNull('accepted_at')
                ->count(),
            'department' => BusinessUser::where('business_id', $this->business_id)
                ->whereNotNull('accepted_at')
                ->whereIn('department', $audience['departments'] ?? [])
                ->count(),
            'users' => count($audience['user_ids'] ?? []),
            default => 0,
        };
    }

    /**
     * Survey natijalarini hisoblash
     */
    public function calculateResults(): array
    {
        $responses = $this->responses()->get();

        if ($responses->isEmpty()) {
            return [];
        }

        $questions = $this->questions ?? [];
        $results = [];

        foreach ($questions as $index => $question) {
            $questionKey = "q_{$index}";
            $answers = $responses->pluck("answers.{$questionKey}")->filter();

            $results[$questionKey] = [
                'question' => $question['text'] ?? '',
                'type' => $question['type'] ?? 'text',
                'total_responses' => $answers->count(),
            ];

            switch ($question['type'] ?? 'text') {
                case 'scale':
                case 'rating':
                    $numericAnswers = $answers->map(fn($a) => (float) $a);
                    $results[$questionKey]['avg_score'] = round($numericAnswers->avg(), 2);
                    $results[$questionKey]['min'] = $numericAnswers->min();
                    $results[$questionKey]['max'] = $numericAnswers->max();
                    $results[$questionKey]['distribution'] = $numericAnswers->countBy()->sortKeys()->toArray();
                    break;

                case 'choice':
                case 'multiple_choice':
                    $results[$questionKey]['distribution'] = $answers->flatten()->countBy()->toArray();
                    break;

                case 'yes_no':
                    $results[$questionKey]['yes_count'] = $answers->filter(fn($a) => $a === 'yes' || $a === true)->count();
                    $results[$questionKey]['no_count'] = $answers->filter(fn($a) => $a === 'no' || $a === false)->count();
                    break;

                case 'text':
                default:
                    $results[$questionKey]['responses'] = $answers->take(50)->toArray();
                    break;
            }
        }

        return $results;
    }

    /**
     * Gallup Q12 survey yaratish
     */
    public static function createQ12Survey(string $businessId, string $createdBy): self
    {
        $questions = [
            ['text' => 'Men ishda nima qilishim kerakligini aniq bilaman', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => 'Menga ishni yaxshi bajarish uchun kerakli materiallar va jihozlar berilgan', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => 'Ishda har kuni eng yaxshi qila oladigan ishimni qilish imkoniyatim bor', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "So'nggi yetti kunda yaxshi ish uchun tan olindim yoki maqtaldim", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "Rahbarim yoki ishda kimdir men haqimda g'amxo'rlik qiladi", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => 'Ishda kimdir mening rivojlanishimni qo\'llab-quvvatlaydi', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => 'Ishda fikrim inobatga olinadi', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => 'Kompaniya missiyasi/maqsadi mening ishim muhimligini his qildiradi', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => 'Hamkasblarim sifatli ish qilishga intiladi', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => 'Ishda eng yaqin do\'stim bor', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "So'nggi olti oyda kimdir men bilan rivojlanishim haqida gaplashdi", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "So'nggi bir yilda o'rganish va o'sish imkoniyatlarim bo'ldi", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
        ];

        return self::create([
            'business_id' => $businessId,
            'title' => 'Gallup Q12 Engagement Survey',
            'description' => "Hodimlarning ishga qiziqishini o'lchash uchun standart so'rovnoma",
            'type' => self::TYPE_ENGAGEMENT,
            'status' => self::STATUS_DRAFT,
            'questions' => $questions,
            'target_audience' => ['type' => 'all'],
            'is_anonymous' => true,
            'created_by' => $createdBy,
            'settings' => [
                'allow_skip' => false,
                'show_progress' => true,
                'randomize_questions' => false,
            ],
        ]);
    }

    /**
     * Pulse survey yaratish
     */
    public static function createPulseSurvey(string $businessId, string $createdBy): self
    {
        $questions = [
            ['text' => "Bu haftada o'zingizni qanchalik motivatsiyali his qildingiz?", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "Ishda qanchalik stress his qildingiz?", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "Jamoa bilan hamkorlik qanchalik yaxshi bo'ldi?", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "Qo'shimcha fikr-mulohazalaringiz bormi?", 'type' => 'text'],
        ];

        return self::create([
            'business_id' => $businessId,
            'title' => "Haftalik Pulse So'rovnoma",
            'description' => "Tezkor kayfiyat va holat tekshiruvi",
            'type' => self::TYPE_PULSE,
            'status' => self::STATUS_DRAFT,
            'questions' => $questions,
            'target_audience' => ['type' => 'all'],
            'is_anonymous' => true,
            'created_by' => $createdBy,
            'settings' => [
                'allow_skip' => true,
                'show_progress' => true,
            ],
        ]);
    }
}
