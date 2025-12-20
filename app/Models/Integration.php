<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integration extends Model
{
    use BelongsToBusiness, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'type',
        'name',
        'description',
        'is_active',
        'credentials',
        'config',
        'last_sync_at',
        'last_error_at',
        'last_error_message',
        'sync_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'credentials' => 'encrypted',
        'config' => 'array',
        'last_sync_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];
}
