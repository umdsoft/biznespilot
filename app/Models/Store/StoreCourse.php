<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreCourse extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $table = 'store_courses';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'what_you_learn',
        'requirements',
        'price',
        'compare_price',
        'image_url',
        'duration_hours',
        'level',
        'instructor',
        'instructor_photo',
        'max_students',
        'enrolled_count',
        'start_date',
        'end_date',
        'format',
        'certificate_included',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'duration_hours' => 'integer',
        'max_students' => 'integer',
        'enrolled_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'certificate_included' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class, 'category_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(StoreCourseLesson::class, 'course_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function hasDiscount(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getDiscountPercent(): int
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    public function hasAvailableSpots(): bool
    {
        if (is_null($this->max_students)) {
            return true;
        }

        return $this->enrolled_count < $this->max_students;
    }

    public function getCatalogName(): string
    {
        return $this->name;
    }

    public function getCatalogPrice(): float
    {
        return (float) $this->price;
    }

    public function getCatalogImage(): ?string
    {
        return $this->image_url;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->hasAvailableSpots();
    }

    public function getCatalogDescription(): ?string
    {
        return $this->description;
    }

    public function getCatalogAttributes(): array
    {
        return [
            'duration_hours' => $this->duration_hours,
            'level' => $this->level,
            'instructor' => $this->instructor,
            'max_students' => $this->max_students,
            'enrolled_count' => $this->enrolled_count,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'format' => $this->format,
            'certificate_included' => $this->certificate_included,
            'compare_price' => $this->compare_price,
            'has_discount' => $this->hasDiscount(),
            'discount_percent' => $this->getDiscountPercent(),
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'what_you_learn' => $this->what_you_learn,
            'price' => $this->price,
            'level' => $this->level,
            'instructor' => $this->instructor,
            'format' => $this->format,
            'category' => $this->category?->name,
        ];
    }

    public function getCatalogType(): string
    {
        return 'course';
    }
}
