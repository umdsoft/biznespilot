<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ChannelAnalyticsController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ChatbotManagementController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Telegram\TelegramBotManagementController;
use App\Http\Controllers\Telegram\TelegramFunnelController;
use App\Http\Controllers\Telegram\TelegramTriggerController;
use App\Http\Controllers\Telegram\TelegramBroadcastController;
use App\Http\Controllers\Telegram\TelegramUserController;
use App\Http\Controllers\Telegram\TelegramConversationController;
use App\Http\Controllers\Telegram\TelegramFunnelWebhookController;
use App\Http\Controllers\InstagramWebhookController;
use App\Http\Controllers\FacebookWebhookController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\CompetitorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\ContentCalendarController;
use App\Http\Controllers\DreamBuyerController;
use App\Http\Controllers\CustdevController;
use App\Http\Controllers\PublicSurveyController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MarketingAnalyticsController;
use App\Http\Controllers\MarketingCampaignController;
use App\Http\Controllers\MetaCampaignController;
use App\Http\Controllers\OffersController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TelephonyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TodoTemplateController;
use App\Http\Controllers\LeadFormController;
use App\Http\Controllers\PublicLeadFormController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\UnifiedInboxController;
use App\Http\Controllers\OnboardingWebController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BusinessManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\FeedbackManagementController;
use App\Http\Controllers\Admin\NotificationManagementController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\TargetAnalysisController;
use App\Http\Controllers\InstagramAnalysisController;
use App\Http\Controllers\InstagramChatbotController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\AlgorithmController;
use App\Http\Controllers\YouTubeAnalyticsController;
use App\Http\Controllers\GoogleAdsAnalyticsController;
use App\Http\Controllers\GoogleAdsCampaignController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Public team invitation routes
Route::get('/invite/{token}', [TeamController::class, 'showAcceptInvite'])->name('invite.show');
Route::post('/invite/{token}/accept', [TeamController::class, 'acceptInvite'])->name('invite.accept');

// 2FA verification routes (accessible without full auth)
Route::get('/two-factor/verify', [AuthController::class, 'showTwoFactorVerify'])->name('two-factor.verify');
Route::post('/two-factor/verify', [AuthController::class, 'verifyTwoFactor']);

// Root redirect based on role and business status
Route::middleware('auth')->get('/', function () {
    $user = auth()->user();

    if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
        return redirect()->route('admin.dashboard');
    }

    // Check if user has a business
    if (!$user->businesses()->exists()) {
        return redirect()->route('welcome.index');
    }

    return redirect()->route('business.dashboard');
});

// Logout route (accessible from both panels)
Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('logout');

// Business switching route
Route::middleware('auth')->post('/switch-business/{business}', [WelcomeController::class, 'switchBusiness'])->name('switch-business');

// Welcome routes (for users without business)
Route::middleware('auth')->prefix('welcome')->name('welcome.')->group(function () {
    Route::get('/', [WelcomeController::class, 'index'])->name('index');
    Route::get('/create-business', [WelcomeController::class, 'createBusiness'])->name('create-business');
    Route::post('/create-business', [WelcomeController::class, 'storeBusiness'])->name('store-business');
    Route::get('/start', [WelcomeController::class, 'start'])->name('start');
});

// Create new business (for users who already have businesses)
Route::middleware('auth')->get('/new-business', [WelcomeController::class, 'newBusiness'])->name('new-business');
Route::middleware('auth')->post('/new-business', [WelcomeController::class, 'storeNewBusiness'])->name('store-new-business');

// Onboarding routes (outside business prefix for cleaner URL, requires business)
Route::middleware(['auth', 'has.business'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingWebController::class, 'index'])->name('index');
    Route::get('/step/{stepCode}', [OnboardingWebController::class, 'step'])->name('step');
    Route::post('/complete', [OnboardingWebController::class, 'complete'])->name('complete');
});

