<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Services\UnifiedInboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UnifiedInboxController extends Controller
{
    use HasCurrentBusiness;

    protected UnifiedInboxService $inboxService;

    public function __construct(UnifiedInboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    /**
     * Sidebar badge uchun — faqat unread count (tez va yengil)
     */
    public function unreadCount(Request $request)
    {
        $user = Auth::user();
        // HasCurrentBusiness trait — owner + team membership ikkalasini qo'llab-quvvatlaydi.
        // `$user->businesses()` faqat OWNED business'larni qaytaradi (team-member uchun null).
        $business = $this->getCurrentBusiness($request);
        $businessId = $business?->id;

        if (!$businessId) {
            return response()->json(['count' => 0]);
        }

        try {
            $count = \Illuminate\Support\Facades\Cache::remember(
                "inbox_unread_count:{$businessId}",
                30, // 30 sekund kesh
                function () use ($businessId) {
                    // Faqat count — to'liq suhbatlar yuklanmaydi
                    $total = 0;
                    foreach (['telegram_messages', 'instagram_messages'] as $table) {
                        if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                            $total += \Illuminate\Support\Facades\DB::table($table)
                                ->where('business_id', $businessId)
                                ->where('is_read', false)
                                ->where('direction', 'incoming')
                                ->count();
                        }
                    }
                    return $total;
                }
            );
            return response()->json(['count' => (int) $count]);
        } catch (\Exception $e) {
            return response()->json(['count' => 0]);
        }
    }

    public function index(Request $request)
    {
        // HasCurrentBusiness — team-member ham accessible (faqat owned emas).
        $currentBusiness = $this->getCurrentBusiness($request);

        if (! $currentBusiness) {
            return redirect()->route('login');
        }

        $filters = [
            'channel' => $request->get('channel'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        $conversations = $this->inboxService->getAllConversations($currentBusiness, $filters);
        $stats = $this->inboxService->getInboxStats($currentBusiness);

        // Return JSON for AJAX polling requests (but not for Inertia requests)
        if (! $request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
            return response()->json([
                'conversations' => $conversations,
                'stats' => $stats,
            ]);
        }

        return Inertia::render('Business/Inbox/Index', [
            'conversations' => $conversations,
            'stats' => $stats,
            'filters' => $filters,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
            'panelType' => $this->detectPanelType($currentBusiness),
        ]);
    }

    public function show($conversationId)
    {
        $conversation = $this->inboxService->getConversationDetails($conversationId);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $result = $this->inboxService->sendMessage(
            $conversationId,
            $validated['message'],
            Auth::id()
        );

        return response()->json($result);
    }
}
