<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustdevAnalytics extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'custdev_analytics';

    protected $fillable = [
        'survey_id',
        'question_id',
        'answer_value',
        'count',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    // Relationships

    public function survey(): BelongsTo
    {
        return $this->belongsTo(CustdevSurvey::class, 'survey_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CustdevQuestion::class, 'question_id');
    }
}
