<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentPoolNote extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'talent_pool_candidate_id', 'business_id', 'user_id',
        'content', 'type', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(TalentPoolCandidate::class, 'talent_pool_candidate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
