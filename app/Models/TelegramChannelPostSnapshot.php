<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TelegramChannelPostSnapshot — post views/reactions snapshot at a given time.
 *
 * SyncTelegramChannelStatsJob har safar post'ni yangilaganda shu jadvalga
 * yozuv qoldiradi. Bu bizga "oxirgi 24 soat ichida post qancha ko'rilgan"
 * degan savolga javob beradi (views kumulyativ, delta hisoblash kerak).
 */
class TelegramChannelPostSnapshot extends Model
{
    use HasUuid;

    public $timestamps = true;

    protected $fillable = [
        'telegram_channel_post_id',
        'snapshot_at',
        'views',
        'reactions_count',
        'forwards_count',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'views' => 'integer',
        'reactions_count' => 'integer',
        'forwards_count' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(TelegramChannelPost::class, 'telegram_channel_post_id');
    }
}
