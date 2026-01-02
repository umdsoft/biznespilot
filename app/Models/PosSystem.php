<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSystem extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'pos_systems';

    protected $fillable = [
        'business_id',
        'integration_id',
        'system_name',
        'system_type',
        'api_key',
        'api_secret',
        'api_endpoint',
        'location_id',
        'store_id',
        'total_sales',
        'total_transactions',
        'total_revenue',
        'average_transaction_value',
        'daily_sales',
        'weekly_sales',
        'monthly_sales',
        'is_active',
        'last_synced_at',
        'disconnected_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_revenue' => 'decimal:2',
        'average_transaction_value' => 'decimal:2',
        'daily_sales' => 'decimal:2',
        'weekly_sales' => 'decimal:2',
        'monthly_sales' => 'decimal:2',
        'last_synced_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PosProduct::class);
    }

    public function dailySales(): HasMany
    {
        return $this->hasMany(PosDailySale::class);
    }

    public function updateSalesMetrics(): void
    {
        $this->daily_sales = $this->transactions()
            ->whereDate('created_at', today())
            ->sum('total_amount');

        $this->weekly_sales = $this->transactions()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_amount');

        $this->monthly_sales = $this->transactions()
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        if ($this->total_transactions > 0) {
            $this->average_transaction_value = $this->total_revenue / $this->total_transactions;
        }

        $this->save();
    }
}
