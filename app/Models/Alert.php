<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alert extends Model
{
    use BelongsToBusiness, SoftDeletes;

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
        'message',
        'severity',
        'is_read',
        'is_dismissed',
        'action_url',
        'action_label',
        'metadata',
        'triggered_at',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'metadata' => 'array',
        'triggered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user who receives the alert.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
