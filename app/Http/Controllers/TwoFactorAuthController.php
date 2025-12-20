<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class TwoFactorAuthController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Show 2FA setup page
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $twoFactorData = null;

        if ($user->two_factor_enabled) {
            // User already has 2FA enabled
            $recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);

            $twoFactorData = [
                'enabled' => true,
                'enabled_at' => $user->two_factor_enabled_at,
                'recovery_codes_count' => $recoveryCodes ? $recoveryCodes->count() : 0,
            ];
        }

        return Inertia::render('Settings/TwoFactorAuth', [
            'twoFactorData' => $twoFactorData,
        ]);
    }

    /**
     * Generate QR code for 2FA setup
     */
    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return back()->withErrors(['message' => '2FA allaqachon yoqilgan.']);
        }

        // Generate secret
        $secret = $this->twoFactorService->generateSecretKey();

        // Generate QR code URL
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl($user, $secret);

        // Store secret temporarily in session
        session(['two_factor_secret_temp' => $secret]);

        return Inertia::render('Settings/TwoFactorSetup', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    /**
     * Enable 2FA after verifying code
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $secret = session('two_factor_secret_temp');

        if (!$secret) {
            return back()->withErrors(['code' => 'Sessiya tugagan. Qaytadan boshlang.']);
        }

        // Try to enable 2FA
        $enabled = $this->twoFactorService->enable($user, $secret, $request->code);

        if (!$enabled) {
            return back()->withErrors(['code' => 'Noto\'g\'ri kod. Qaytadan urinib ko\'ring.']);
        }

        // Clear temporary secret
        session()->forget('two_factor_secret_temp');

        // Get recovery codes to show user
        $recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);

        return Inertia::render('Settings/TwoFactorRecoveryCodes', [
            'recoveryCodes' => $recoveryCodes,
            'message' => '2FA muvaffaqiyatli yoqildi!',
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!\Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Noto\'g\'ri parol.']);
        }

        $this->twoFactorService->disable($user);

        return redirect()->route('settings.two-factor')->with('success', '2FA o\'chirildi.');
    }

    /**
     * Show recovery codes
     */
    public function showRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return redirect()->route('settings.two-factor');
        }

        $recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);

        return Inertia::render('Settings/TwoFactorRecoveryCodes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!\Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Noto\'g\'ri parol.']);
        }

        if (!$user->two_factor_enabled) {
            return back()->withErrors(['message' => '2FA yoqilmagan.']);
        }

        $recoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

        return Inertia::render('Settings/TwoFactorRecoveryCodes', [
            'recoveryCodes' => $recoveryCodes,
            'message' => 'Yangi recovery codelar yaratildi!',
        ]);
    }
}
