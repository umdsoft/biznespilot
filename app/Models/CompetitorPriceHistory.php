<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorPriceHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'competitor_price_history';

    protected $fillable = [
        'product_id',
        'price',
        'original_price',
        'discount_percent',
        'is_on_sale',
        'stock_status',
        'currency',
        'recorded_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_on_sale' => 'boolean',
        'recorded_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(CompetitorProduct::class, 'product_id');
    }
}
