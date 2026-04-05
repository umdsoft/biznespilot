<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TeamAlert extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id', 'alert_type', 'severity', 'detecting_agent',
        'message', 'action_taken', 'resolved', 'resolved_at',
        'notified_owner', 'notified_at',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'notified_owner' => 'boolean',
        'notified_at' => 'datetime',
    ];
}
