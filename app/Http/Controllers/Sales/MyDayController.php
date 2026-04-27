<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Services\Sales\MyDayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyDayController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        private MyDayService $myDayService
    ) {}

    /**
     * My Day dashboard — barcha rollar uchun bitta endpoint
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($request);
        $role = $business ? $this->getUserSalesRole($user, $business) : 'guest';

        $data = $business ? $this->myDayService->getMyDayData($user, $business) : [];

        return Inertia::render('Sales/MyDay/Index', [
            'data' => $data,
            'role' => $role,
            'followups' => $business ? $this->myDayService->getUpcomingFollowups($user, $business) : [],
            'schedule' => $business ? $this->myDayService->getTodaySchedule($user, $business) : [],
            'weeklyProgress' => $business ? $this->myDayService->getWeeklyProgress($user, $business) : [],
            // Layout panelType — barcha 6 rolni qo'llab-quvvatlash uchun
            'panelType' => $business ? $this->detectPanelType($business) : 'business',
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
        // Business modelida `owner_id` mavjud emas — to'g'risi `user_id`.
        // Avvalgi kod har doim false qaytarib, owner ham 'guest' rolni olardi.
        if ($business->user_id === $user->id) {
            return 'owner';
        }

        $pivot = $user->teamBusinesses()
            ->where('businesses.id', $business->id)
            ->first()?->pivot;

        return $pivot?->department ?? 'guest';
    }
}
