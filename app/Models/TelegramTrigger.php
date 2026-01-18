<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramTrigger extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'telegram_bot_id',
        'funnel_id',
        'step_id',
        'name',
        'type',
        'value',
        'match_type',
        'priority',
        'is_active',
        'conditions',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    // Relations
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnel::class, 'funnel_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnelStep::class, 'step_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    // Matching
    public function matches(string $input): bool
    {
        return match ($this->match_type) {
            'exact' => $this->matchExact($input),
            'contains' => $this->matchContains($input),
            'starts_with' => $this->matchStartsWith($input),
            'ends_with' => $this->matchEndsWith($input),
            'regex' => $this->matchRegex($input),
            'wildcard' => $this->matchWildcard($input),
            default => false,
        };
    }

    protected function matchExact(string $input): bool
    {
        return strtolower($input) === strtolower($this->value);
    }

    protected function matchContains(string $input): bool
    {
        $keywords = array_map('trim', explode(',', strtolower($this->value)));
        $inputLower = strtolower($input);

        foreach ($keywords as $keyword) {
            if (str_contains($inputLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function matchStartsWith(string $input): bool
    {
        return str_starts_with(strtolower($input), strtolower($this->value));
    }

    protected function matchEndsWith(string $input): bool
    {
        return str_ends_with(strtolower($input), strtolower($this->value));
    }

    protected function matchRegex(string $input): bool
    {
        return (bool) preg_match('/'.$this->value.'/i', $input);
    }

    protected function matchWildcard(string $input): bool
    {
        $pattern = str_replace('*', '.*', preg_quote($this->value, '/'));

        return (bool) preg_match('/^'.$pattern.'$/i', $input);
    }

    // Conditions
    public function checkConditions(TelegramUser $user): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        // Only new users
        if (data_get($this->conditions, 'only_new_users') && $user->total_messages > 1) {
            return false;
        }

        // Only existing leads
        if (data_get($this->conditions, 'only_existing_leads') && ! $user->lead_id) {
            return false;
        }

        // Required tags
        $requiredTags = data_get($this->conditions, 'required_tags', []);
        if (! empty($requiredTags)) {
            $userTags = $user->tags ?? [];
            if (count(array_intersect($requiredTags, $userTags)) !== count($requiredTags)) {
                return false;
            }
        }

        // Excluded tags
        $excludedTags = data_get($this->conditions, 'excluded_tags', []);
        if (! empty($excludedTags)) {
            $userTags = $user->tags ?? [];
            if (count(array_intersect($excludedTags, $userTags)) > 0) {
                return false;
            }
        }

        return true;
    }
}