// Business Panel Routes (requires business)
Route::middleware(['auth', 'has.business'])->prefix('business')->name('business.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Business routes
    Route::resource('business', BusinessController::class);

    // Dream Buyer routes
    Route::prefix('dream-buyer')->name('dream-buyer.')->group(function () {
        Route::get('/', [DreamBuyerController::class, 'index'])->name('index');
        Route::get('/create', [DreamBuyerController::class, 'create'])->name('create');
        Route::post('/', [DreamBuyerController::class, 'store'])->name('store');
        Route::post('/generate-profile', [DreamBuyerController::class, 'generateProfile'])->name('generate-profile');
        Route::get('/{dreamBuyer}', [DreamBuyerController::class, 'show'])->name('show');
        Route::get('/{dreamBuyer}/edit', [DreamBuyerController::class, 'edit'])->name('edit');
        Route::put('/{dreamBuyer}', [DreamBuyerController::class, 'update'])->name('update');
        Route::delete('/{dreamBuyer}', [DreamBuyerController::class, 'destroy'])->name('destroy');
        Route::post('/{dreamBuyer}/set-primary', [DreamBuyerController::class, 'setPrimary'])->name('set-primary');
        Route::post('/{dreamBuyer}/content-ideas', [DreamBuyerController::class, 'generateContentIdeas'])->name('content-ideas');
        Route::post('/{dreamBuyer}/ad-copy', [DreamBuyerController::class, 'generateAdCopy'])->name('ad-copy');
    });

    // CustDev (Customer Development) routes
    Route::prefix('custdev')->name('custdev.')->group(function () {
        Route::get('/', [CustdevController::class, 'index'])->name('index');
        Route::get('/create', [CustdevController::class, 'create'])->name('create');
        Route::post('/', [CustdevController::class, 'store'])->name('store');
        Route::get('/{custdev}', [CustdevController::class, 'show'])->name('show');
        Route::get('/{custdev}/edit', [CustdevController::class, 'edit'])->name('edit');
        Route::put('/{custdev}', [CustdevController::class, 'update'])->name('update');
        Route::delete('/{custdev}', [CustdevController::class, 'destroy'])->name('destroy');
        Route::post('/{custdev}/toggle-status', [CustdevController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{custdev}/results', [CustdevController::class, 'results'])->name('results');
        Route::get('/{custdev}/export', [CustdevController::class, 'export'])->name('export');
        Route::post('/{custdev}/sync-dream-buyer', [CustdevController::class, 'syncToDreamBuyer'])->name('sync-dream-buyer');
    });

    // Marketing routes
    Route::prefix('marketing')->name('marketing.')->group(function () {
        // Marketing Analytics Dashboard
        Route::get('/', [MarketingAnalyticsController::class, 'index'])->name('index');
        Route::get('/analytics', [MarketingAnalyticsController::class, 'index'])->name('analytics');

        // Lazy Loading API Endpoint
        Route::get('/api/dashboard', [MarketingAnalyticsController::class, 'getDashboardData'])->name('api.dashboard');

        // Channels Management
        Route::get('/channels', [MarketingAnalyticsController::class, 'channels'])->name('channels');
        Route::post('/channels', [MarketingAnalyticsController::class, 'store'])->name('channels.store');
        Route::get('/channels/{channel}', [MarketingAnalyticsController::class, 'channelDetail'])->name('channels.show');
        Route::put('/channels/{channel}', [MarketingAnalyticsController::class, 'update'])->name('channels.update');
        Route::delete('/channels/{channel}', [MarketingAnalyticsController::class, 'destroy'])->name('channels.destroy');

        // Old Marketing Controller routes (content, etc.)
        Route::get('/content', [MarketingController::class, 'content'])->name('content');
        Route::post('/content', [MarketingController::class, 'storeContent'])->name('content.store');
        Route::get('/content/{content}', [MarketingController::class, 'showContent'])->name('content.show');
        Route::get('/content/{content}/edit', [MarketingController::class, 'editContent'])->name('content.edit');
        Route::put('/content/{content}', [MarketingController::class, 'updateContent'])->name('content.update');
        Route::delete('/content/{content}', [MarketingController::class, 'deleteContent'])->name('content.destroy');
    });

    // Marketing Campaigns routes
    Route::prefix('marketing/campaigns')->name('marketing.campaigns.')->group(function () {
        Route::get('/', [MarketingCampaignController::class, 'index'])->name('index');
        Route::get('/create', [MarketingCampaignController::class, 'create'])->name('create');
        Route::post('/', [MarketingCampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [MarketingCampaignController::class, 'show'])->name('show');
        Route::post('/generate-ai', [MarketingCampaignController::class, 'generateAI'])->name('generate-ai');
        Route::post('/{campaign}/launch', [MarketingCampaignController::class, 'launch'])->name('launch');
    });

    // Unified Inbox routes
    Route::prefix('inbox')->name('inbox.')->group(function () {
        Route::get('/', [UnifiedInboxController::class, 'index'])->name('index');
        Route::get('/{conversation}', [UnifiedInboxController::class, 'show'])->name('show');
        Route::post('/{conversation}/send', [UnifiedInboxController::class, 'sendMessage'])->name('send');
    });

    // Channel Analytics routes
    Route::prefix('analytics/channels')->name('analytics.channels.')->group(function () {
        Route::get('/', [ChannelAnalyticsController::class, 'index'])->name('index');
        Route::post('/compare', [ChannelAnalyticsController::class, 'compare'])->name('compare');
    });

    // Sales routes (Lead management)
    Route::resource('sales', SalesController::class)->parameters([
        'sales' => 'lead'
    ]);

    // Sales API routes (Lazy Loading)
    Route::prefix('api/sales')->name('api.sales.')->group(function () {
        Route::get('/leads', [SalesController::class, 'getLeads'])->name('leads');
        Route::get('/stats', [SalesController::class, 'getStats'])->name('stats');
        Route::get('/operators', [SalesController::class, 'getOperators'])->name('operators');
        Route::post('/leads/{lead}/assign', [SalesController::class, 'assign'])->name('assign');
        Route::post('/leads/bulk-assign', [SalesController::class, 'bulkAssign'])->name('bulk-assign');
        Route::get('/operator-stats', [SalesController::class, 'getOperatorStats'])->name('operator-stats');
        Route::post('/check-duplicate', [SalesController::class, 'checkDuplicate'])->name('check-duplicate');
        // Analytics routes
        Route::get('/funnel-stats', [SalesController::class, 'getFunnelStats'])->name('funnel-stats');
        Route::get('/source-stats', [SalesController::class, 'getSourceStats'])->name('source-stats');
        Route::get('/lost-reasons-stats', [SalesController::class, 'getLostReasonsStats'])->name('lost-reasons-stats');
        Route::post('/leads/{lead}/mark-lost', [SalesController::class, 'markAsLost'])->name('mark-lost');
    });

    // Tasks routes (Vazifalar)
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update');
        Route::post('/{task}/complete', [TaskController::class, 'complete'])->name('complete');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        Route::get('/stats', [TaskController::class, 'stats'])->name('stats');
        Route::get('/lead/{lead}', [TaskController::class, 'leadTasks'])->name('lead');
    });

    // Todos routes (Todo List tizimi)
    Route::prefix('todos')->name('todos.')->group(function () {
        Route::get('/', [TodoController::class, 'index'])->name('index');
        Route::post('/', [TodoController::class, 'store'])->name('store');
        Route::get('/{todo}', [TodoController::class, 'show'])->name('show');
        Route::put('/{todo}', [TodoController::class, 'update'])->name('update');
        Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
        Route::post('/{todo}/toggle', [TodoController::class, 'toggleComplete'])->name('toggle');
        Route::post('/{todo}/toggle-user', [TodoController::class, 'toggleUserComplete'])->name('toggle-user');
        Route::post('/reorder', [TodoController::class, 'reorder'])->name('reorder');
        Route::get('/api/dashboard', [TodoController::class, 'dashboard'])->name('dashboard');

        // Subtasks
        Route::post('/{todo}/subtasks', [TodoController::class, 'addSubtask'])->name('subtasks.store');
        Route::put('/{todo}/subtasks/{subtask}', [TodoController::class, 'updateSubtask'])->name('subtasks.update');
        Route::post('/{todo}/subtasks/{subtask}/toggle', [TodoController::class, 'toggleSubtask'])->name('subtasks.toggle');
        Route::delete('/{todo}/subtasks/{subtask}', [TodoController::class, 'deleteSubtask'])->name('subtasks.destroy');

        // Recurrence
        Route::post('/{todo}/recurrence', [TodoController::class, 'addRecurrence'])->name('recurrence.store');
    });

    // Todo Recurrences routes
    Route::prefix('todo-recurrences')->name('todo-recurrences.')->group(function () {
        Route::put('/{recurrence}', [TodoController::class, 'updateRecurrence'])->name('update');
        Route::delete('/{recurrence}', [TodoController::class, 'deleteRecurrence'])->name('destroy');
        Route::post('/{recurrence}/pause', [TodoController::class, 'pauseRecurrence'])->name('pause');
        Route::post('/{recurrence}/resume', [TodoController::class, 'resumeRecurrence'])->name('resume');
    });

    // Todo Templates routes
    Route::prefix('todo-templates')->name('todo-templates.')->group(function () {
        Route::get('/', [TodoTemplateController::class, 'index'])->name('index');
        Route::get('/list', [TodoTemplateController::class, 'list'])->name('list');
        Route::post('/', [TodoTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [TodoTemplateController::class, 'show'])->name('show');
        Route::put('/{template}', [TodoTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [TodoTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/duplicate', [TodoTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{template}/apply', [TodoTemplateController::class, 'apply'])->name('apply');

        // Template Items
        Route::post('/{template}/items', [TodoTemplateController::class, 'addItem'])->name('items.store');
        Route::put('/{template}/items/{item}', [TodoTemplateController::class, 'updateItem'])->name('items.update');
        Route::delete('/{template}/items/{item}', [TodoTemplateController::class, 'deleteItem'])->name('items.destroy');
        Route::post('/{template}/items/reorder', [TodoTemplateController::class, 'reorderItems'])->name('items.reorder');
    });

    // Lead Forms routes
    Route::prefix('lead-forms')->name('lead-forms.')->group(function () {
        Route::get('/', [LeadFormController::class, 'index'])->name('index');
        Route::get('/create', [LeadFormController::class, 'create'])->name('create');
        Route::post('/', [LeadFormController::class, 'store'])->name('store');
        Route::get('/{leadForm}', [LeadFormController::class, 'show'])->name('show');
        Route::get('/{leadForm}/edit', [LeadFormController::class, 'edit'])->name('edit');
        Route::put('/{leadForm}', [LeadFormController::class, 'update'])->name('update');
        Route::delete('/{leadForm}', [LeadFormController::class, 'destroy'])->name('destroy');
        Route::post('/{leadForm}/toggle-status', [LeadFormController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{leadForm}/duplicate', [LeadFormController::class, 'duplicate'])->name('duplicate');
        Route::get('/{leadForm}/embed-code', [LeadFormController::class, 'getEmbedCode'])->name('embed-code');
    });

    // Offers routes
    Route::prefix('offers')->name('offers.')->group(function () {
        Route::get('/', [OffersController::class, 'index'])->name('index');
        Route::get('/create', [OffersController::class, 'create'])->name('create');
        Route::post('/', [OffersController::class, 'store'])->name('store');
        Route::post('/generate-ai', [OffersController::class, 'generateAI'])->name('generate-ai');
        Route::post('/generate-guarantee', [OffersController::class, 'generateGuarantee'])->name('generate-guarantee');
        Route::post('/calculate-value-score', [OffersController::class, 'calculateValueScore'])->name('calculate-value-score');
        Route::get('/{offer}', [OffersController::class, 'show'])->name('show');
        Route::get('/{offer}/edit', [OffersController::class, 'edit'])->name('edit');
        Route::put('/{offer}', [OffersController::class, 'update'])->name('update');
        Route::delete('/{offer}', [OffersController::class, 'destroy'])->name('destroy');
        Route::post('/{offer}/duplicate', [OffersController::class, 'duplicate'])->name('duplicate');
        Route::post('/{offer}/generate-variations', [OffersController::class, 'generateVariations'])->name('generate-variations');
    });

    // Sales Analytics routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        // Main Pages
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/funnel', [AnalyticsController::class, 'funnel'])->name('funnel');
        Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
        Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');

        // Lazy Loading API Endpoints
        Route::get('/api/initial', [AnalyticsController::class, 'getInitialData'])->name('api.initial');
        Route::get('/api/funnel-page', [AnalyticsController::class, 'getFunnelPageData'])->name('api.funnel-page');
        Route::get('/api/performance-page', [AnalyticsController::class, 'getPerformancePageData'])->name('api.performance-page');
        Route::get('/api/revenue-page', [AnalyticsController::class, 'getRevenuePageData'])->name('api.revenue-page');

        // AJAX Data Endpoints
        Route::post('/data/funnel', [AnalyticsController::class, 'getFunnelData'])->name('data.funnel');
        Route::post('/data/dream-buyer-performance', [AnalyticsController::class, 'getDreamBuyerPerformance'])->name('data.dream-buyer');
        Route::post('/data/offer-performance', [AnalyticsController::class, 'getOfferPerformance'])->name('data.offer');
        Route::post('/data/source-analysis', [AnalyticsController::class, 'getLeadSourceAnalysis'])->name('data.source');
        Route::post('/data/revenue-trends', [AnalyticsController::class, 'getRevenueTrends'])->name('data.revenue-trends');
        Route::post('/data/revenue-forecast', [AnalyticsController::class, 'getRevenueForecast'])->name('data.forecast');
        Route::post('/data/conversion-rates', [AnalyticsController::class, 'getConversionRates'])->name('data.conversion');
        Route::post('/data/dashboard-metrics', [AnalyticsController::class, 'getDashboardMetrics'])->name('data.dashboard');
        Route::post('/data/top-performers', [AnalyticsController::class, 'getTopPerformers'])->name('data.top-performers');

        // Export Endpoints
        Route::post('/export/pdf', [AnalyticsController::class, 'exportPDF'])->name('export.pdf');
        Route::post('/export/excel', [AnalyticsController::class, 'exportExcel'])->name('export.excel');
    });

    // Target Analysis routes
    Route::prefix('target-analysis')->name('target-analysis.')->group(function () {
        Route::get('/', [TargetAnalysisController::class, 'index'])->name('index');
        Route::get('/dream-buyer-match', [TargetAnalysisController::class, 'getDreamBuyerMatch'])->name('dream-buyer-match');
        Route::get('/segments', [TargetAnalysisController::class, 'getSegments'])->name('segments');
        Route::get('/growth-trends', [TargetAnalysisController::class, 'getGrowthTrends'])->name('growth-trends');
        Route::post('/insights/regenerate', [TargetAnalysisController::class, 'regenerateInsights'])->name('insights.regenerate');
        Route::get('/churn-risk', [TargetAnalysisController::class, 'getChurnRisk'])->name('churn-risk');
        Route::get('/export', [TargetAnalysisController::class, 'export'])->name('export');
        Route::get('/top-performers', [TargetAnalysisController::class, 'getTopPerformers'])->name('top-performers');
    });

    // Chatbot routes (internal AI chat)
    Route::prefix('chatbot')->name('chatbot.')->group(function () {
        Route::get('/', [ChatbotController::class, 'index'])->name('index');
        Route::post('/send', [ChatbotController::class, 'sendMessage'])->name('send');
        Route::delete('/clear', [ChatbotController::class, 'clearHistory'])->name('clear');
    });

    // Customer Chatbot Management routes
    Route::prefix('customer-bot')->name('customer-bot.')->group(function () {
        // Dashboard
        Route::get('/', [ChatbotManagementController::class, 'index'])->name('index');

        // Lazy Loading API Endpoint
        Route::get('/api/stats', [ChatbotManagementController::class, 'getDashboardStatsApi'])->name('api.stats');

        // Conversations
        Route::get('/conversations', [ChatbotManagementController::class, 'conversations'])->name('conversations');
        Route::get('/conversations/{conversation}', [ChatbotManagementController::class, 'conversation'])->name('conversation');
        Route::post('/conversations/{conversation}/close', [ChatbotManagementController::class, 'closeConversation'])->name('conversation.close');
        Route::post('/conversations/{conversation}/reopen', [ChatbotManagementController::class, 'reopenConversation'])->name('conversation.reopen');
        Route::post('/conversations/{conversation}/handoff', [ChatbotManagementController::class, 'handoffConversation'])->name('conversation.handoff');

        // Settings
        Route::get('/settings', [ChatbotManagementController::class, 'settings'])->name('settings');
        Route::put('/settings', [ChatbotManagementController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/telegram/webhook', [ChatbotManagementController::class, 'setupTelegramWebhook'])->name('settings.telegram.webhook');
        Route::get('/settings/telegram/info', [ChatbotManagementController::class, 'getTelegramBotInfo'])->name('settings.telegram.info');

        // Knowledge Base
        Route::get('/knowledge-base', [ChatbotManagementController::class, 'knowledgeBase'])->name('knowledge-base');
        Route::post('/knowledge-base', [ChatbotManagementController::class, 'storeKnowledge'])->name('knowledge-base.store');
        Route::put('/knowledge-base/{knowledge}', [ChatbotManagementController::class, 'updateKnowledge'])->name('knowledge-base.update');
        Route::delete('/knowledge-base/{knowledge}', [ChatbotManagementController::class, 'destroyKnowledge'])->name('knowledge-base.destroy');

        // Templates
        Route::get('/templates', [ChatbotManagementController::class, 'templates'])->name('templates');
        Route::post('/templates', [ChatbotManagementController::class, 'storeTemplate'])->name('templates.store');
        Route::put('/templates/{template}', [ChatbotManagementController::class, 'updateTemplate'])->name('templates.update');
        Route::delete('/templates/{template}', [ChatbotManagementController::class, 'destroyTemplate'])->name('templates.destroy');
    });

    // Telegram Funnel Builder routes
    Route::prefix('telegram-funnels')->name('telegram-funnels.')->group(function () {
        // Bot management
        Route::get('/', [TelegramBotManagementController::class, 'index'])->name('index');
        Route::get('/create', [TelegramBotManagementController::class, 'create'])->name('create');
        Route::post('/', [TelegramBotManagementController::class, 'store'])->name('store');
        Route::get('/{bot}', [TelegramBotManagementController::class, 'show'])->name('show');
        Route::put('/{bot}', [TelegramBotManagementController::class, 'update'])->name('update');
        Route::delete('/{bot}', [TelegramBotManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{bot}/toggle-active', [TelegramBotManagementController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{bot}/setup-webhook', [TelegramBotManagementController::class, 'setupWebhook'])->name('setup-webhook');
        Route::get('/{bot}/stats', [TelegramBotManagementController::class, 'stats'])->name('stats');

        // Funnels for bot
        Route::prefix('{bot}/funnels')->name('funnels.')->group(function () {
            Route::get('/', [TelegramFunnelController::class, 'index'])->name('index');
            Route::post('/', [TelegramFunnelController::class, 'store'])->name('store');
            Route::get('/{funnel}', [TelegramFunnelController::class, 'show'])->name('show');
            Route::put('/{funnel}', [TelegramFunnelController::class, 'update'])->name('update');
            Route::delete('/{funnel}', [TelegramFunnelController::class, 'destroy'])->name('destroy');
            Route::post('/{funnel}/toggle-active', [TelegramFunnelController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{funnel}/duplicate', [TelegramFunnelController::class, 'duplicate'])->name('duplicate');
            Route::post('/{funnel}/save-steps', [TelegramFunnelController::class, 'saveSteps'])->name('save-steps');
        });

        // Triggers for bot
        Route::prefix('{bot}/triggers')->name('triggers.')->group(function () {
            Route::get('/', [TelegramTriggerController::class, 'index'])->name('index');
            Route::post('/', [TelegramTriggerController::class, 'store'])->name('store');
            Route::put('/{trigger}', [TelegramTriggerController::class, 'update'])->name('update');
            Route::delete('/{trigger}', [TelegramTriggerController::class, 'destroy'])->name('destroy');
            Route::post('/{trigger}/toggle-active', [TelegramTriggerController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/test', [TelegramTriggerController::class, 'test'])->name('test');
        });

        // Broadcasts for bot
        Route::prefix('{bot}/broadcasts')->name('broadcasts.')->group(function () {
            Route::get('/', [TelegramBroadcastController::class, 'index'])->name('index');
            Route::get('/create', [TelegramBroadcastController::class, 'create'])->name('create');
            Route::post('/', [TelegramBroadcastController::class, 'store'])->name('store');
            Route::get('/{broadcast}', [TelegramBroadcastController::class, 'show'])->name('show');
            Route::put('/{broadcast}', [TelegramBroadcastController::class, 'update'])->name('update');
            Route::delete('/{broadcast}', [TelegramBroadcastController::class, 'destroy'])->name('destroy');
            Route::post('/{broadcast}/start', [TelegramBroadcastController::class, 'start'])->name('start');
            Route::post('/{broadcast}/pause', [TelegramBroadcastController::class, 'pause'])->name('pause');
            Route::post('/{broadcast}/resume', [TelegramBroadcastController::class, 'resume'])->name('resume');
            Route::post('/{broadcast}/cancel', [TelegramBroadcastController::class, 'cancel'])->name('cancel');
            Route::post('/preview-recipients', [TelegramBroadcastController::class, 'previewRecipients'])->name('preview-recipients');
        });

        // Users for bot
        Route::prefix('{bot}/users')->name('users.')->group(function () {
            Route::get('/', [TelegramUserController::class, 'index'])->name('index');
            Route::get('/export', [TelegramUserController::class, 'export'])->name('export');
            Route::get('/{user}', [TelegramUserController::class, 'show'])->name('show');
            Route::put('/{user}', [TelegramUserController::class, 'update'])->name('update');
            Route::post('/{user}/add-tag', [TelegramUserController::class, 'addTag'])->name('add-tag');
            Route::post('/{user}/remove-tag', [TelegramUserController::class, 'removeTag'])->name('remove-tag');
            Route::post('/{user}/reset-state', [TelegramUserController::class, 'resetState'])->name('reset-state');
            Route::post('/bulk-add-tags', [TelegramUserController::class, 'bulkAddTags'])->name('bulk-add-tags');
        });

        // Conversations for bot
        Route::prefix('{bot}/conversations')->name('conversations.')->group(function () {
            Route::get('/', [TelegramConversationController::class, 'index'])->name('index');
            Route::get('/{conversation}', [TelegramConversationController::class, 'show'])->name('show');
            Route::post('/{conversation}/send', [TelegramConversationController::class, 'sendMessage'])->name('send');
            Route::post('/{conversation}/assign', [TelegramConversationController::class, 'assign'])->name('assign');
            Route::post('/{conversation}/close', [TelegramConversationController::class, 'close'])->name('close');
            Route::post('/{conversation}/reopen', [TelegramConversationController::class, 'reopen'])->name('reopen');
            Route::post('/{conversation}/add-tag', [TelegramConversationController::class, 'addTag'])->name('add-tag');
            Route::post('/{conversation}/remove-tag', [TelegramConversationController::class, 'removeTag'])->name('remove-tag');
            Route::get('/{conversation}/messages', [TelegramConversationController::class, 'getNewMessages'])->name('messages');
        });
    });

    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');

        // Algorithmic Reports (non-AI)
        Route::get('/algorithmic', [ReportsController::class, 'algorithmicReports'])->name('algorithmic');
        Route::post('/generate', [ReportsController::class, 'generateReport'])->name('generate');
        Route::get('/realtime', [ReportsController::class, 'getRealtime'])->name('realtime');
        Route::get('/list', [ReportsController::class, 'getReports'])->name('list');
        Route::get('/{id}', [ReportsController::class, 'showReport'])->name('show');
        Route::get('/{id}/data', [ReportsController::class, 'getReportData'])->name('data');
        Route::delete('/{id}', [ReportsController::class, 'deleteReport'])->name('delete');

        // Report Schedules
        Route::post('/schedules', [ReportsController::class, 'saveSchedule'])->name('schedules.save');
        Route::post('/schedules/{id}/toggle', [ReportsController::class, 'toggleSchedule'])->name('schedules.toggle');
        Route::delete('/schedules/{id}', [ReportsController::class, 'deleteSchedule'])->name('schedules.delete');
    });

    // KPI routes
    Route::get('/kpi', [DashboardController::class, 'kpi'])->name('kpi');
    Route::get('/kpi/data-entry', [DashboardController::class, 'kpiDataEntry'])->name('kpi.data-entry');
    Route::post('/kpi/calculate-plan', [DashboardController::class, 'calculateKPIPlan'])->name('kpi.calculate-plan');
    Route::post('/kpi/save-plan', [DashboardController::class, 'saveKPIPlan'])->name('kpi.save-plan');

    // Settings routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::put('/preferences', [SettingsController::class, 'updateSettings'])->name('preferences.update');
        Route::put('/api-keys', [SettingsController::class, 'updateApiKeys'])->name('api-keys.update');
        Route::delete('/api-keys', [SettingsController::class, 'deleteApiKey'])->name('api-keys.delete');

        // WhatsApp Integration
        Route::get('/whatsapp', [SettingsController::class, 'whatsapp'])->name('whatsapp');
        Route::get('/whatsapp-ai', [SettingsController::class, 'whatsappAI'])->name('whatsapp-ai');

        // Instagram Integration
        Route::get('/instagram-ai', [SettingsController::class, 'instagramAI'])->name('instagram-ai');

        // Google Ads Integration
        Route::get('/google-ads', [SettingsController::class, 'googleAds'])->name('google-ads');
        Route::post('/google-ads/connect', [SettingsController::class, 'connectGoogleAds'])->name('google-ads.connect');
        Route::post('/google-ads/disconnect', [SettingsController::class, 'disconnectGoogleAds'])->name('google-ads.disconnect');
        Route::get('/google-ads/callback', [SettingsController::class, 'googleAdsCallback'])->name('google-ads.callback');

        // Yandex Direct Integration
        Route::get('/yandex-direct', [SettingsController::class, 'yandexDirect'])->name('yandex-direct');
        Route::post('/yandex-direct/connect', [SettingsController::class, 'connectYandexDirect'])->name('yandex-direct.connect');
        Route::post('/yandex-direct/disconnect', [SettingsController::class, 'disconnectYandexDirect'])->name('yandex-direct.disconnect');
        Route::get('/yandex-direct/callback', [SettingsController::class, 'yandexDirectCallback'])->name('yandex-direct.callback');

        // YouTube Analytics Integration
        Route::get('/youtube', [SettingsController::class, 'youtube'])->name('youtube');
        Route::post('/youtube/connect', [SettingsController::class, 'connectYoutube'])->name('youtube.connect');
        Route::post('/youtube/disconnect', [SettingsController::class, 'disconnectYoutube'])->name('youtube.disconnect');
        Route::get('/youtube/callback', [SettingsController::class, 'youtubeCallback'])->name('youtube.callback');

        // SMS Integration (Eskiz & PlayMobile)
        Route::get('/sms', [SmsController::class, 'settings'])->name('sms');
        // Eskiz
        Route::post('/sms/connect', [SmsController::class, 'connect'])->name('sms.connect');
        Route::post('/sms/disconnect', [SmsController::class, 'disconnect'])->name('sms.disconnect');
        Route::post('/sms/refresh-balance', [SmsController::class, 'refreshBalance'])->name('sms.refresh-balance');
        // PlayMobile
        Route::post('/sms/playmobile/connect', [SmsController::class, 'connectPlaymobile'])->name('sms.playmobile.connect');
        Route::post('/sms/playmobile/disconnect', [SmsController::class, 'disconnectPlaymobile'])->name('sms.playmobile.disconnect');

        // Telephony Integration (PBX & SipUni)
        Route::get('/telephony', [TelephonyController::class, 'settings'])->name('telephony');
        // PBX
        Route::post('/telephony/pbx/connect', [TelephonyController::class, 'connectPbx'])->name('telephony.pbx.connect');
        Route::post('/telephony/pbx/disconnect', [TelephonyController::class, 'disconnectPbx'])->name('telephony.pbx.disconnect');
        // SipUni
        Route::post('/telephony/sipuni/connect', [TelephonyController::class, 'connectSipuni'])->name('telephony.sipuni.connect');
        Route::post('/telephony/sipuni/disconnect', [TelephonyController::class, 'disconnectSipuni'])->name('telephony.sipuni.disconnect');
        // OnlinePBX
        Route::post('/telephony/onlinepbx/connect', [TelephonyController::class, 'connectOnlinePbx'])->name('telephony.onlinepbx.connect');
        Route::post('/telephony/onlinepbx/sync', [TelephonyController::class, 'syncOnlinePbxHistory'])->name('telephony.onlinepbx.sync');

        // Payment Integration (Payme & Click)
        Route::get('/payments', [PaymentController::class, 'settings'])->name('payments');
        // Payme
        Route::post('/payments/payme/connect', [PaymentController::class, 'connectPayme'])->name('payments.payme.connect');
        Route::post('/payments/payme/disconnect', [PaymentController::class, 'disconnectPayme'])->name('payments.payme.disconnect');
        // Click
        Route::post('/payments/click/connect', [PaymentController::class, 'connectClick'])->name('payments.click.connect');
        Route::post('/payments/click/disconnect', [PaymentController::class, 'disconnectClick'])->name('payments.click.disconnect');

        // 2FA routes
        Route::get('/two-factor', [TwoFactorAuthController::class, 'show'])->name('two-factor');
        Route::get('/two-factor/setup', [TwoFactorAuthController::class, 'setup'])->name('two-factor.setup');
        Route::post('/two-factor/enable', [TwoFactorAuthController::class, 'enable'])->name('two-factor.enable');
        Route::post('/two-factor/disable', [TwoFactorAuthController::class, 'disable'])->name('two-factor.disable');
        Route::get('/two-factor/recovery-codes', [TwoFactorAuthController::class, 'showRecoveryCodes'])->name('two-factor.recovery-codes');
        Route::post('/two-factor/recovery-codes/regenerate', [TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes.regenerate');

        // Team Management routes
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::post('/add', [TeamController::class, 'invite'])->name('invite');
            Route::put('/{member}', [TeamController::class, 'update'])->name('update');
            Route::delete('/{member}', [TeamController::class, 'remove'])->name('remove');
            Route::post('/{member}/reset-password', [TeamController::class, 'resetPassword'])->name('reset-password');
        });
    });

    // SMS routes
    Route::prefix('sms')->name('sms.')->group(function () {
        // Status check
        Route::get('/status', [SmsController::class, 'status'])->name('status');

        // History & Statistics
        Route::get('/history', [SmsController::class, 'history'])->name('history');
        Route::get('/statistics', [SmsController::class, 'statistics'])->name('statistics');

        // Send SMS to lead
        Route::post('/send/{lead}', [SmsController::class, 'sendToLead'])->name('send');
        Route::get('/lead/{lead}/history', [SmsController::class, 'leadHistory'])->name('lead.history');

        // Bulk SMS
        Route::post('/bulk-send', [SmsController::class, 'bulkSend'])->name('bulk-send');

        // Calculate SMS parts
        Route::post('/calculate-parts', [SmsController::class, 'calculateParts'])->name('calculate-parts');

        // Templates
        Route::get('/templates', [SmsController::class, 'templates'])->name('templates');
        Route::post('/templates', [SmsController::class, 'storeTemplate'])->name('templates.store');
        Route::put('/templates/{template}', [SmsController::class, 'updateTemplate'])->name('templates.update');
        Route::delete('/templates/{template}', [SmsController::class, 'destroyTemplate'])->name('templates.destroy');
        Route::post('/templates/{template}/preview', [SmsController::class, 'previewTemplate'])->name('templates.preview');
    });

    // Telephony routes
    Route::prefix('telephony')->name('telephony.')->group(function () {
        // Status check
        Route::get('/status', [TelephonyController::class, 'status'])->name('status');

        // History & Statistics
        Route::get('/history', [TelephonyController::class, 'history'])->name('history');
        Route::get('/statistics', [TelephonyController::class, 'statistics'])->name('statistics');

        // Make calls
        Route::post('/call', [TelephonyController::class, 'makeCall'])->name('call');
        Route::post('/call/{lead}', [TelephonyController::class, 'callLead'])->name('call.lead');
        Route::get('/lead/{lead}/history', [TelephonyController::class, 'leadCallHistory'])->name('lead.history');

        // Balance
        Route::post('/refresh-balance', [TelephonyController::class, 'refreshBalance'])->name('refresh-balance');
    });

    // Payment routes (Payme & Click)
    Route::prefix('payments')->name('payments.')->group(function () {
        // Get available providers
        Route::get('/providers', [PaymentController::class, 'getProviders'])->name('providers');

        // Transactions
        Route::get('/transactions', [PaymentController::class, 'transactions'])->name('transactions');

        // Create payment link for lead
        Route::post('/lead/{lead}/create-link', [PaymentController::class, 'createPaymentLink'])->name('lead.create-link');

        // Get lead transactions
        Route::get('/lead/{lead}/transactions', [PaymentController::class, 'getLeadTransactions'])->name('lead.transactions');

        // Cancel transaction
        Route::post('/transactions/{transaction}/cancel', [PaymentController::class, 'cancelTransaction'])->name('transactions.cancel');

        // Success page
        Route::get('/success', [PaymentController::class, 'success'])->name('success');
    });

    // Activity Logs routes
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/stats', [ActivityLogController::class, 'stats'])->name('stats');
        Route::get('/export', [ActivityLogController::class, 'export'])->name('export');
        Route::post('/clean', [ActivityLogController::class, 'clean'])->name('clean')->middleware('can:manage:settings');
        Route::get('/{log}', [ActivityLogController::class, 'show'])->name('show');
    });

    // SWOT Analysis routes
    Route::prefix('swot')->name('swot.')->group(function () {
        Route::get('/', [CompetitorController::class, 'swotIndex'])->name('index');
        Route::post('/generate', [CompetitorController::class, 'generateBusinessSwot'])->name('generate');
        Route::post('/save', [CompetitorController::class, 'saveBusinessSwot'])->name('save');
    });

    // Competitor Intelligence routes
    Route::prefix('competitors')->name('competitors.')->group(function () {
        Route::get('/', [CompetitorController::class, 'index'])->name('index');
        Route::get('/dashboard', [CompetitorController::class, 'dashboard'])->name('dashboard');
        Route::post('/', [CompetitorController::class, 'store'])->name('store');
        Route::get('/alerts', [CompetitorController::class, 'alerts'])->name('alerts');
        Route::post('/alerts/{alert}/read', [CompetitorController::class, 'markAlertRead'])->name('alerts.read');
        Route::post('/alerts/{alert}/archive', [CompetitorController::class, 'archiveAlert'])->name('alerts.archive');
        Route::get('/{competitor}', [CompetitorController::class, 'show'])->name('show');
        Route::get('/{competitor}/edit', [CompetitorController::class, 'edit'])->name('edit');
        Route::put('/{competitor}', [CompetitorController::class, 'update'])->name('update');
        Route::delete('/{competitor}', [CompetitorController::class, 'destroy'])->name('destroy');
        Route::post('/{competitor}/metrics', [CompetitorController::class, 'recordMetrics'])->name('metrics.record');
        Route::post('/{competitor}/monitor', [CompetitorController::class, 'monitor'])->name('monitor');
        Route::post('/{competitor}/swot', [CompetitorController::class, 'generateSwot'])->name('swot.generate');

        // Marketing Intelligence API routes
        Route::post('/{competitor}/products', [CompetitorController::class, 'addProduct'])->name('products.store');
        Route::delete('/{competitor}/products/{product}', [CompetitorController::class, 'deleteProduct'])->name('products.destroy');
        Route::post('/{competitor}/ads', [CompetitorController::class, 'addAd'])->name('ads.store');
        Route::delete('/{competitor}/ads/{ad}', [CompetitorController::class, 'deleteAd'])->name('ads.destroy');
        Route::post('/{competitor}/review-sources', [CompetitorController::class, 'addReviewSource'])->name('review-sources.store');
        Route::delete('/{competitor}/review-sources/{source}', [CompetitorController::class, 'deleteReviewSource'])->name('review-sources.destroy');
        Route::post('/{competitor}/analyze-content', [CompetitorController::class, 'analyzeContent'])->name('content.analyze');
        Route::post('/{competitor}/scan-ads', [CompetitorController::class, 'scanAds'])->name('ads.scan');
        Route::post('/{competitor}/scan-reviews', [CompetitorController::class, 'scanReviews'])->name('reviews.scan');

        // Lazy Loading API Endpoints
        Route::get('/api/insights', [CompetitorController::class, 'getInsights'])->name('api.insights');
        Route::get('/api/dashboard-data', [CompetitorController::class, 'getDashboardData'])->name('api.dashboard-data');
    });

    // Target Analysis API routes
    Route::prefix('api/target-analysis')->name('api.target-analysis.')->group(function () {
        Route::get('/', [TargetAnalysisController::class, 'getAnalysisData'])->name('data');
        Route::get('/dream-buyer-match', [TargetAnalysisController::class, 'getDreamBuyerMatch'])->name('dream-buyer-match');
        Route::get('/segments', [TargetAnalysisController::class, 'getSegments'])->name('segments');
        Route::get('/growth-trends', [TargetAnalysisController::class, 'getGrowthTrends'])->name('growth-trends');
        Route::post('/insights/regenerate', [TargetAnalysisController::class, 'regenerateInsights'])->name('insights.regenerate');
        Route::get('/churn-risk', [TargetAnalysisController::class, 'getChurnRisk'])->name('churn-risk');
        Route::get('/export', [TargetAnalysisController::class, 'export'])->name('export');
        Route::get('/top-performers', [TargetAnalysisController::class, 'getTopPerformers'])->name('top-performers');
    });

    // Meta Ads Integration routes (inside target-analysis)
    Route::prefix('target-analysis/meta')->name('target-analysis.meta.')->group(function () {
        Route::get('/auth-url', [TargetAnalysisController::class, 'getMetaAuthUrl'])->name('auth-url');
        Route::get('/callback', [TargetAnalysisController::class, 'handleMetaCallback'])->name('callback');
        Route::post('/disconnect', [TargetAnalysisController::class, 'disconnectMeta'])->name('disconnect');
        Route::post('/sync', [TargetAnalysisController::class, 'syncMeta'])->name('sync');
        Route::post('/refresh', [TargetAnalysisController::class, 'refreshMeta'])->name('refresh');
        Route::post('/select-account', [TargetAnalysisController::class, 'selectMetaAccount'])->name('select-account');
    });

    // Meta Ads API routes (inside target-analysis)
    Route::prefix('api/target-analysis/meta')->name('api.target-analysis.meta.')->group(function () {
        Route::get('/overview', [TargetAnalysisController::class, 'getMetaOverview'])->name('overview');
        Route::get('/campaigns', [TargetAnalysisController::class, 'getMetaCampaigns'])->name('campaigns');
        Route::get('/demographics', [TargetAnalysisController::class, 'getMetaDemographics'])->name('demographics');
        Route::get('/placements', [TargetAnalysisController::class, 'getMetaPlacements'])->name('placements');
        Route::get('/trend', [TargetAnalysisController::class, 'getMetaTrend'])->name('trend');
        Route::post('/ai-insights', [TargetAnalysisController::class, 'getMetaAIInsights'])->name('ai-insights');
    });

    // Meta Campaigns page routes
    Route::prefix('meta-campaigns')->name('meta-campaigns.')->group(function () {
        Route::get('/{id}', [MetaCampaignController::class, 'showPage'])->name('show');
    });

    // Meta Campaigns API routes (paginated campaigns list)
    Route::prefix('api/meta-campaigns')->name('api.meta-campaigns.')->group(function () {
        Route::get('/', [MetaCampaignController::class, 'index'])->name('index');
        Route::get('/filters', [MetaCampaignController::class, 'filters'])->name('filters');
        Route::get('/{id}', [MetaCampaignController::class, 'show'])->name('show');
        Route::get('/{id}/adsets', [MetaCampaignController::class, 'getAdSets'])->name('adsets');
        Route::get('/{id}/ads', [MetaCampaignController::class, 'getAds'])->name('ads');
        Route::post('/sync', [MetaCampaignController::class, 'sync'])->name('sync');
    });

    // Instagram Analysis routes
    Route::prefix('instagram-analysis')->name('instagram-analysis.')->group(function () {
        Route::get('/', [InstagramAnalysisController::class, 'index'])->name('index');
        Route::post('/select-account', [InstagramAnalysisController::class, 'selectAccount'])->name('select-account');
        Route::post('/sync', [InstagramAnalysisController::class, 'sync'])->name('sync');
        Route::get('/check-permissions', [InstagramAnalysisController::class, 'checkPermissions'])->name('check-permissions');
    });

    // Instagram Analysis API routes
    Route::prefix('api/instagram-analysis')->name('api.instagram-analysis.')->group(function () {
        Route::get('/overview', [InstagramAnalysisController::class, 'getOverview'])->name('overview');
        Route::get('/media-performance', [InstagramAnalysisController::class, 'getMediaPerformance'])->name('media-performance');
        Route::get('/reels-analytics', [InstagramAnalysisController::class, 'getReelsAnalytics'])->name('reels-analytics');
        Route::get('/engagement', [InstagramAnalysisController::class, 'getEngagementAnalytics'])->name('engagement');
        Route::get('/audience', [InstagramAnalysisController::class, 'getAudienceDemographics'])->name('audience');
        Route::get('/hashtags', [InstagramAnalysisController::class, 'getHashtagPerformance'])->name('hashtags');
        Route::get('/growth-trend', [InstagramAnalysisController::class, 'getGrowthTrend'])->name('growth-trend');
        Route::get('/content-comparison', [InstagramAnalysisController::class, 'getContentComparison'])->name('content-comparison');
        Route::post('/ai-insights', [InstagramAnalysisController::class, 'getAIInsights'])->name('ai-insights');

        // Business Insights API - amaliy tavsiyalar
        Route::get('/business-insights', [InstagramAnalysisController::class, 'getBusinessInsights'])->name('business-insights');
        Route::get('/content-winners', [InstagramAnalysisController::class, 'getContentWinners'])->name('content-winners');
        Route::get('/growth-drivers', [InstagramAnalysisController::class, 'getGrowthDrivers'])->name('growth-drivers');
        Route::get('/viral-analysis', [InstagramAnalysisController::class, 'getViralAnalysis'])->name('viral-analysis');
    });

    // YouTube Analytics routes
    Route::prefix('youtube-analytics')->name('youtube-analytics.')->group(function () {
        Route::get('/', [YouTubeAnalyticsController::class, 'index'])->name('index');
        Route::post('/sync', [YouTubeAnalyticsController::class, 'sync'])->name('sync');
        Route::get('/video/{videoId}', [YouTubeAnalyticsController::class, 'videoDetail'])->name('video');
    });

    // Google Ads Analytics routes
    Route::prefix('google-ads-analytics')->name('google-ads-analytics.')->group(function () {
        Route::get('/', [GoogleAdsAnalyticsController::class, 'index'])->name('index');
    });

    // Google Ads Campaigns page routes
    Route::prefix('google-ads-campaigns')->name('google-ads-campaigns.')->group(function () {
        Route::get('/{id}', [GoogleAdsCampaignController::class, 'showPage'])->name('show');
    });

    // Google Ads Campaigns API routes
    Route::prefix('api/google-ads-campaigns')->name('api.google-ads-campaigns.')->group(function () {
        Route::get('/', [GoogleAdsCampaignController::class, 'index'])->name('index');
        Route::get('/filters', [GoogleAdsCampaignController::class, 'filters'])->name('filters');
        Route::post('/', [GoogleAdsCampaignController::class, 'store'])->name('store');
        Route::get('/{id}', [GoogleAdsCampaignController::class, 'show'])->name('show');
        Route::put('/{id}', [GoogleAdsCampaignController::class, 'update'])->name('update');
        Route::patch('/{id}/status', [GoogleAdsCampaignController::class, 'updateStatus'])->name('status');
        Route::delete('/{id}', [GoogleAdsCampaignController::class, 'destroy'])->name('destroy');
        Route::post('/sync', [GoogleAdsCampaignController::class, 'sync'])->name('sync');
        Route::get('/{id}/insights', [GoogleAdsCampaignController::class, 'getInsights'])->name('insights');
        Route::get('/{id}/ad-groups', [GoogleAdsCampaignController::class, 'getAdGroups'])->name('ad-groups');
        Route::get('/ad-groups/{adGroupId}/keywords', [GoogleAdsCampaignController::class, 'getKeywords'])->name('keywords');
        Route::post('/ad-groups/{adGroupId}/keywords', [GoogleAdsCampaignController::class, 'addKeywords'])->name('keywords.add');
        Route::delete('/keywords/{keywordId}', [GoogleAdsCampaignController::class, 'removeKeyword'])->name('keywords.remove');
    });

    // Instagram Chatbot routes
    Route::prefix('instagram-chatbot')->name('instagram-chatbot.')->group(function () {
        Route::get('/', [InstagramChatbotController::class, 'index'])->name('index');
    });

    // Instagram Chatbot API routes
    Route::prefix('api/instagram-chatbot')->name('api.instagram-chatbot.')->group(function () {
        Route::get('/dashboard', [InstagramChatbotController::class, 'getDashboard'])->name('dashboard');
        Route::get('/automations', [InstagramChatbotController::class, 'getAutomations'])->name('automations');
        Route::post('/automations', [InstagramChatbotController::class, 'createAutomation'])->name('automations.create');
        Route::put('/automations/{id}', [InstagramChatbotController::class, 'updateAutomation'])->name('automations.update');
        Route::delete('/automations/{id}', [InstagramChatbotController::class, 'deleteAutomation'])->name('automations.delete');
        Route::post('/automations/{id}/toggle', [InstagramChatbotController::class, 'toggleAutomation'])->name('automations.toggle');
        Route::get('/conversations', [InstagramChatbotController::class, 'getConversations'])->name('conversations');
        Route::get('/conversations/{id}', [InstagramChatbotController::class, 'getConversation'])->name('conversations.show');
        Route::post('/conversations/{id}/message', [InstagramChatbotController::class, 'sendMessage'])->name('conversations.message');
        Route::get('/trigger-types', [InstagramChatbotController::class, 'getTriggerTypes'])->name('trigger-types');
        Route::get('/action-types', [InstagramChatbotController::class, 'getActionTypes'])->name('action-types');
        Route::get('/quick-replies', [InstagramChatbotController::class, 'getQuickReplies'])->name('quick-replies');
        Route::post('/quick-replies', [InstagramChatbotController::class, 'createQuickReply'])->name('quick-replies.create');

        // Flow Builder API routes
        Route::get('/node-types', [InstagramChatbotController::class, 'getNodeTypes'])->name('node-types');
        Route::get('/templates', [InstagramChatbotController::class, 'getTemplates'])->name('templates');
        Route::post('/flow-automations', [InstagramChatbotController::class, 'createFlowAutomation'])->name('flow-automations.create');
        Route::get('/flow-automations/{id}', [InstagramChatbotController::class, 'getFlowAutomation'])->name('flow-automations.show');
        Route::put('/flow-automations/{id}', [InstagramChatbotController::class, 'updateFlowAutomation'])->name('flow-automations.update');
    });

    // Algorithm Engine routes (Predictive Analytics without AI)
    Route::prefix('algorithm')->name('algorithm.')->group(function () {
        Route::get('/', [AlgorithmController::class, 'showDashboard'])->name('dashboard');
        Route::get('/api/modules', [AlgorithmController::class, 'analyzeModules'])->name('api.modules');
        Route::get('/api/modules/{module}', [AlgorithmController::class, 'analyzeModule'])->name('api.module');
        Route::get('/api/predictions', [AlgorithmController::class, 'predictNextSteps'])->name('api.predictions');
        Route::get('/api/quick-wins', [AlgorithmController::class, 'getQuickWins'])->name('api.quick-wins');
        Route::get('/api/critical', [AlgorithmController::class, 'getCriticalActions'])->name('api.critical');
        Route::get('/api/data-audit', [AlgorithmController::class, 'auditDataAccuracy'])->name('api.data-audit');
        Route::get('/api/full', [AlgorithmController::class, 'dashboard'])->name('api.full');
        Route::post('/api/refresh', [AlgorithmController::class, 'refreshCache'])->name('api.refresh');
    });

    // Strategy Building routes (FAZA 3)
    Route::prefix('strategy')->name('strategy.')->group(function () {
        // Dashboard
        Route::get('/', [StrategyController::class, 'index'])->name('index');
        Route::get('/wizard', [StrategyController::class, 'wizard'])->name('wizard');
        Route::post('/build-complete', [StrategyController::class, 'buildComplete'])->name('build-complete');
        Route::get('/templates', [StrategyController::class, 'templates'])->name('templates');

        // Annual Strategy
        Route::prefix('annual')->name('annual.')->group(function () {
            Route::post('/', [StrategyController::class, 'createAnnual'])->name('store');
            Route::get('/{annual}', [StrategyController::class, 'showAnnual'])->name('show');
            Route::put('/{annual}', [StrategyController::class, 'updateAnnual'])->name('update');
            Route::post('/{annual}/generate-quarters', [StrategyController::class, 'generateQuarters'])->name('generate-quarters');
        });

        // Quarterly Plan
        Route::prefix('quarterly')->name('quarterly.')->group(function () {
            Route::get('/{quarterly}', [StrategyController::class, 'showQuarterly'])->name('show');
            Route::put('/{quarterly}', [StrategyController::class, 'updateQuarterly'])->name('update');
            Route::post('/{quarterly}/generate-months', [StrategyController::class, 'generateMonths'])->name('generate-months');
        });

        // Monthly Plan
        Route::prefix('monthly')->name('monthly.')->group(function () {
            Route::get('/{monthly}', [StrategyController::class, 'showMonthly'])->name('show');
            Route::put('/{monthly}', [StrategyController::class, 'updateMonthly'])->name('update');
            Route::post('/{monthly}/generate-weeks', [StrategyController::class, 'generateWeeks'])->name('generate-weeks');
        });

        // Weekly Plan
        Route::prefix('weekly')->name('weekly.')->group(function () {
            Route::get('/{weekly}', [StrategyController::class, 'showWeekly'])->name('show');
            Route::put('/{weekly}', [StrategyController::class, 'updateWeekly'])->name('update');
            Route::post('/{weekly}/tasks', [StrategyController::class, 'addTask'])->name('add-task');
            Route::post('/{weekly}/tasks/{taskId}/complete', [StrategyController::class, 'completeTask'])->name('complete-task');
        });

        // Approve and Complete actions
        Route::post('/{type}/{id}/approve', [StrategyController::class, 'approve'])->name('approve');
        Route::post('/{type}/{id}/complete', [StrategyController::class, 'complete'])->name('complete');

        // KPI routes
        Route::get('/{type}/{id}/kpis', [StrategyController::class, 'getKPIs'])->name('kpis');
        Route::put('/kpi/{kpi}', [StrategyController::class, 'updateKPI'])->name('kpi.update');

        // Budget routes
        Route::get('/{type}/{id}/budget', [StrategyController::class, 'getBudget'])->name('budget');
        Route::post('/budget/{allocation}/spending', [StrategyController::class, 'recordSpending'])->name('budget.spending');
    });

    // Content Calendar routes (FAZA 3)
    Route::prefix('content-calendar')->name('content-calendar.')->group(function () {
        Route::get('/', [ContentCalendarController::class, 'index'])->name('index');
        Route::post('/', [ContentCalendarController::class, 'store'])->name('store');
        Route::get('/analytics', [ContentCalendarController::class, 'analytics'])->name('analytics');

        // Lazy Loading API Endpoint
        Route::get('/api/calendar', [ContentCalendarController::class, 'getCalendarData'])->name('api.calendar');

        // Content item actions
        Route::get('/{content}', [ContentCalendarController::class, 'show'])->name('show');
        Route::put('/{content}', [ContentCalendarController::class, 'update'])->name('update');
        Route::delete('/{content}', [ContentCalendarController::class, 'destroy'])->name('destroy');
        Route::post('/{content}/move', [ContentCalendarController::class, 'move'])->name('move');
        Route::post('/{content}/duplicate', [ContentCalendarController::class, 'duplicate'])->name('duplicate');
        Route::post('/{content}/approve', [ContentCalendarController::class, 'approve'])->name('approve');
        Route::post('/{content}/schedule', [ContentCalendarController::class, 'schedule'])->name('schedule');
        Route::post('/{content}/publish', [ContentCalendarController::class, 'publish'])->name('publish');
        Route::put('/{content}/metrics', [ContentCalendarController::class, 'updateMetrics'])->name('metrics');
        Route::post('/{content}/generate-ai', [ContentCalendarController::class, 'generateAI'])->name('generate-ai');

        // Bulk actions
        Route::post('/bulk-status', [ContentCalendarController::class, 'bulkUpdateStatus'])->name('bulk-status');

        // Generate from plans
        Route::post('/generate-monthly/{monthly}', [ContentCalendarController::class, 'generateMonthly'])->name('generate-monthly');
        Route::post('/generate-weekly/{weekly}', [ContentCalendarController::class, 'generateWeekly'])->name('generate-weekly');
    });

    // ============================================
    // FAZA 4: Dashboard, Alerts, Insights, Reports
    // ============================================

    // Dashboard API routes
    Route::prefix('api/dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/initial', [DashboardController::class, 'getInitialData'])->name('initial'); // Lazy load initial data
        Route::get('/data', [DashboardController::class, 'getData'])->name('data');
        Route::get('/kpis', [DashboardController::class, 'getKPIs'])->name('kpis');
        Route::get('/trends', [DashboardController::class, 'getTrends'])->name('trends');
        Route::get('/funnel', [DashboardController::class, 'getFunnel'])->name('funnel');
        Route::get('/channels', [DashboardController::class, 'getChannelComparison'])->name('channels');
        Route::post('/widgets', [DashboardController::class, 'updateWidgets'])->name('widgets');
        Route::post('/refresh', [DashboardController::class, 'refresh'])->name('refresh');
    });

    // Alerts routes (FAZA 4)
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->name('index');
        Route::get('/active', [AlertController::class, 'getActive'])->name('active');
        Route::get('/rules', [AlertController::class, 'rules'])->name('rules');
        Route::post('/rules', [AlertController::class, 'createRule'])->name('rules.store');
        Route::put('/rules/{rule}', [AlertController::class, 'updateRule'])->name('rules.update');
        Route::delete('/rules/{rule}', [AlertController::class, 'deleteRule'])->name('rules.destroy');
        Route::get('/{alert}', [AlertController::class, 'show'])->name('show');
        Route::post('/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{alert}/resolve', [AlertController::class, 'resolve'])->name('resolve');
        Route::post('/{alert}/snooze', [AlertController::class, 'snooze'])->name('snooze');
        Route::post('/{alert}/dismiss', [AlertController::class, 'dismiss'])->name('dismiss');
    });

    // Insights routes (FAZA 4)
    Route::prefix('insights')->name('insights.')->group(function () {
        Route::get('/', [InsightController::class, 'index'])->name('index');
        Route::get('/active', [InsightController::class, 'getActive'])->name('active');
        Route::post('/regenerate', [InsightController::class, 'regenerate'])->name('regenerate');
        Route::get('/category', [InsightController::class, 'getByCategory'])->name('category');
        Route::get('/{insight}', [InsightController::class, 'show'])->name('show');
        Route::post('/{insight}/viewed', [InsightController::class, 'markViewed'])->name('viewed');
        Route::post('/{insight}/acted', [InsightController::class, 'markActed'])->name('acted');
        Route::post('/{insight}/dismiss', [InsightController::class, 'dismiss'])->name('dismiss');
    });

    // Reports routes (FAZA 4) - extends existing reports
    Route::prefix('generated-reports')->name('generated-reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/schedules', [ReportController::class, 'schedules'])->name('schedules');
        Route::post('/schedules', [ReportController::class, 'createSchedule'])->name('schedules.store');
        Route::put('/schedules/{schedule}', [ReportController::class, 'updateSchedule'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [ReportController::class, 'deleteSchedule'])->name('schedules.destroy');
        Route::post('/schedules/{schedule}/run', [ReportController::class, 'runSchedule'])->name('schedules.run');
        Route::post('/generate/daily', [ReportController::class, 'generateDaily'])->name('generate.daily');
        Route::post('/generate/weekly', [ReportController::class, 'generateWeekly'])->name('generate.weekly');
        Route::post('/generate/monthly', [ReportController::class, 'generateMonthly'])->name('generate.monthly');
        Route::post('/generate/quarterly', [ReportController::class, 'generateQuarterly'])->name('generate.quarterly');
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
        Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
    });

    // Notifications routes (FAZA 4)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread', [NotificationController::class, 'getUnread'])->name('unread');
        Route::get('/count', [NotificationController::class, 'getCount'])->name('count');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/{notification}/clicked', [NotificationController::class, 'markAsClicked'])->name('clicked');
        Route::delete('/{notification}', [NotificationController::class, 'delete'])->name('delete');
    });

    // Feedback routes (User submissions)
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::post('/', [FeedbackController::class, 'store'])->name('store');
        Route::get('/my', [FeedbackController::class, 'myFeedback'])->name('my');
        Route::get('/types', [FeedbackController::class, 'getTypes'])->name('types');
    });
});

