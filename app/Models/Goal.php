<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
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
        'name',
        'description',
        'target_value',
        'current_value',
        'unit',
        'start_date',
        'deadline',
        'status',
        'priority',
        'milestones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'start_date' => 'date',
        'deadline' => 'date',
        'milestones' => 'array',
    ];

    /**
     * Get the user who owns the goal.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
