<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'business_id',
        'rule_code',
        'rule_name_uz',
        'rule_name_en',
        'description_uz',
        'description_en',
        'alert_type',
        'metric_code',
        'condition',
        'threshold_value',
        'threshold_percent',
        'comparison_period',
        'severity',
        'message_template_uz',
        'message_template_en',
        'action_suggestion_uz',
        'action_suggestion_en',
        'is_active',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:2',
        'threshold_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('business_id');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where(function ($q) use ($businessId) {
            $q->whereNull('business_id')
                ->orWhere('business_id', $businessId);
        });
    }

    public function getName($locale = 'uz')
    {
        return $locale === 'en' && $this->rule_name_en
            ? $this->rule_name_en
            : $this->rule_name_uz;
    }

    public function getMessageTemplate($locale = 'uz')
    {
        return $locale === 'en' && $this->message_template_en
            ? $this->message_template_en
            : $this->message_template_uz;
    }

    public function formatMessage($data, $locale = 'uz')
    {
        $template = $this->getMessageTemplate($locale);

        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }

    public function checkCondition($currentValue, $previousValue = null)
    {
        switch ($this->condition) {
            case 'greater_than':
                return $currentValue > ($this->threshold_value ?? 0);

            case 'less_than':
                return $currentValue < ($this->threshold_value ?? 0);

            case 'equals':
                return $currentValue == ($this->threshold_value ?? 0);

            case 'change_up':
                if (! $previousValue || $previousValue == 0) {
                    return false;
                }
                $change = (($currentValue - $previousValue) / $previousValue) * 100;

                return $change >= ($this->threshold_percent ?? 0);

            case 'change_down':
                if (! $previousValue || $previousValue == 0) {
                    return false;
                }
                $change = (($previousValue - $currentValue) / $previousValue) * 100;

                return $change >= ($this->threshold_percent ?? 0);

            default:
                return false;
        }
    }
}
