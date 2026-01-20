<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\MyDayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyDayController extends Controller
{
    public function __construct(
        private MyDayService $myDayService
    ) {}

    /**
     * My Day dashboard
     * Barcha rollar uchun bitta endpoint
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $business = $user->currentBusiness;
        $role = $this->getUserSalesRole($user, $business);

        $data = $this->myDayService->getMyDayData($user, $business);

        return Inertia::render('Sales/MyDay/Index', [
            'data' => $data,
            'role' => $role,
            'followups' => $this->myDayService->getUpcomingFollowups($user, $business),
            'schedule' => $this->myDayService->getTodaySchedule($user, $business),
            'weeklyProgress' => $this->myDayService->getWeeklyProgress($user, $business),
        ]);
    }

    /**
     * API endpoint for real-time stats refresh
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        $data = $this->myDayService->getMyDayData($user, $business);

        return response()->json([
            'stats' => $data['stats'],
            'targets' => $data['targets'],
            'alerts' => $data['alerts'],
        ]);
    }

    /**
     * Foydalanuvchining sotuv rolini olish
     */
    protected function getUserSalesRole($user, $business): string
    {
        if ($business->owner_id === $user->id) {
            return 'owner';
        }

        $pivot = $user->teamBusinesses()
            ->where('businesses.id', $business->id)
            ->first()?->pivot;

        return $pivot?->department ?? 'guest';
    }
}
