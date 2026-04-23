<?php

namespace App\Models\Partner;

use App\Models\Business;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerClick extends Model
{
    use HasUuids;

    public $timestamps = false; // only created_at

    protected $fillable = [
        'partner_id', 'code', 'ip', 'user_agent',
        'utm_source', 'utm_medium', 'utm_campaign',
        'landing_page', 'referrer', 'converted_business_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function convertedBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'converted_business_id');
    }
}
