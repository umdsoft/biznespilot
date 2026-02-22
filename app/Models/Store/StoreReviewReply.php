<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreReviewReply extends Model
{
    use HasUuids;

    protected $table = 'store_review_replies';

    protected $fillable = [
        'review_id', 'reply', 'replied_by',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(StoreReview::class, 'review_id');
    }
}
