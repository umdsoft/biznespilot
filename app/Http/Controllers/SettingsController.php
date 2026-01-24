<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
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
            'preferred_ai_model' => ['string', 'in:gpt-4,gpt-3.5-turbo,claude-3-opus,claude-3-sonnet'],
            'ai_creativity_level' => ['integer', 'min:1', 'max:10'],
            'theme' => ['string', 'in:light,dark,auto'],
            'language' => ['string', 'in:uz,ru,en'],
        ]);

        $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);
        $settings->update($validated);

        return redirect()->back()->with('success', 'Sozlamalar yangilandi!');
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
}
