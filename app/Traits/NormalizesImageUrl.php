<?php

namespace App\Traits;

/**
 * Normalizes image_url to relative path.
 *
 * Old records may contain full URLs with stale tunnel/domain names.
 * This accessor strips the host, returning only the path portion
 * (e.g., /storage/store/products/uuid/file.webp).
 */
trait NormalizesImageUrl
{
    public function getImageUrlAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        // Already relative
        if (str_starts_with($value, '/')) {
            return $value;
        }

        // Full URL — extract path portion only
        $path = parse_url($value, PHP_URL_PATH);

        return $path ?: $value;
    }
}
