<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramQuickReply extends Model
{
    use HasUuid;

    protected $fillable = [
        'account_id',
        'title',
        'content',
        'shortcut',
        'usage_count',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'account_id');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function scopeByShortcut($query, string $shortcut)
    {
        return $query->where('shortcut', $shortcut);
    }

    public function scopeMostUsed($query, int $limit = 10)
    {
        return $query->orderByDesc('usage_count')->limit($limit);
    }
}
