<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustdevSurvey extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'dream_buyer_id',
        'title',
        'description',
        'slug',
        'status',
        'theme_color',
        'logo_url',
        'welcome_message',
        'thank_you_message',
        'collect_contact',
        'anonymous',
        'estimated_time',
        'response_limit',
        'expires_at',
        'views_count',
        'responses_count',
        'completion_rate',
    ];

    protected $casts = [
        'collect_contact' => 'boolean',
        'anonymous' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($survey) {
            if (empty($survey->slug)) {
                $survey->slug = self::generateUniqueSlug();
            }
        });
    }

    /**
     * Generate unique slug for survey
     */
    public static function generateUniqueSlug(): string
    {
        do {
            $slug = Str::random(8);
        } while (self::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * Get the public URL for the survey
     */
    public function getPublicUrlAttribute(): string
    {
        return url("/s/{$this->slug}");
    }

    /**
     * Check if survey is active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->response_limit && $this->responses_count >= $this->response_limit) {
            return false;
        }

        return true;
    }

    /**
     * Get default questions for new survey (9 ta savol - "Sell Like Crazy" framework)
     */
    public static function getDefaultQuestions(): array
    {
        return [
            [
                'type' => 'multiselect',
                'category' => 'where_spend_time',
                'question' => 'Qaysi ijtimoiy tarmoqlardan ko\'proq foydalanasiz?',
                'description' => 'Bir nechta variantni tanlashingiz mumkin',
                'icon' => '📱',
                'required' => true,
                'is_default' => true,
                'options' => [
                    'Instagram',
                    'Facebook',
                    'Telegram',
                    'YouTube',
                    'TikTok',
                    'LinkedIn',
                    'Twitter/X',
                    'WhatsApp',
                    'Boshqa',
                ],
                'order' => 1,
            ],
            [
                'type' => 'multiselect',
                'category' => 'info_sources',
                'question' => 'Biror narsa sotib olishdan oldin qayerdan ma\'lumot qidirasiz?',
                'description' => 'Qaror qabul qilishdan oldin ishonchli manbalar',
                'icon' => '🔍',
                'required' => true,
                'is_default' => true,
                'options' => [
                    'Google qidiruv',
                    'YouTube videolar',
                    'Do\'stlar/tanishlar tavsiyasi',
                    'Telegram kanallar',
                    'Ekspert/mutaxassis maslahati',
                    'Sharhlar va reytinglar',
                    'Bloglar va maqolalar',
                    'Ijtimoiy tarmoqdagi reklamalar',
                    'Boshqa',
                ],
                'order' => 2,
            ],
            [
                'type' => 'textarea',
                'category' => 'frustrations',
                'question' => 'Hozirda sizni eng ko\'p nima bezovta qilmoqda yoki qiynayapti?',
                'description' => 'Kundalik hayotingiz yoki ishingizda duch kelayotgan qiyinchiliklar',
                'placeholder' => 'Masalan: vaqt yetishmasligi, natijalar ko\'rinmasligi, stress...',
                'icon' => '😤',
                'required' => true,
                'is_default' => true,
                'order' => 3,
            ],
            [
                'type' => 'textarea',
                'category' => 'dreams',
                'question' => 'Yaqin kelajakda (6-12 oy ichida) nimaga erishmoqchisiz?',
                'description' => 'Eng muhim maqsad va orzularingiz',
                'placeholder' => 'Masalan: daromadni oshirish, ko\'proq vaqt, yangi ko\'nikma...',
                'icon' => '✨',
                'required' => true,
                'is_default' => true,
                'order' => 4,
            ],
            [
                'type' => 'textarea',
                'category' => 'fears',
                'question' => 'Yangi mahsulot yoki xizmat sotib olishda sizni nima to\'xtatadi?',
                'description' => 'Qo\'rquvlar, shubhalar va e\'tirozlar',
                'placeholder' => 'Masalan: pulim behuda ketadi, ishlamasa nima qilaman...',
                'icon' => '😰',
                'required' => true,
                'is_default' => true,
                'order' => 5,
            ],
            [
                'type' => 'multiselect',
                'category' => 'communication_preferences',
                'question' => 'Qaysi aloqa usulini afzal ko\'rasiz?',
                'description' => 'Siz bilan qanday bog\'lanishimizni xohlaysiz',
                'icon' => '💬',
                'required' => true,
                'is_default' => true,
                'options' => [
                    'Telegram xabar',
                    'Telefon qo\'ng\'iroq',
                    'WhatsApp',
                    'Email',
                    'Video qo\'ng\'iroq (Zoom/Google Meet)',
                    'Yuzma-yuz uchrashuv',
                    'SMS',
                    'Instagram DM',
                ],
                'order' => 6,
            ],
            [
                'type' => 'textarea',
                'category' => 'daily_routine',
                'question' => 'Odatiy kuningiz qanday o\'tadi?',
                'description' => 'Ertalabdan kechgacha nima bilan band bo\'lasiz',
                'placeholder' => 'Masalan: ertalab ishga, tushlikda..., kechqurun...',
                'icon' => '🌅',
                'required' => true,
                'is_default' => true,
                'order' => 7,
            ],
            [
                'type' => 'textarea',
                'category' => 'happiness_triggers',
                'question' => 'Sizni nima baxtli qiladi?',
                'description' => 'Hayotingizda eng ko\'p quvonch keltiruvchi narsalar',
                'placeholder' => 'Masalan: oila bilan vaqt, muvaffaqiyat, sayohat...',
                'icon' => '😊',
                'required' => true,
                'is_default' => true,
                'order' => 8,
            ],
            [
                'type' => 'scale',
                'category' => 'satisfaction',
                'question' => 'Hozirgi vaziyatingizdan qanchalik qoniqasiz?',
                'description' => '1 - umuman qoniqmayman, 10 - to\'liq qoniqaman',
                'icon' => '📊',
                'required' => true,
                'is_default' => true,
                'settings' => ['min' => 1, 'max' => 10],
                'order' => 9,
            ],
        ];
    }

    // Relationships

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function dreamBuyer(): BelongsTo
    {
        return $this->belongsTo(DreamBuyer::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CustdevQuestion::class, 'survey_id')->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CustdevResponse::class, 'survey_id');
    }

    public function completedResponses(): HasMany
    {
        return $this->hasMany(CustdevResponse::class, 'survey_id')->where('status', 'completed');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(CustdevAnalytics::class, 'survey_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
