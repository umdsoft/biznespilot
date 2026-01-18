<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TranslationController extends Controller
{
    /**
     * Get translations for a specific locale
     */
    public function index(string $locale)
    {
        // Validate locale
        $allowedLocales = ['uz-latn', 'uz-cyrl', 'ru'];
        if (! in_array($locale, $allowedLocales)) {
            $locale = 'uz-latn'; // Default fallback
        }

        // Cache translations for 1 hour
        $cacheKey = "translations_{$locale}";

        return Cache::remember($cacheKey, 3600, function () use ($locale) {
            $translations = [];
            $langPath = base_path("lang/{$locale}");

            if (File::isDirectory($langPath)) {
                $files = File::files($langPath);

                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        $filename = $file->getFilenameWithoutExtension();
                        $content = include $file->getPathname();

                        if (is_array($content)) {
                            $translations[$filename] = $content;
                        }
                    }
                }
            }

            // Flatten the translations for easier access
            return $this->flattenTranslations($translations);
        });
    }

    /**
     * Flatten nested translations array
     */
    private function flattenTranslations(array $translations, string $prefix = ''): array
    {
        $result = [];

        foreach ($translations as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenTranslations($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Clear translation cache
     */
    public function clearCache()
    {
        $locales = ['uz-latn', 'uz-cyrl', 'ru'];

        foreach ($locales as $locale) {
            Cache::forget("translations_{$locale}");
        }

        return response()->json(['message' => 'Translation cache cleared']);
    }
}
