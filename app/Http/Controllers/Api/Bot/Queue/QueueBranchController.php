<?php

namespace App\Http\Controllers\Api\Bot\Queue;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Queue\QueueBranchResource;
use App\Http\Resources\Bot\Queue\QueueSpecialistResource;
use App\Models\Bot\Queue\QueueBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueBranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');

        $branches = QueueBranch::forBusiness($businessId)
            ->active()
            ->withCount('specialists')
            ->with('services')
            ->get();

        return response()->json([
            'branches' => QueueBranchResource::collection($branches),
        ]);
    }

    public function specialists(QueueBranch $branch): JsonResponse
    {
        $specialists = $branch->specialists()
            ->where('is_active', true)
            ->with('services')
            ->get();

        return response()->json([
            'specialists' => QueueSpecialistResource::collection($specialists),
        ]);
    }
}
