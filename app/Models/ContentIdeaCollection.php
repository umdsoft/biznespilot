<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentIdeaCollection extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'icon',
        'color',
        'industry_id',
        'suitable_industries',
        'is_global',
        'is_featured',
        'ideas_count',
        'sort_order',
    ];

    protected $casts = [
        'suitable_industries' => 'array',
        'is_global' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function ideas(): BelongsToMany
    {
        return $this->belongsToMany(ContentIdea::class, 'content_idea_collection_items', 'collection_id', 'idea_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('content_idea_collection_items.sort_order');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Global to'plamlar
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope: Featured to'plamlar
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Muayyan biznes turi uchun
     */
    public function scopeForIndustry($query, $industryId)
    {
        return $query->where(function ($q) use ($industryId) {
            $q->where('industry_id', $industryId)
                ->orWhereJsonContains('suitable_industries', $industryId)
                ->orWhere('is_global', true);
        });
    }

    // ==================== METHODS ====================

    /**
     * G'oya qo'shish
     */
    public function addIdea(ContentIdea $idea, int $sortOrder = null): void
    {
        $this->ideas()->attach($idea->id, [
            'sort_order' => $sortOrder ?? $this->ideas()->count(),
        ]);

        $this->updateIdeasCount();
    }

    /**
     * G'oya olib tashlash
     */
    public function removeIdea(ContentIdea $idea): void
    {
        $this->ideas()->detach($idea->id);
        $this->updateIdeasCount();
    }

    /**
     * G'oyalar sonini yangilash
     */
    public function updateIdeasCount(): void
    {
        $this->update(['ideas_count' => $this->ideas()->count()]);
    }
}