// Admin Panel Routes (Platform Management)
Route::middleware(['auth', 'admin'])->prefix('dashboard')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // System Health & Analytics
    Route::get('/system-health', [AdminDashboardController::class, 'systemHealth'])->name('system-health');
    Route::get('/analytics', [AdminDashboardController::class, 'analytics'])->name('analytics');

    // Business Management
    Route::prefix('businesses')->name('businesses.')->group(function () {
        Route::get('/', [BusinessManagementController::class, 'index'])->name('index');
        Route::get('/{business}', [BusinessManagementController::class, 'show'])->name('show');
        Route::put('/{business}/status', [BusinessManagementController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{business}', [BusinessManagementController::class, 'destroy'])->name('destroy');
    });

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Feedback Management (Admin)
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::get('/', [FeedbackManagementController::class, 'index'])->name('index');
        Route::get('/analytics', [FeedbackManagementController::class, 'analytics'])->name('analytics');
        Route::get('/{feedback}', [FeedbackManagementController::class, 'show'])->name('show');
        Route::post('/{feedback}/status', [FeedbackManagementController::class, 'updateStatus'])->name('update-status');
        Route::post('/{feedback}/priority', [FeedbackManagementController::class, 'updatePriority'])->name('update-priority');
        Route::post('/{feedback}/note', [FeedbackManagementController::class, 'addNote'])->name('add-note');
        Route::delete('/{feedback}', [FeedbackManagementController::class, 'destroy'])->name('destroy');
    });

    // Notification Management (Admin)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationManagementController::class, 'index'])->name('index');
        Route::get('/create', [NotificationManagementController::class, 'create'])->name('create');
        Route::post('/', [NotificationManagementController::class, 'store'])->name('store');
        Route::get('/analytics', [NotificationManagementController::class, 'analytics'])->name('analytics');
        Route::get('/users', [NotificationManagementController::class, 'getUsers'])->name('users');
        Route::get('/businesses', [NotificationManagementController::class, 'getBusinesses'])->name('businesses');
        Route::get('/{notification}', [NotificationManagementController::class, 'show'])->name('show');
        Route::delete('/{notification}', [NotificationManagementController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [NotificationManagementController::class, 'bulkDestroy'])->name('bulk-destroy');
    });
});

