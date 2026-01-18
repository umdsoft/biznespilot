<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeadForm extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'business_id',
        'default_source_id',
        'name',
        'title',
        'description',
        'slug',
        'fields',
        'submit_button_text',
        'theme_color',
        'lead_magnet_type',
        'lead_magnet_title',
        'lead_magnet_file',
        'lead_magnet_link',
        'lead_magnet_text',
        'success_message',
        'redirect_url',
        'show_lead_magnet_on_success',
        'default_status',
        'default_score',
        'is_active',
        'views_count',
        'submissions_count',
        'track_utm',
    ];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean',
        'show_lead_magnet_on_success' => 'boolean',
        'track_utm' => 'boolean',
        'views_count' => 'integer',
        'submissions_count' => 'integer',
        'default_score' => 'integer',
    ];

    protected $attributes = [
        'submit_button_text' => 'Yuborish',
        'theme_color' => '#10b981',
        'lead_magnet_type' => 'none',
        'success_message' => 'Rahmat! Ma\'lumotlaringiz qabul qilindi.',
        'default_status' => 'new',
        'default_score' => 50,
        'is_active' => true,
        'track_utm' => true,
    ];

    /**
     * Default form fields configuration
     */
    public static function getDefaultFields(): array
    {
        return [
            [
                'id' => 'name',
                'type' => 'text',
                'label' => 'Ismingiz',
                'placeholder' => 'Ismingizni kiriting',
                'required' => true,
                'map_to' => 'name', // Maps to Lead model field
            ],
            [
                'id' => 'phone',
                'type' => 'phone',
                'label' => 'Telefon raqamingiz',
                'placeholder' => '+998 90 123 45 67',
                'required' => true,
                'map_to' => 'phone',
            ],
        ];
    }

    /**
     * Available field types for form builder
     */
    public static function getFieldTypes(): array
    {
        return [
            'text' => [
                'label' => 'Matn',
                'icon' => 'type',
                'maps_to' => ['name', 'company', 'position', 'custom'],
            ],
            'email' => [
                'label' => 'Email',
                'icon' => 'mail',
                'maps_to' => ['email'],
            ],
            'phone' => [
                'label' => 'Telefon',
                'icon' => 'phone',
                'maps_to' => ['phone'],
            ],
            'textarea' => [
                'label' => 'Ko\'p qatorli matn',
                'icon' => 'align-left',
                'maps_to' => ['notes', 'custom'],
            ],
            'select' => [
                'label' => 'Tanlash',
                'icon' => 'list',
                'maps_to' => ['custom'],
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'icon' => 'check-square',
                'maps_to' => ['custom'],
            ],
            'number' => [
                'label' => 'Raqam',
                'icon' => 'hash',
                'maps_to' => ['estimated_value', 'custom'],
            ],
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->name ?? $model->title);
            }
            if (empty($model->fields)) {
                $model->fields = static::getDefaultFields();
            }
        });
    }

    /**
     * Generate unique slug
     */
    public static function generateUniqueSlug(string $base): string
    {
        $slug = Str::slug($base).'-'.Str::random(6);

        while (static::where('slug', $slug)->exists()) {
            $slug = Str::slug($base).'-'.Str::random(6);
        }

        return $slug;
    }

    /**
     * Get public URL
     */
    public function getPublicUrlAttribute(): string
    {
        return url("/f/{$this->slug}");
    }

    /**
     * Get conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->views_count === 0) {
            return 0;
        }

        return round(($this->submissions_count / $this->views_count) * 100, 1);
    }

    /**
     * Check if form has lead magnet
     */
    public function hasLeadMagnet(): bool
    {
        return $this->lead_magnet_type !== 'none';
    }

    /**
     * Increment view count
     */
    public function recordView(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment submission count
     */
    public function recordSubmission(): void
    {
        $this->increment('submissions_count');
    }

    // Relationships

    public function defaultSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'default_source_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LeadFormSubmission::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'id', 'lead_id')
            ->through('submissions');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithLeadMagnet($query)
    {
        return $query->where('lead_magnet_type', '!=', 'none');
    }
}
