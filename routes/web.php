<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\AIInsightsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\MonthlyStrategyController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ChannelAnalyticsController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ChatbotManagementController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\InstagramWebhookController;
use App\Http\Controllers\FacebookWebhookController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\CompetitorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\ContentCalendarController;
use App\Http\Controllers\DreamBuyerController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MarketingAnalyticsController;
use App\Http\Controllers\MarketingCampaignController;
use App\Http\Controllers\OffersController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\UnifiedInboxController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BusinessManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\TargetAnalysisController;
use App\Http\Controllers\InstagramAnalysisController;
use App\Http\Controllers\InstagramChatbotController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// 2FA verification routes (accessible without full auth)
Route::get('/two-factor/verify', [AuthController::class, 'showTwoFactorVerify'])->name('two-factor.verify');
Route::post('/two-factor/verify', [AuthController::class, 'verifyTwoFactor']);

// Root redirect based on role
Route::middleware('auth')->get('/', function () {
    $user = auth()->user();

    if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('business.dashboard');
});

// Logout route (accessible from both panels)
Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('logout');

// Business Panel Routes
Route::middleware('auth')->prefix('business')->name('business.')->group(function () {
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

    // Marketing routes
    Route::prefix('marketing')->name('marketing.')->group(function () {
        // Marketing Analytics Dashboard
        Route::get('/', [MarketingAnalyticsController::class, 'index'])->name('index');
        Route::get('/analytics', [MarketingAnalyticsController::class, 'index'])->name('analytics');

        // Channels Management
        Route::get('/channels', [MarketingAnalyticsController::class, 'channels'])->name('channels');
        Route::post('/channels', [MarketingAnalyticsController::class, 'store'])->name('channels.store');
        Route::get('/channels/{channel}', [MarketingAnalyticsController::class, 'channelDetail'])->name('channels.show');
        Route::put('/channels/{channel}', [MarketingAnalyticsController::class, 'update'])->name('channels.update');
        Route::delete('/channels/{channel}', [MarketingAnalyticsController::class, 'destroy'])->name('channels.destroy');

        // Old Marketing Controller routes (content, etc.)
        Route::get('/content', [MarketingController::class, 'content'])->name('content');
        Route::post('/content', [MarketingController::class, 'storeContent'])->name('content.store');
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

    // Sales routes
    Route::resource('sales', SalesController::class);

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

    // AI routes
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/', [AIController::class, 'index'])->name('index');
        Route::post('/analyze-dream-buyer', [AIController::class, 'analyzeDreamBuyer'])->name('analyze-dream-buyer');
        Route::post('/generate-content', [AIController::class, 'generateContent'])->name('generate-content');
        Route::post('/analyze-competitor', [AIController::class, 'analyzeCompetitor'])->name('analyze-competitor');
        Route::post('/optimize-offer', [AIController::class, 'optimizeOffer'])->name('optimize-offer');
        Route::post('/get-advice', [AIController::class, 'getAdvice'])->name('get-advice');
    });

    // AI Insights routes
    Route::prefix('ai/insights')->name('ai.insights.')->group(function () {
        Route::get('/', [AIInsightsController::class, 'index'])->name('index');
        Route::get('/statistics', [AIInsightsController::class, 'statistics'])->name('statistics');
        Route::post('/generate', [AIInsightsController::class, 'generate'])->name('generate');
        Route::post('/queue-generation', [AIInsightsController::class, 'queueGeneration'])->name('queue-generation');
        Route::post('/mark-multiple-read', [AIInsightsController::class, 'markMultipleAsRead'])->name('mark-multiple-read');
        Route::get('/{insight}', [AIInsightsController::class, 'show'])->name('show');
        Route::post('/{insight}/mark-read', [AIInsightsController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{insight}/record-action', [AIInsightsController::class, 'recordAction'])->name('record-action');
        Route::delete('/{insight}', [AIInsightsController::class, 'destroy'])->name('destroy');
    });

    // AI Monthly Strategy routes
    Route::prefix('ai/strategy')->name('ai.strategy.')->group(function () {
        Route::get('/', [MonthlyStrategyController::class, 'index'])->name('index');
        Route::get('/statistics', [MonthlyStrategyController::class, 'statistics'])->name('statistics');
        Route::post('/generate', [MonthlyStrategyController::class, 'generate'])->name('generate');
        Route::post('/queue-generation', [MonthlyStrategyController::class, 'queueGeneration'])->name('queue-generation');
        Route::get('/{strategy}', [MonthlyStrategyController::class, 'show'])->name('show');
        Route::post('/{strategy}/approve', [MonthlyStrategyController::class, 'approve'])->name('approve');
        Route::post('/{strategy}/complete', [MonthlyStrategyController::class, 'complete'])->name('complete');
        Route::post('/{strategy}/archive', [MonthlyStrategyController::class, 'archive'])->name('archive');
        Route::delete('/{strategy}', [MonthlyStrategyController::class, 'destroy'])->name('destroy');
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

    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
    });

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

        // 2FA routes
        Route::get('/two-factor', [TwoFactorAuthController::class, 'show'])->name('two-factor');
        Route::get('/two-factor/setup', [TwoFactorAuthController::class, 'setup'])->name('two-factor.setup');
        Route::post('/two-factor/enable', [TwoFactorAuthController::class, 'enable'])->name('two-factor.enable');
        Route::post('/two-factor/disable', [TwoFactorAuthController::class, 'disable'])->name('two-factor.disable');
        Route::get('/two-factor/recovery-codes', [TwoFactorAuthController::class, 'showRecoveryCodes'])->name('two-factor.recovery-codes');
        Route::post('/two-factor/recovery-codes/regenerate', [TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes.regenerate');
    });

    // Activity Logs routes
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/stats', [ActivityLogController::class, 'stats'])->name('stats');
        Route::get('/export', [ActivityLogController::class, 'export'])->name('export');
        Route::post('/clean', [ActivityLogController::class, 'clean'])->name('clean')->middleware('can:manage:settings');
        Route::get('/{log}', [ActivityLogController::class, 'show'])->name('show');
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
        Route::put('/{competitor}', [CompetitorController::class, 'update'])->name('update');
        Route::delete('/{competitor}', [CompetitorController::class, 'destroy'])->name('destroy');
        Route::post('/{competitor}/metrics', [CompetitorController::class, 'recordMetrics'])->name('metrics.record');
        Route::post('/{competitor}/monitor', [CompetitorController::class, 'monitor'])->name('monitor');
        Route::post('/{competitor}/swot', [CompetitorController::class, 'generateSwot'])->name('swot.generate');
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

    // AI Diagnostic routes (FAZA 2)
    Route::prefix('diagnostic')->name('diagnostic.')->group(function () {
        Route::get('/', [DiagnosticController::class, 'index'])->name('index');
        Route::get('/check-eligibility', [DiagnosticController::class, 'checkEligibility'])->name('check-eligibility');
        Route::post('/start', [DiagnosticController::class, 'start'])->name('start');
        Route::get('/history', [DiagnosticController::class, 'history'])->name('history');
        Route::get('/{diagnostic}/status', [DiagnosticController::class, 'status'])->name('status');
        Route::get('/{diagnostic}/processing', [DiagnosticController::class, 'processing'])->name('processing');
        Route::get('/{diagnostic}', [DiagnosticController::class, 'show'])->name('show');
        Route::get('/{diagnostic}/questions', [DiagnosticController::class, 'questions'])->name('questions');
        Route::post('/questions/{question}/answer', [DiagnosticController::class, 'answerQuestion'])->name('questions.answer');
        Route::get('/{diagnostic}/report/{type?}', [DiagnosticController::class, 'downloadReport'])->name('report');
        Route::get('/{diagnostic1}/compare/{diagnostic2}', [DiagnosticController::class, 'compare'])->name('compare');
    });

    // Diagnostic API routes
    Route::prefix('api/diagnostic')->name('api.diagnostic.')->group(function () {
        Route::get('/latest', [DiagnosticController::class, 'apiLatest'])->name('latest');
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
});

// Webhook routes (public, no authentication required)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    // Telegram webhooks
    Route::match(['get', 'post'], '/telegram/{business}', [TelegramWebhookController::class, 'handle'])->name('telegram');
    Route::get('/telegram/{business}/verify', [TelegramWebhookController::class, 'verify'])->name('telegram.verify');

    // Instagram webhooks
    Route::match(['get', 'post'], '/instagram/{business}', [InstagramWebhookController::class, 'handle'])->name('instagram');

    // Facebook Messenger webhooks
    Route::match(['get', 'post'], '/facebook/{business}', [FacebookWebhookController::class, 'handle'])->name('facebook');

    // WhatsApp webhooks
    Route::match(['get', 'post'], '/whatsapp/{business}', [WhatsAppWebhookController::class, 'handle'])->name('whatsapp');
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
