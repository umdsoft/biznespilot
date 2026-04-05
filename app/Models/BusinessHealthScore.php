<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Biznes sog'ligi ball modeli.
 */
class BusinessHealthScore extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id', 'period_start', 'period_end',
        'overall_score', 'marketing_score', 'marketing_details',
        'sales_score', 'sales_details', 'finance_score', 'finance_details',
        'customer_score', 'customer_details',
        'previous_overall_score', 'change_from_previous',
        'top_issues', 'recommendations', 'ai_tokens_used',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'overall_score' => 'integer',
        'marketing_score' => 'integer',
        'marketing_details' => 'array',
        'sales_score' => 'integer',
        'sales_details' => 'array',
        'finance_score' => 'integer',
        'finance_details' => 'array',
        'customer_score' => 'integer',
        'customer_details' => 'array',
        'previous_overall_score' => 'integer',
        'change_from_previous' => 'integer',
        'top_issues' => 'array',
        'recommendations' => 'array',
        'ai_tokens_used' => 'integer',
    ];
}
