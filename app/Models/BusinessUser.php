<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BusinessUser extends Pivot
{
    use HasUuid;

    protected $table = 'business_user';

    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'permissions',
        'invited_at',
        'accepted_at',
        'joined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'joined_at' => 'datetime',
    ];
}