// Public Survey routes (CustDev - no authentication required)
Route::prefix('s')->name('survey.')->group(function () {
    Route::get('/{slug}', [PublicSurveyController::class, 'show'])->name('show');
    Route::post('/{slug}/start', [PublicSurveyController::class, 'startResponse'])->name('start');
    Route::post('/{slug}/answer', [PublicSurveyController::class, 'saveAnswer'])->name('answer');
    Route::post('/{slug}/complete', [PublicSurveyController::class, 'complete'])->name('complete');
    Route::get('/{slug}/thank-you', [PublicSurveyController::class, 'thankYou'])->name('thank-you');
});

// Public Lead Form routes (no authentication required)
Route::prefix('f')->name('lead-form.')->group(function () {
    Route::get('/{slug}', [PublicLeadFormController::class, 'show'])->name('show');
    Route::post('/{slug}/submit', [PublicLeadFormController::class, 'submit'])->name('submit');
    Route::get('/{slug}/download/{submission}', [PublicLeadFormController::class, 'download'])->name('download');
    Route::get('/{slug}/thank-you', [PublicLeadFormController::class, 'thankYou'])->name('thank-you');
});

// API endpoint for lead form webhooks (Facebook, Google Ads, etc.)
Route::post('/api/lead-forms/{slug}/submit', [PublicLeadFormController::class, 'apiSubmit'])->name('api.lead-form.submit');

