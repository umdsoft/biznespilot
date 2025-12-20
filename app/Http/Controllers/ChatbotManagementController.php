<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Models\ChatbotConversation;
use App\Models\ChatbotDailyStats;
use App\Models\ChatbotKnowledgeBase;
use App\Models\ChatbotTemplate;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ChatbotManagementController extends Controller
{
    protected TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Chatbot dashboard
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $config = ChatbotConfig::firstOrCreate(
            ['business_id' => $business->id],
            [
                'bot_name' => $business->name . ' Bot',
                'welcome_message' => "Assalomu alaykum! {business_name}ga xush kelibsiz. Sizga qanday yordam bera olaman?",
                'default_response' => "Kechirasiz, sizning savolingizni tushunmadim. Iltimos, boshqacha so'rab ko'ring.",
                'business_hours_start' => '09:00',
                'business_hours_end' => '18:00',
                'auto_response_enabled' => true,
            ]
        );

        // Get statistics for the last 30 days
        $stats = $this->getDashboardStats($business);

        return Inertia::render('Business/Chatbot/Dashboard', [
            'config' => $config,
            'stats' => $stats,
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(Business $business): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        // Daily stats for last 30 days
        $dailyStats = ChatbotDailyStats::where('business_id', $business->id)
            ->where('date', '>=', $thirtyDaysAgo)
            ->orderBy('date', 'asc')
            ->get();

        // Current active conversations
        $activeConversations = ChatbotConversation::where('business_id', $business->id)
            ->where('status', 'active')
            ->count();

        // Total conversations
        $totalConversations = ChatbotConversation::where('business_id', $business->id)->count();

        // Today's stats
        $todayStats = ChatbotDailyStats::where('business_id', $business->id)
            ->whereDate('date', today())
            ->first();

        // This month's aggregated stats
        $monthStats = ChatbotDailyStats::where('business_id', $business->id)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('
                SUM(total_conversations) as total_conversations,
                SUM(new_conversations) as new_conversations,
                SUM(leads_created) as leads_created,
                SUM(leads_converted) as leads_converted,
                AVG(conversion_rate) as avg_conversion_rate,
                SUM(telegram_conversations) as telegram_conversations,
                SUM(instagram_conversations) as instagram_conversations,
                SUM(facebook_conversations) as facebook_conversations
            ')
            ->first();

        // Funnel breakdown
        $funnelStats = ChatbotConversation::where('business_id', $business->id)
            ->selectRaw('
                current_stage,
                COUNT(*) as count
            ')
            ->groupBy('current_stage')
            ->get()
            ->pluck('count', 'current_stage');

        // Channel distribution
        $channelDistribution = [
            'telegram' => $monthStats->telegram_conversations ?? 0,
            'instagram' => $monthStats->instagram_conversations ?? 0,
            'facebook' => $monthStats->facebook_conversations ?? 0,
        ];

        // Intent breakdown (aggregate from daily stats)
        $intentBreakdown = $this->aggregateIntentBreakdown($dailyStats);

        return [
            'daily_chart' => $dailyStats->map(fn($stat) => [
                'date' => $stat->date->format('Y-m-d'),
                'conversations' => $stat->total_conversations,
                'messages' => $stat->total_messages,
                'leads' => $stat->leads_created,
            ]),
            'active_conversations' => $activeConversations,
            'total_conversations' => $totalConversations,
            'today' => [
                'conversations' => $todayStats->total_conversations ?? 0,
                'messages' => $todayStats->total_messages ?? 0,
                'leads' => $todayStats->leads_created ?? 0,
                'avg_response_time' => $todayStats->avg_response_time_seconds ?? 0,
            ],
            'month' => [
                'conversations' => $monthStats->total_conversations ?? 0,
                'new_conversations' => $monthStats->new_conversations ?? 0,
                'leads_created' => $monthStats->leads_created ?? 0,
                'leads_converted' => $monthStats->leads_converted ?? 0,
                'conversion_rate' => round($monthStats->avg_conversion_rate ?? 0, 2),
            ],
            'funnel' => $funnelStats,
            'channels' => $channelDistribution,
            'intents' => $intentBreakdown,
        ];
    }

    /**
     * Aggregate intent breakdown from daily stats
     */
    private function aggregateIntentBreakdown($dailyStats): array
    {
        $aggregated = [];

        foreach ($dailyStats as $stat) {
            if ($stat->intent_breakdown) {
                foreach ($stat->intent_breakdown as $intent => $count) {
                    $aggregated[$intent] = ($aggregated[$intent] ?? 0) + $count;
                }
            }
        }

        return $aggregated;
    }

    /**
     * Conversations list
     */
    public function conversations(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $query = ChatbotConversation::where('business_id', $business->id)
            ->with(['lead', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->withCount('messages');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by channel
        if ($request->has('channel') && $request->channel !== 'all') {
            $query->where('channel', $request->channel);
        }

        // Filter by stage
        if ($request->has('stage') && $request->stage !== 'all') {
            $query->where('current_stage', $request->stage);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', "%{$request->search}%")
                    ->orWhere('customer_email', 'like', "%{$request->search}%")
                    ->orWhere('customer_phone', 'like', "%{$request->search}%");
            });
        }

        $conversations = $query->orderBy('updated_at', 'desc')
            ->paginate(20);

        return Inertia::render('Business/Chatbot/Conversations', [
            'conversations' => $conversations,
            'filters' => $request->only(['status', 'channel', 'stage', 'search']),
        ]);
    }

    /**
     * Single conversation detail
     */
    public function conversation(Request $request, ChatbotConversation $conversation)
    {
        $this->authorize('view', $conversation);

        $conversation->load([
            'messages' => fn($q) => $q->orderBy('created_at', 'asc'),
            'lead',
        ]);

        return Inertia::render('Business/Chatbot/ConversationDetail', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * Close conversation
     */
    public function closeConversation(Request $request, ChatbotConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return back()->with('success', 'Suhbat yopildi');
    }

    /**
     * Reopen conversation
     */
    public function reopenConversation(Request $request, ChatbotConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $conversation->update([
            'status' => 'active',
            'closed_at' => null,
        ]);

        return back()->with('success', 'Suhbat qayta ochildi');
    }

    /**
     * Hand off to human
     */
    public function handoffConversation(Request $request, ChatbotConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $conversation->update([
            'handed_off' => true,
            'handed_off_at' => now(),
        ]);

        return back()->with('success', 'Suhbat operatorga topshirildi');
    }

    /**
     * Chatbot settings
     */
    public function settings(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $config = ChatbotConfig::firstOrCreate(
            ['business_id' => $business->id],
            [
                'bot_name' => $business->name . ' Bot',
                'welcome_message' => "Assalomu alaykum! {business_name}ga xush kelibsiz.",
                'default_response' => "Kechirasiz, tushunmadim.",
            ]
        );

        return Inertia::render('Business/Chatbot/Settings', [
            'config' => $config,
            'telegram_webhook_url' => route('webhooks.telegram', ['business' => $business->id]),
            'instagram_webhook_url' => route('webhooks.instagram', ['business' => $business->id]),
            'facebook_webhook_url' => route('webhooks.facebook', ['business' => $business->id]),
        ]);
    }

    /**
     * Update chatbot settings
     */
    public function updateSettings(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'bot_name' => 'nullable|string|max:255',
            'welcome_message' => 'required|string',
            'default_response' => 'required|string',
            'business_hours_start' => 'nullable|date_format:H:i',
            'business_hours_end' => 'nullable|date_format:H:i',
            'outside_hours_message' => 'nullable|string',
            'auto_response_enabled' => 'boolean',
            'ai_enabled' => 'boolean',
            'telegram_enabled' => 'boolean',
            'telegram_bot_token' => 'nullable|string',
            'instagram_enabled' => 'boolean',
            'instagram_page_id' => 'nullable|string',
            'instagram_access_token' => 'nullable|string',
            'facebook_enabled' => 'boolean',
            'facebook_page_id' => 'nullable|string',
            'facebook_access_token' => 'nullable|string',
        ]);

        $config = ChatbotConfig::updateOrCreate(
            ['business_id' => $business->id],
            $validated
        );

        return back()->with('success', 'Sozlamalar saqlandi');
    }

    /**
     * Setup Telegram webhook
     */
    public function setupTelegramWebhook(Request $request)
    {
        $business = $request->user()->currentBusiness;
        $config = ChatbotConfig::where('business_id', $business->id)->first();

        if (!$config || !$config->telegram_bot_token) {
            return back()->withErrors(['telegram' => 'Telegram bot token kiritilmagan']);
        }

        $webhookUrl = route('webhooks.telegram', ['business' => $business->id]);

        $result = $this->telegramService->setWebhook($config->telegram_bot_token, $webhookUrl);

        if ($result['success']) {
            return back()->with('success', 'Telegram webhook o\'rnatildi');
        }

        return back()->withErrors(['telegram' => $result['message'] ?? 'Webhook o\'rnatishda xatolik']);
    }

    /**
     * Get Telegram bot info
     */
    public function getTelegramBotInfo(Request $request)
    {
        $business = $request->user()->currentBusiness;
        $config = ChatbotConfig::where('business_id', $business->id)->first();

        if (!$config || !$config->telegram_bot_token) {
            return response()->json(['error' => 'Token not configured'], 400);
        }

        $info = $this->telegramService->getBotInfo($config->telegram_bot_token);

        return response()->json($info);
    }

    /**
     * Knowledge base management
     */
    public function knowledgeBase(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $query = ChatbotKnowledgeBase::where('business_id', $business->id);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('question', 'like', "%{$request->search}%")
                    ->orWhere('answer', 'like', "%{$request->search}%")
                    ->orWhere('category', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $knowledgeBase = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $categories = ChatbotKnowledgeBase::where('business_id', $business->id)
            ->select('category')
            ->distinct()
            ->pluck('category');

        return Inertia::render('Business/Chatbot/KnowledgeBase', [
            'knowledge_base' => $knowledgeBase,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Store knowledge base item
     */
    public function storeKnowledge(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['business_id'] = $business->id;

        ChatbotKnowledgeBase::create($validated);

        return back()->with('success', 'Bilim bazasiga qo\'shildi');
    }

    /**
     * Update knowledge base item
     */
    public function updateKnowledge(Request $request, ChatbotKnowledgeBase $knowledge)
    {
        $this->authorize('update', $knowledge);

        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $knowledge->update($validated);

        return back()->with('success', 'Yangilandi');
    }

    /**
     * Delete knowledge base item
     */
    public function destroyKnowledge(Request $request, ChatbotKnowledgeBase $knowledge)
    {
        $this->authorize('delete', $knowledge);

        $knowledge->delete();

        return back()->with('success', 'O\'chirildi');
    }

    /**
     * Templates management
     */
    public function templates(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $query = ChatbotTemplate::where('business_id', $business->id);

        if ($request->has('intent') && $request->intent !== 'all') {
            $query->where('intent', $request->intent);
        }

        if ($request->has('stage') && $request->stage !== 'all') {
            $query->where('funnel_stage', $request->stage);
        }

        $templates = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Chatbot/Templates', [
            'templates' => $templates,
            'filters' => $request->only(['intent', 'stage']),
        ]);
    }

    /**
     * Store template
     */
    public function storeTemplate(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'intent' => 'nullable|string',
            'funnel_stage' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
            'variables' => 'nullable|array',
            'quick_replies' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['business_id'] = $business->id;

        ChatbotTemplate::create($validated);

        return back()->with('success', 'Shablon yaratildi');
    }

    /**
     * Update template
     */
    public function updateTemplate(Request $request, ChatbotTemplate $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'intent' => 'nullable|string',
            'funnel_stage' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
            'variables' => 'nullable|array',
            'quick_replies' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return back()->with('success', 'Shablon yangilandi');
    }

    /**
     * Delete template
     */
    public function destroyTemplate(Request $request, ChatbotTemplate $template)
    {
        $this->authorize('delete', $template);

        $template->delete();

        return back()->with('success', 'Shablon o\'chirildi');
    }
}
