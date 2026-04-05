<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class CashFlowForecast extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id', 'forecast_date', 'predicted_income',
        'predicted_expense', 'predicted_balance', 'confidence_level', 'is_danger',
    ];

    protected $casts = [
        'forecast_date' => 'date', 'predicted_income' => 'decimal:2',
        'predicted_expense' => 'decimal:2', 'predicted_balance' => 'decimal:2',
        'confidence_level' => 'decimal:2', 'is_danger' => 'boolean',
    ];
}