// Webhook routes (public, no authentication required)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    // Telegram Funnel Builder webhooks (new system)
    Route::match(['get', 'post'], '/telegram-funnel/{botId}', [TelegramFunnelWebhookController::class, 'handle'])->name('telegram.funnel.webhook');

    // Telegram webhooks (legacy chatbot system)
    Route::match(['get', 'post'], '/telegram/{business}', [TelegramWebhookController::class, 'handle'])->name('telegram');
    Route::get('/telegram/{business}/verify', [TelegramWebhookController::class, 'verify'])->name('telegram.verify');

    // Instagram webhooks
    Route::match(['get', 'post'], '/instagram/{business}', [InstagramWebhookController::class, 'handle'])->name('instagram');

    // Facebook Messenger webhooks
    Route::match(['get', 'post'], '/facebook/{business}', [FacebookWebhookController::class, 'handle'])->name('facebook');

    // WhatsApp webhooks
    Route::match(['get', 'post'], '/whatsapp/{business}', [WhatsAppWebhookController::class, 'handle'])->name('whatsapp');

    // Telephony webhooks
    Route::post('/pbx', [TelephonyController::class, 'pbxWebhook'])->name('pbx');
    Route::post('/sipuni', [TelephonyController::class, 'sipuniWebhook'])->name('sipuni');
    Route::post('/onlinepbx', [TelephonyController::class, 'onlinePbxWebhook'])->name('onlinepbx');

    // Payment webhooks (Payme & Click)
    Route::post('/payme', [PaymentController::class, 'paymeWebhook'])->name('payme');
    Route::post('/click', [PaymentController::class, 'clickWebhook'])->name('click');
    Route::get('/whatsapp/{business}/info', [WhatsAppWebhookController::class, 'getWebhookInfo'])->name('whatsapp.info');
    Route::post('/whatsapp/{business}/test', [WhatsAppWebhookController::class, 'sendTestMessage'])->name('whatsapp.test');
    Route::post('/whatsapp/{business}/template', [WhatsAppWebhookController::class, 'sendTemplate'])->name('whatsapp.template');
    Route::post('/whatsapp/{business}/buttons', [WhatsAppWebhookController::class, 'sendButtons'])->name('whatsapp.buttons');
    Route::post('/whatsapp/{business}/media', [WhatsAppWebhookController::class, 'sendMedia'])->name('whatsapp.media');
});

// WhatsApp AI API routes (authenticated)
Route::middleware('auth')->prefix('api/whatsapp/{business}')->name('api.whatsapp.')->group(function () {
    Route::get('/ai-config', [WhatsAppWebhookController::class, 'getAIConfig'])->name('ai-config');
    Route::post('/ai-config', [WhatsAppWebhookController::class, 'updateAIConfig'])->name('ai-config.update');
    Route::post('/ai-templates', [WhatsAppWebhookController::class, 'saveAITemplates'])->name('ai-templates');
});

// Instagram AI API routes (authenticated)
Route::middleware('auth')->prefix('api/instagram/{business}')->name('api.instagram.')->group(function () {
    Route::get('/ai-config', [InstagramWebhookController::class, 'getAIConfig'])->name('ai-config');
    Route::post('/ai-config', [InstagramWebhookController::class, 'updateAIConfig'])->name('ai-config.update');
    Route::post('/ai-templates', [InstagramWebhookController::class, 'saveAITemplates'])->name('ai-templates');
});
