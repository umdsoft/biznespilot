<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCourseLesson extends Model
{
    use HasUuids;

    protected $table = 'store_course_lessons';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'duration_minutes',
        'video_url',
        'content',
        'is_free_preview',
        'sort_order',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_free_preview' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(StoreCourse::class, 'course_id');
    }
}
