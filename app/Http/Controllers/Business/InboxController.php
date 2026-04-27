<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Services\UnifiedInboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * Yagona Inbox — barcha rollar uchun bitta umumiy controller.
 *
 * Avval ikki xil controller bor edi (UnifiedInboxController + Shared\InboxController),
 * har biri o'xshash logikani qaytarardi. Endi business/marketing/operator/sales-head
 * rollarining barchasi shu sinfdan foydalanadi va `Business/Inbox/Index` Vue
 * sahifasini render qiladi. Foydalanuvchi roli `panelType` propi orqali
 * (HasCurrentBusiness::detectPanelType) komponentga uzatiladi.
 */
class InboxController extends Controller
{
    use HasCurrentBusiness;

    protected UnifiedInboxService $inboxService;

    public function __construct(UnifiedInboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    /**
     * Inbox asosiy sahifasi — barcha kanallardagi suhbatlar va stats.
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);
        $panelType = $business ? $this->detectPanelType($business) : 'business';

        // Biznes konteksti yo'q bo'lsa — bo'sh inbox qaytariladi (oq sahifa o'rniga)
        if (! $business) {
            return $this->emptyInboxResponse($request, $panelType);
        }

        $filters = [
            'channel' => $request->get('channel'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        try {
            $conversations = $this->inboxService->getAllConversations($business, $filters);
            $stats = $this->inboxService->getInboxStats($business);
        } catch (\Throwable $e) {
            \Log::error('Inbox: getAllConversations failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            $conversations = collect([]);
            $stats = ['total' => 0, 'open' => 0, 'pending' => 0, 'closed' => 0, 'unread' => ['total' => 0]];
        }

        $payload = [
            'conversations' => method_exists($conversations, 'values')
                ? $conversations->values()->toArray()
                : (array) $conversations,
            'stats' => $stats,
            'filters' => $filters,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'panelType' => $panelType,
        ];

        if ($this->wantsJsonNotInertia($request)) {
            return response()->json([
                'conversations' => $payload['conversations'],
                'stats' => $stats,
            ]);
        }

        return Inertia::render('Business/Inbox/Index', $payload);
    }

    /**
     * Suhbat tafsilotlari — modal/sahifa uchun.
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
     * Operator/marketing tomonidan xabar yuborish.
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

    /**
     * Suhbatni o'qilgan deb belgilash.
     */
    public function markRead(Request $request, $conversationId)
    {
        $business = $this->getCurrentBusiness($request);

        if (! $business) {
            return response()->json(['success' => false], 403);
        }

        // Cache invalidation — sidebar badge'ini darhol yangilash uchun
        Cache::forget("inbox_unread_count:{$business->id}");

        // Service-da markRead bo'lsa chaqirish, bo'lmasa muvaffaqiyat qaytarish
        if (method_exists($this->inboxService, 'markConversationRead')) {
            $this->inboxService->markConversationRead($conversationId);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Sidebar badge uchun yengil endpoint — faqat o'qilmaganlar soni.
     */
    public function unreadCount(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (! $business) {
            return response()->json(['count' => 0]);
        }

        try {
            $count = Cache::remember(
                "inbox_unread_count:{$business->id}",
                30,
                function () use ($business) {
                    $total = 0;
                    foreach (['telegram_messages', 'instagram_messages'] as $table) {
                        if (Schema::hasTable($table)) {
                            $total += DB::table($table)
                                ->where('business_id', $business->id)
                                ->where('is_read', false)
                                ->where('direction', 'incoming')
                                ->count();
                        }
                    }

                    return $total;
                }
            );

            return response()->json(['count' => (int) $count]);
        } catch (\Throwable $e) {
            return response()->json(['count' => 0]);
        }
    }

    /**
     * Biznes yo'q bo'lsa — bo'sh inbox response (HTTP 200, oq sahifa emas).
     */
    protected function emptyInboxResponse(Request $request, string $panelType)
    {
        $emptyStats = ['total' => 0, 'open' => 0, 'pending' => 0, 'closed' => 0, 'unread' => ['total' => 0]];

        if ($this->wantsJsonNotInertia($request)) {
            return response()->json([
                'conversations' => [],
                'stats' => $emptyStats,
            ]);
        }

        return Inertia::render('Business/Inbox/Index', [
            'conversations' => [],
            'stats' => $emptyStats,
            'filters' => [],
            'currentBusiness' => null,
            'panelType' => $panelType,
        ]);
    }

    /**
     * AJAX polling so'rovlarini Inertia so'rovlaridan ajratish.
     */
    protected function wantsJsonNotInertia(Request $request): bool
    {
        return ! $request->header('X-Inertia') && ($request->wantsJson() || $request->ajax());
    }
}
