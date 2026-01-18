<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'user_id',
        'widget_type',
        'widget_code',
        'title',
        'data_source',
        'metric',
        'aggregation',
        'period',
        'comparison_period',
        'position_x',
        'position_y',
        'width',
        'height',
        'sort_order',
        'color_scheme',
        'show_trend',
        'show_comparison',
        'is_visible',
        'is_pinned',
    ];

    protected $casts = [
        'show_trend' => 'boolean',
        'show_comparison' => 'boolean',
        'is_visible' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('position_y')->orderBy('position_x');
    }

    public static function getDefaultWidgets()
    {
        return [
            [
                'widget_type' => 'kpi_card',
                'widget_code' => 'revenue',
                'title' => 'Daromad',
                'data_source' => 'kpi_daily',
                'metric' => 'revenue_total',
                'period' => 'this_month',
                'comparison_period' => 'previous_period',
                'position_x' => 0,
                'position_y' => 0,
                'width' => 1,
                'height' => 1,
                'color_scheme' => 'green',
            ],
            [
                'widget_type' => 'kpi_card',
                'widget_code' => 'leads',
                'title' => 'Lidlar',
                'data_source' => 'kpi_daily',
                'metric' => 'leads_total',
                'period' => 'this_month',
                'comparison_period' => 'previous_period',
                'position_x' => 1,
                'position_y' => 0,
                'width' => 1,
                'height' => 1,
                'color_scheme' => 'blue',
            ],
            [
                'widget_type' => 'kpi_card',
                'widget_code' => 'cac',
                'title' => 'CAC',
                'data_source' => 'kpi_daily',
                'metric' => 'cac',
                'period' => 'this_month',
                'comparison_period' => 'previous_period',
                'position_x' => 2,
                'position_y' => 0,
                'width' => 1,
                'height' => 1,
                'color_scheme' => 'purple',
            ],
            [
                'widget_type' => 'kpi_card',
                'widget_code' => 'roas',
                'title' => 'ROAS',
                'data_source' => 'kpi_daily',
                'metric' => 'ad_roas',
                'period' => 'this_month',
                'comparison_period' => 'previous_period',
                'position_x' => 3,
                'position_y' => 0,
                'width' => 1,
                'height' => 1,
                'color_scheme' => 'amber',
            ],
            [
                'widget_type' => 'gauge',
                'widget_code' => 'health',
                'title' => 'Biznes Salomatligi',
                'data_source' => 'kpi_daily',
                'metric' => 'health_score',
                'period' => 'today',
                'position_x' => 4,
                'position_y' => 0,
                'width' => 1,
                'height' => 1,
                'color_scheme' => 'default',
            ],
            [
                'widget_type' => 'chart_line',
                'widget_code' => 'revenue_trend',
                'title' => 'Daromad Trendi',
                'data_source' => 'kpi_daily',
                'metric' => 'revenue_total',
                'period' => 'this_month',
                'position_x' => 0,
                'position_y' => 1,
                'width' => 2,
                'height' => 1,
                'color_scheme' => 'green',
            ],
            [
                'widget_type' => 'chart_funnel',
                'widget_code' => 'sales_funnel',
                'title' => 'Savdo Funneli',
                'data_source' => 'funnel',
                'period' => 'this_month',
                'position_x' => 2,
                'position_y' => 1,
                'width' => 2,
                'height' => 1,
                'color_scheme' => 'blue',
            ],
        ];
    }
}
