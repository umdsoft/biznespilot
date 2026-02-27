<?php

namespace App\Http\Controllers\Api\Bot\Service;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Service\ServiceMasterResource;
use App\Models\Bot\Service\ServiceMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceMasterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');

        $query = ServiceMaster::forBusiness($businessId)
            ->active()
            ->with('categories');

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('service_categories.id', $request->input('category_id'));
            });
        }

        $sortField = $request->input('sort', 'rating_avg');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $masters = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'masters' => ServiceMasterResource::collection($masters),
            'meta' => [
                'current_page' => $masters->currentPage(),
                'last_page' => $masters->lastPage(),
                'total' => $masters->total(),
            ],
        ]);
    }

    public function show(ServiceMaster $master): JsonResponse
    {
        $master->load('categories');

        return response()->json([
            'master' => new ServiceMasterResource($master),
            'stats' => [
                'completed_jobs' => $master->completed_jobs,
                'rating_avg' => (float) $master->rating_avg,
                'rating_count' => $master->rating_count,
                'experience_years' => $master->experience_years,
            ],
        ]);
    }
}
