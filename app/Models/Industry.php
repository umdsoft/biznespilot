<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'name_uz',
        'name_en',
        'slug',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Industry::class, 'parent_id')->orderBy('sort_order');
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helpers
    public function getName(string $locale = 'uz'): string
    {
        return $locale === 'en' ? $this->name_en : $this->name_uz;
    }

    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }
}
