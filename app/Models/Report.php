<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'title',
        'description',
        'data',
        'filters',
        'period_start',
        'period_end',
        'file_path',
        'generated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'filters' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the user who generated the report.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
