<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class MarketResearch extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'market_research';

    protected $fillable = [
        'business_id',
        'research_type',
        'title',
        'methodology',
        'sample_size',
        'findings_summary',
        'key_insights',
        'data_sources',
        'conducted_at',
        'valid_until',
        'attachments',
    ];

    protected $casts = [
        'key_insights' => 'array',
        'data_sources' => 'array',
        'attachments' => 'array',
        'conducted_at' => 'date',
        'valid_until' => 'date',
    ];

    // Constants
    public const RESEARCH_TYPES = [
        'market_size' => 'Bozor hajmi',
        'competitor' => 'Raqobatchi tahlili',
        'customer' => 'Mijoz tadqiqoti',
        'industry_trends' => 'Soha trendlari',
    ];

    public const METHODOLOGIES = [
        'survey' => 'So\'rovnoma',
        'interview' => 'Intervyu',
        'secondary' => 'Ikkilamchi ma\'lumotlar',
        'observation' => 'Kuzatish',
    ];

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('research_type', $type);
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', now());
        });
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('conducted_at', 'desc');
    }

    // Helpers
    public function getTypeLabel(): string
    {
        return self::RESEARCH_TYPES[$this->research_type] ?? $this->research_type;
    }

    public function getMethodologyLabel(): ?string
    {
        if (! $this->methodology) {
            return null;
        }

        return self::METHODOLOGIES[$this->methodology] ?? $this->methodology;
    }

    public function isValid(): bool
    {
        if (! $this->valid_until) {
            return true;
        }

        return $this->valid_until->isFuture();
    }

    public function isExpired(): bool
    {
        return ! $this->isValid();
    }
}
