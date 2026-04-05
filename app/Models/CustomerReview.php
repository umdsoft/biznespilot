<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class CustomerReview extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id', 'source', 'source_id', 'reviewer_name', 'rating',
        'review_text', 'language', 'sentiment', 'sentiment_score', 'categories',
        'response_text', 'response_status', 'suggested_response', 'flagged', 'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'sentiment_score' => 'decimal:2',
        'categories' => 'array',
        'flagged' => 'boolean',
        'reviewed_at' => 'datetime',
    ];
}
