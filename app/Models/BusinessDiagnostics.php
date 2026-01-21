<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Business Diagnostics - 7 questions from the book
 * Helps identify business evolution level and improvement areas
 */
class BusinessDiagnostics extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $table = 'business_diagnostics';

    protected $fillable = [
        'business_id',
        'assessed_by',
        'assessment_date',
        // 7 Questions from book
        'q1_owner_role',        // tired_employee | leader
        'q2_main_competency',   // product | sales | management
        'q3_company_type',      // family_business | ptu | goal_instrument
        'q4_management_style',  // plan_control | problem_solving
        'q5_tops_role',         // leaders | secretaries
        'q6_motivation_exists', // salary_only | salary_plus_bonus
        'q7_motivation_type',   // individual | team
        // Results
        'evolution_level',      // 1-5
        'recommendations',
        'action_plan',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'action_plan' => 'array',
    ];

    // Relationships
    public function assessedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    // Calculate evolution level based on answers
    public function calculateEvolutionLevel(): int
    {
        $score = 0;

        // Q1: Owner role
        if ($this->q1_owner_role === 'leader') $score += 1;

        // Q2: Main competency
        if ($this->q2_main_competency === 'management') $score += 1;

        // Q3: Company type
        if ($this->q3_company_type === 'goal_instrument') $score += 1;

        // Q4: Management style
        if ($this->q4_management_style === 'plan_control') $score += 1;

        // Q5: TOPs role
        if ($this->q5_tops_role === 'leaders') $score += 1;

        // Q6: Motivation exists
        if ($this->q6_motivation_exists === 'salary_plus_bonus') $score += 1;

        // Q7: Motivation type
        if ($this->q7_motivation_type === 'team') $score += 1;

        // Map score to evolution level (1-5)
        return match(true) {
            $score >= 7 => 5,
            $score >= 5 => 4,
            $score >= 3 => 3,
            $score >= 1 => 2,
            default => 1,
        };
    }

    // Generate recommendations based on answers
    public function generateRecommendations(): string
    {
        $recommendations = [];

        if ($this->q1_owner_role === 'tired_employee') {
            $recommendations[] = "Egasi charchagan xodim holatida. Liderlik ko'nikmalarini rivojlantirish kerak.";
        }

        if ($this->q2_main_competency !== 'management') {
            $recommendations[] = "Boshqaruv kompetensiyasini rivojlantirish zarur. Hozircha faqat {$this->q2_main_competency} bo'yicha kuchli.";
        }

        if ($this->q3_company_type === 'family_business') {
            $recommendations[] = "Oilaviy biznesdan professional tuzilmaga o'tish kerak.";
        } elseif ($this->q3_company_type === 'ptu') {
            $recommendations[] = "Kompaniya 'o'quv markazi' bo'lib qolgan - xodimlar o'rganib ketishmoqda. Professional jamoani shakllantirish kerak.";
        }

        if ($this->q4_management_style === 'problem_solving') {
            $recommendations[] = "Rejalashtirish va nazorat tizimini joriy qilish kerak. Hozir faqat muammolarni hal qilish bilan shug'ullanilmoqda.";
        }

        if ($this->q5_tops_role === 'secretaries') {
            $recommendations[] = "TOP menejerlar sekretar vazifasini bajarmoqda. Ularga mustaqil qaror qilish huquqini berish kerak.";
        }

        if ($this->q6_motivation_exists === 'salary_only') {
            $recommendations[] = "Motivatsiya tizimini joriy qilish kerak: Oklad + Bonus.";
        }

        if ($this->q7_motivation_type === 'individual') {
            $recommendations[] = "Individual motivatsiyadan jamoaviyga o'tish kerak.";
        }

        return implode("\n\n", $recommendations);
    }

    // Get evolution level label
    public function getEvolutionLevelLabelAttribute(): string
    {
        return match($this->evolution_level) {
            1 => '1-daraja: Tug\'ilish (Remeslo)',
            2 => '2-daraja: O\'sish (Trafik, Sotuvlar)',
            3 => '3-daraja: Sistemali biznes',
            4 => '4-daraja: Operatsiyadan chiqish',
            5 => '5-daraja: Investor',
            default => 'Noma\'lum',
        };
    }

    // Get question labels (for display)
    public static function getQuestionLabels(): array
    {
        return [
            'q1_owner_role' => [
                'question' => 'Egasi biznesda kim?',
                'options' => [
                    'tired_employee' => 'Charchagan xodim, mo\'jiza kutayotgan',
                    'leader' => 'Lider, kompaniyani maqsadga olib boruvchi',
                ],
            ],
            'q2_main_competency' => [
                'question' => 'Asosiy kompetensiya qaysi?',
                'options' => [
                    'product' => 'Mahsulot yaratish',
                    'sales' => 'Sotish va targ\'ibot',
                    'management' => 'Boshqaruv',
                ],
            ],
            'q3_company_type' => [
                'question' => 'Kompaniya turi qanday?',
                'options' => [
                    'family_business' => 'Oilaviy biznes',
                    'ptu' => 'PTU (xodimlar o\'rganib ketadi)',
                    'goal_instrument' => 'Maqsadga erishish vositasi',
                ],
            ],
            'q4_management_style' => [
                'question' => 'Boshqaruv uslubi qanday?',
                'options' => [
                    'plan_control' => 'Rejalashtirish, nazorat, og\'ishlar bilan ishlash',
                    'problem_solving' => 'Kunlik muammolarni hal qilish',
                ],
            ],
            'q5_tops_role' => [
                'question' => 'TOPlar tizimda kim?',
                'options' => [
                    'leaders' => 'Rahbar - rejalashtira oladigan va qaror qabul qiladigan',
                    'secretaries' => 'Sekretar - bossning to\'g\'ridan-to\'g\'ri topshiriqlarini bajaruvchi',
                ],
            ],
            'q6_motivation_exists' => [
                'question' => 'Kompaniyada motivatsiya bormi?',
                'options' => [
                    'salary_only' => 'Xodimlar faqat oklad oladi, natijaga bog\'lanmagan',
                    'salary_plus_bonus' => 'Xodimlar ishlaydi, natija uchun oladi (oklad + bonus)',
                ],
            ],
            'q7_motivation_type' => [
                'question' => 'Motivatsiya turi qanday?',
                'options' => [
                    'individual' => 'Individual - bonus shaxsiy ko\'rsatkichlarga bog\'liq',
                    'team' => 'Jamoaviy - bonus bo\'lim (kompaniya) natijasiga bog\'liq',
                ],
            ],
        ];
    }

    // Scopes
    public function scopeLatest($query)
    {
        return $query->orderByDesc('assessment_date');
    }

    public function scopeForAssessor($query, string $userId)
    {
        return $query->where('assessed_by', $userId);
    }
}
