<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustdevAnswer extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'response_id',
        'question_id',
        'answer',
        'selected_options',
        'rating_value',
        'time_spent',
    ];

    protected $casts = [
        'selected_options' => 'array',
    ];

    /**
     * Get display value for the answer
     */
    public function getDisplayValue(): string
    {
        if ($this->selected_options) {
            return implode(', ', $this->selected_options);
        }

        if ($this->rating_value !== null) {
            return (string) $this->rating_value;
        }

        return $this->answer ?? '';
    }

    // Relationships

    public function response(): BelongsTo
    {
        return $this->belongsTo(CustdevResponse::class, 'response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CustdevQuestion::class, 'question_id');
    }
}
