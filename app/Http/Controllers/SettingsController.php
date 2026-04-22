<?php

namespace App\Http\Controllers;

use App\Models\TelegramChannel;
use App\Models\UserSetting;
use App\Services\Telegram\SystemBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_notifications' => true,
                'browser_notifications' => true,
                'marketing_emails' => false,
                'preferred_ai_model' => 'gpt-4',
                'ai_creativity_level' => 7,
                'theme' => 'light',
                'language' => 'uz',
            ]
        );

        return Inertia::render('Business/Settings/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'settings' => [
                'email_notifications' => $settings->email_notifications,
                'browser_notifications' => $settings->browser_notifications,
                'marketing_emails' => $settings->marketing_emails,
                'preferred_ai_model' => $settings->preferred_ai_model,
                'ai_creativity_level' => $settings->ai_creativity_level,
                'theme' => $settings->theme,
                'language' => $settings->language,
                'has_openai_key' => ! empty($settings->openai_api_key),
                'has_claude_key' => ! empty($settings->claude_api_key),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profil yangilandi!');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Joriy parol noto\'g\'ri.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Parol yangilandi!');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => ['boolean'],
            'browser_notifications' => ['boolean'],
            'marketing_emails' => ['boolean'],
            'preferred_ai_model' => ['string', 'in:gpt-4,gpt-3.5-turbo,claude-haiku-4-5,claude-sonnet-4-5,claude-opus-4-6'],
            'ai_creativity_level' => ['integer', 'min:1', 'max:10'],
            'theme' => ['string', 'in:light,dark,auto'],
            'language' => ['string', 'in:uz,uz-latn,uz-cyrl,ru,en'],
        ]);

        $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);
        $settings->update($validated);

        // Til o'zgartirilgan bo'lsa — cookie ga yozish (i18n.js uni o'qiydi)
        $response = redirect()->back()->with('success', 'Sozlamalar yangilandi!');
        if (!empty($validated['language'])) {
            $lang = $validated['language'] === 'uz' ? 'uz-latn' : $validated['language'];
            $response->cookie('locale', $lang, 60 * 24 * 365, '/', null, false, false);
        }

        return $response;
    }

    public function updateApiKeys(Request $request)
    {
        $validated = $request->validate([
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'claude_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);

        if ($request->filled('openai_api_key')) {
            $settings->setOpenAIKey($validated['openai_api_key']);
        }

        if ($request->filled('claude_api_key')) {
            $settings->setClaudeKey($validated['claude_api_key']);
        }

        $settings->save();

        return redirect()->back()->with('success', 'API kalitlari yangilandi!');
    }

    public function deleteApiKey(Request $request)
    {
        $validated = $request->validate([
            'key_type' => ['required', 'string', 'in:openai,claude'],
        ]);

        $settings = UserSetting::where('user_id', Auth::id())->first();

        if ($settings) {
            if ($validated['key_type'] === 'openai') {
                $settings->openai_api_key = null;
            } elseif ($validated['key_type'] === 'claude') {
                $settings->claude_api_key = null;
            }
            $settings->save();
        }

        return redirect()->back()->with('success', 'API kaliti o\'chirildi!');
    }

    /**
     * WhatsApp Integration Settings
     *
     * @return \Inertia\Response
     */
    public function whatsapp()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (! $currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznесni tanlang yoki yarating.');
        }

        return Inertia::render('Business/Settings/WhatsApp', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
        ]);
    }

    /**
     * WhatsApp AI Configuration Settings
     *
     * @return \Inertia\Response
     */
    public function whatsappAI()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (! $currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznесni tanlang yoki yarating.');
        }

        return Inertia::render('Business/Settings/WhatsAppAI', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
        ]);
    }

    /**
     * Instagram AI Configuration Settings
     *
     * @return \Inertia\Response
     */
    public function instagramAI()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (! $currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznесni tanlang yoki yarating.');
        }

        return Inertia::render('Business/Settings/InstagramAI', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
        ]);
    }

    // ==========================================
    // TELEGRAM SYSTEM BOT INTEGRATION
    // ==========================================

    /**
     * Get Telegram connection status.
     */
    public function telegramStatus()
    {
        $user = Auth::user();

        return response()->json([
            'connected' => $user->hasTelegramLinked(),
            'chat_id' => $user->telegram_chat_id ? '***' . substr($user->telegram_chat_id, -4) : null,
            'linked_at' => $user->telegram_linked_at?->toIso8601String(),
        ]);
    }

    /**
     * Generate Telegram connect link for deep linking authentication.
     *
     * User clicks link -> Opens System Bot -> /start {token} -> Account linked
     */
    public function telegramConnect()
    {
        $user = Auth::user();

        // If already connected, return status
        if ($user->hasTelegramLinked()) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram allaqachon ulangan',
                'connected' => true,
                'linked_at' => $user->telegram_linked_at?->toIso8601String(),
            ], 400);
        }

        // Generate connect link
        $linkData = $user->generateTelegramConnectLink();

        return response()->json([
            'success' => true,
            'message' => 'Havolani bosing va botni ishga tushiring',
            'link' => $linkData['link'],
            'expires_at' => $linkData['expires_at'],
            'instructions' => [
                '1. Quyidagi havolani bosing',
                '2. Telegram ochiladi',
                '3. "Start" tugmasini bosing',
                '4. Hisob avtomatik ulanadi',
            ],
        ]);
    }

    /**
     * Disconnect Telegram account.
     */
    public function telegramDisconnect()
    {
        $user = Auth::user();

        if (!$user->hasTelegramLinked()) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram ulanmagan',
            ], 400);
        }

        $user->unlinkTelegram();

        return response()->json([
            'success' => true,
            'message' => 'Telegram uzildi. Endi kunlik hisobotlar yuborilmaydi.',
        ]);
    }

    // ==========================================
    // TELEGRAM CHANNEL ANALYTICS
    // ==========================================

    /**
     * Get deep link to add System Bot as admin to a user's channel.
     */
    public function telegramChannelConnectLink(SystemBotService $bot)
    {
        $user = Auth::user();

        if (!$user->hasTelegramLinked()) {
            return response()->json([
                'success' => false,
                'message' => 'Avval Telegram hisobingizni ulang.',
                'requires_telegram_link' => true,
            ], 400);
        }

        $link = $bot->generateChannelDeepLink();

        if (!$link) {
            return response()->json([
                'success' => false,
                'message' => 'System Bot sozlanmagan. Administratorga murojaat qiling.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'link' => $link,
            'bot_username' => $bot->getBotUsername(),
            'instructions' => [
                "1. Quyidagi havolani bosing.",
                "2. Kanalni tanlang (yoki yangi kanal yarating).",
                "3. Botga faqat «Kanalni boshqarish» huquqini bering — post yozish yoki o'chirish kerak emas.",
                "4. Bot avtomatik ravishda ulanadi — sizga Telegram orqali tasdiq xabari keladi.",
            ],
        ]);
    }

    /**
     * List user's tracked Telegram channels.
     */
    public function telegramChannelsIndex()
    {
        $user = Auth::user();
        $businessId = session('current_business_id');

        $channels = TelegramChannel::query()
            ->where('business_id', $businessId)
            ->orderByDesc('is_active')
            ->orderByDesc('subscriber_count')
            ->get()
            ->map(fn ($channel) => [
                'id' => $channel->id,
                'title' => $channel->title,
                'username' => $channel->chat_username,
                'public_link' => $channel->publicLink(),
                'photo_url' => $channel->photo_url,
                'subscriber_count' => $channel->subscriber_count,
                'type' => $channel->type,
                'admin_status' => $channel->admin_status,
                'is_active' => $channel->is_active,
                'connected_at' => $channel->connected_at?->toIso8601String(),
                'last_synced_at' => $channel->last_synced_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'channels' => $channels,
        ]);
    }

    /**
     * Remove a tracked channel (soft-disable).
     * Note: bot must be removed from admin manually by user in Telegram.
     */
    public function telegramChannelDisconnect(TelegramChannel $channel, SystemBotService $bot)
    {
        $user = Auth::user();
        $businessId = session('current_business_id');

        if ($channel->business_id !== $businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        // Try to leave the chat from bot's side (best-effort)
        $bot->leaveChat($channel->telegram_chat_id);

        $channel->markDisconnected(TelegramChannel::STATUS_LEFT);

        return response()->json([
            'success' => true,
            'message' => "«{$channel->title}» kanali uzildi.",
        ]);
    }
}
