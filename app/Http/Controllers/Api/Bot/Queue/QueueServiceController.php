<?php

namespace App\Http\Controllers\Api\Bot\Queue;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Queue\QueueServiceResource;
use App\Models\Bot\Queue\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');

        $services = QueueService::forBusiness($businessId)
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'services' => QueueServiceResource::collection($services),
        ]);
    }
}
