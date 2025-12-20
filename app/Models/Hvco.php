<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hvco extends Model
{
    use BelongsToBusiness, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'content_type',
        'file_path',
        'thumbnail',
        'downloads_count',
        'views_count',
        'leads_generated',
        'requires_email',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requires_email' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
