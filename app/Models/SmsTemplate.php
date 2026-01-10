<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTemplate extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    protected $fillable = [
        'business_id',
        'name',
        'content',
        'category',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get SMS messages that used this template
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'template_id');
    }

    /**
     * Get the business that owns this template
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope: Only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Replace placeholders with lead data
     *
     * Supported placeholders:
     * - {name} - Lead name
     * - {phone} - Lead phone
     * - {company} - Lead company
     * - {email} - Lead email
     */
    public function renderForLead(Lead $lead): string
    {
        $placeholders = [
            '{name}' => $lead->name ?? '',
            '{phone}' => $lead->phone ?? '',
            '{company}' => $lead->company ?? '',
            '{email}' => $lead->email ?? '',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $this->content);
    }

    /**
     * Increment the usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
