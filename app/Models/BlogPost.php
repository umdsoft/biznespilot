<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasUuid;

    /**
     * Blog post categories
     */
    public const CATEGORIES = [
        'crm',
        'marketing',
        'smm',
        'finance',
        'hr',
        'ai',
        'business',
        'startup',
    ];

    /**
     * Supported locales
     */
    public const LOCALES = [
        'uz-latn',
        'ru',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'category',
        'locale',
        'meta_title',
        'meta_description',
        'tags',
        'is_published',
        'published_at',
        'views_count',
        'author_name',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the blog post URL.
     */
    public function getUrlAttribute(): string
    {
        return "/blog/{$this->slug}";
    }

    /**
     * Scope: only published posts (is_published=true and published_at <= now)
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: filter by locale
     */
    public function scopeLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope: filter by category
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
