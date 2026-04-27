<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Services\UnifiedInboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Shared Inbox — saleshead/operator (va boshqa rollar) uchun.
 *
 * Avval har panel uchun alohida Vue sahifa render qilinardi
 * ('SalesHead/Inbox/Index', 'Operator/Inbox/Index'). Endi `Business/Inbox/Index`
 * dynamic layoutComponent bilan barcha rollarga xizmat qiladi
 * — `panelType` propi orqali (HasCurrentBusiness::detectPanelType).
 */
class InboxController extends Controller
{
    use HasCurrentBusiness;

    protected UnifiedInboxService $inboxService;

    public function __construct(UnifiedInboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    public function index(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness($request);
        $panelType = $currentBusiness ? $this->detectPanelType($currentBusiness) : 'business';

        if (! $currentBusiness) {
            if (! $request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
                return response()->json([
                    'conversations' => [],
                    'stats' => ['total' => 0, 'unread' => ['total' => 0]],
                ]);
            }

            return Inertia::render('Business/Inbox/Index', [
                'conversations' => [],
                'stats' => ['total' => 0, 'unread' => ['total' => 0]],
                'filters' => [],
                'currentBusiness' => null,
                'panelType' => $panelType,
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

        return Inertia::render('Business/Inbox/Index', [
            'conversations' => $conversations->values()->toArray(),
            'stats' => $stats,
            'filters' => $filters,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
            'panelType' => $panelType,
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
