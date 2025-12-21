<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramFlowEdge extends Model
{
    protected $fillable = [
        'automation_id',
        'edge_id',
        'source_node_id',
        'target_node_id',
        'source_handle',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(InstagramAutomation::class, 'automation_id');
    }

    public function isYesBranch(): bool
    {
        return $this->source_handle === 'yes';
    }

    public function isNoBranch(): bool
    {
        return $this->source_handle === 'no';
    }
}
