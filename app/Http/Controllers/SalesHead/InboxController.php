<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Services\UnifiedInboxService;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InboxController extends Controller
{
    use HasCurrentBusiness;

    protected UnifiedInboxService $inboxService;

    public function __construct(UnifiedInboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    /**
     * Display inbox index page
     */
    public function index(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            // If no business in session, show empty inbox
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'conversations' => [],
                    'stats' => ['total' => 0, 'unread' => ['total' => 0]],
                ]);
            }

            return Inertia::render('SalesHead/Inbox/Index', [
                'conversations' => [],
                'stats' => ['total' => 0, 'unread' => ['total' => 0]],
                'filters' => [],
                'currentBusiness' => null,
            ]);
        }

        $filters = [
            'channel' => $request->get('channel'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        try {
            $conversations = $this->inboxService->getAllConversations($currentBusiness, $filters);
            $stats = $this->inboxService->getInboxStats($currentBusiness);
        } catch (\Exception $e) {
            \Log::error('Inbox error: ' . $e->getMessage());
            $conversations = collect([]);
            $stats = ['total' => 0, 'unread' => ['total' => 0]];
        }

        // Return JSON for AJAX polling requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'conversations' => $conversations->values()->toArray(),
                'stats' => $stats,
            ]);
        }

        return Inertia::render('SalesHead/Inbox/Index', [
            'conversations' => $conversations->values()->toArray(),
            'stats' => $stats,
            'filters' => $filters,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
        ]);
    }

    /**
     * Show conversation details
     */
    public function show($conversationId)
    {
        $conversation = $this->inboxService->getConversationDetails($conversationId);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Send message to conversation
     */
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
