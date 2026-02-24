<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full course resource with lessons, instructor, and enrollment info.
 */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'what_you_learn' => $this->what_you_learn,
            'requirements' => $this->requirements,
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'discount_percent' => $this->getDiscountPercent(),
            'has_discount' => $this->hasDiscount(),
            'image' => $this->image_url,
            'duration_hours' => $this->duration_hours,
            'level' => $this->level,
            'instructor' => $this->instructor,
            'instructor_photo' => $this->instructor_photo,
            'max_students' => $this->max_students,
            'enrolled_count' => $this->enrolled_count,
            'has_available_spots' => $this->hasAvailableSpots(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'format' => $this->format,
            'certificate_included' => $this->certificate_included,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,

            // Category
            'category' => $this->when(
                $this->relationLoaded('category'),
                fn () => $this->category ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ] : null
            ),

            // Lessons
            'lessons' => $this->when(
                $this->relationLoaded('lessons'),
                fn () => $this->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'duration_minutes' => $lesson->duration_minutes,
                    'is_free_preview' => $lesson->is_free_preview,
                    'sort_order' => $lesson->sort_order,
                ])
            ),
            'lessons_count' => $this->when(
                $this->relationLoaded('lessons'),
                fn () => $this->lessons->count()
            ),

            'catalog_type' => 'course',
            'attributes' => $this->getCatalogAttributes(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
