<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Services\UnifiedInboxService;
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

    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'sales-head')) {
            return 'SalesHead';
        }
        if (str_contains($prefix, 'operator')) {
            return 'Operator';
        }

        return 'SalesHead';
    }

    public function index(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        if (! $currentBusiness) {
            if (! $request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
                return response()->json([
                    'conversations' => [],
                    'stats' => ['total' => 0, 'unread' => ['total' => 0]],
                ]);
            }

            return Inertia::render($panelType.'/Inbox/Index', [
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
            \Log::error('Inbox error: '.$e->getMessage());
            $conversations = collect([]);
            $stats = ['total' => 0, 'unread' => ['total' => 0]];
        }

        if (! $request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
            return response()->json([
                'conversations' => $conversations->values()->toArray(),
                'stats' => $stats,
            ]);
        }

        return Inertia::render($panelType.'/Inbox/Index', [
            'conversations' => $conversations->values()->toArray(),
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
