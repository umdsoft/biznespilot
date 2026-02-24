<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Store\TelegramStore;
use Illuminate\Http\JsonResponse;

class RegionController extends Controller
{
    /**
     * GET /regions — List all regions (viloyatlar).
     */
    public function regions(TelegramStore $store): JsonResponse
    {
        $regions = collect(Lead::REGIONS)->map(fn ($name, $key) => [
            'key' => $key,
            'name' => $name,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    /**
     * GET /regions/{key}/districts — List districts for a region.
     */
    public function districts(TelegramStore $store, string $key): JsonResponse
    {
        if (! isset(Lead::DISTRICTS[$key])) {
            return response()->json([
                'success' => false,
                'message' => 'Viloyat topilmadi',
            ], 404);
        }

        $districts = collect(Lead::DISTRICTS[$key])->map(fn ($name, $dKey) => [
            'key' => $dKey,
            'name' => $name,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }
}
