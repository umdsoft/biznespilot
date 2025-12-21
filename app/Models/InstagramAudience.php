<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramAudience extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'instagram_audience';

    protected $fillable = [
        'instagram_account_id',
        'business_id',
        'age_gender',
        'top_cities',
        'top_countries',
        'online_hours',
        'online_days',
        'calculated_at',
    ];

    protected $casts = [
        'age_gender' => 'array',
        'top_cities' => 'array',
        'top_countries' => 'array',
        'online_hours' => 'array',
        'online_days' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    public function getBestPostingTimesAttribute(): array
    {
        $hours = $this->online_hours ?? [];

        if (empty($hours)) {
            return [];
        }

        arsort($hours);
        return array_slice($hours, 0, 3, true);
    }

    public function getBestPostingDaysAttribute(): array
    {
        $days = $this->online_days ?? [];

        if (empty($days)) {
            return [];
        }

        arsort($days);
        return array_slice($days, 0, 3, true);
    }

    /**
     * Get dominant age group
     * Supports both old format (M.18-24, F.25-34) and new API format (by_age, by_gender)
     */
    public function getDominantAgeGroupAttribute(): ?string
    {
        $ageGender = $this->age_gender ?? [];

        if (empty($ageGender)) {
            return null;
        }

        $ageGroups = [];

        // New API format (has 'by_age' key)
        if (isset($ageGender['by_age']) && is_array($ageGender['by_age'])) {
            $ageGroups = $ageGender['by_age'];
        } else {
            // Old format: aggregate by age range (combine M and F)
            foreach ($ageGender as $key => $value) {
                // Skip if value is an array (nested structure)
                if (is_array($value)) {
                    continue;
                }

                // Key format: "F.25-34" or "M.18-24"
                $parts = explode('.', $key);
                $ageRange = $parts[1] ?? $key;

                if (!isset($ageGroups[$ageRange])) {
                    $ageGroups[$ageRange] = 0;
                }
                $ageGroups[$ageRange] += $value;
            }
        }

        if (empty($ageGroups)) {
            return null;
        }

        arsort($ageGroups);
        return array_key_first($ageGroups);
    }

    /**
     * Get gender distribution
     * Supports both old format (M.18-24, F.25-34) and new API format (by_age, by_gender)
     */
    public function getGenderDistributionAttribute(): array
    {
        $ageGender = $this->age_gender ?? [];

        $distribution = ['male' => 0, 'female' => 0, 'unknown' => 0];

        // New API format (has 'by_gender' key)
        if (isset($ageGender['by_gender']) && is_array($ageGender['by_gender'])) {
            $byGender = $ageGender['by_gender'];
            $distribution['male'] = $byGender['M'] ?? 0;
            $distribution['female'] = $byGender['F'] ?? 0;
            $distribution['unknown'] = $byGender['U'] ?? 0;
        } else {
            // Old format: parse from M.XX-XX and F.XX-XX keys
            foreach ($ageGender as $key => $value) {
                if (is_array($value)) {
                    continue;
                }

                if (str_starts_with($key, 'M.')) {
                    $distribution['male'] += $value;
                } elseif (str_starts_with($key, 'F.')) {
                    $distribution['female'] += $value;
                } else {
                    $distribution['unknown'] += $value;
                }
            }
        }

        $total = array_sum($distribution);
        if ($total > 0) {
            foreach ($distribution as $key => $value) {
                $distribution[$key] = round(($value / $total) * 100, 1);
            }
        }

        return $distribution;
    }
}
