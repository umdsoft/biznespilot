<?php

namespace App\Http\Controllers;

use App\Services\UnifiedInboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UnifiedInboxController extends Controller
{
    protected UnifiedInboxService $inboxService;

    public function __construct(UnifiedInboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        $filters = [
            'channel' => $request->get('channel'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        $conversations = $this->inboxService->getAllConversations($currentBusiness, $filters);
        $stats = $this->inboxService->getInboxStats($currentBusiness);

        // Return JSON for AJAX polling requests
        if ($request->wantsJson() || $request->ajax()) {
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
