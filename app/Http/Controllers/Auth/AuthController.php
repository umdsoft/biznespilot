<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\BusinessUser;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(LoginRequest $request)
    {
        $loginField = str_starts_with($request->login, '+') ? 'phone' : 'login';

        // Find user
        $user = User::where($loginField, $request->login)->first();

        // Check if account is locked
        if ($user && $user->locked_until && now()->lt($user->locked_until)) {
            $minutesLeft = now()->diffInMinutes($user->locked_until);
            return back()->withErrors([
                'login' => "Hisobingiz vaqtincha bloklangan. {$minutesLeft} daqiqadan so'ng qayta urinib ko'ring.",
            ])->onlyInput('login');
        }

        $credentials = [
            $loginField => $request->login,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $authenticatedUser = Auth::user();

            // Check if user has 2FA enabled
            if ($authenticatedUser->two_factor_enabled) {
                // Log them out temporarily
                Auth::logout();

                // Store user ID in session for 2FA verification
                session([
                    'two_factor_user_id' => $authenticatedUser->id,
                    'two_factor_remember' => $request->boolean('remember'),
                ]);

                return redirect()->route('two-factor.verify');
            }

            // Reset failed attempts on successful login
            $authenticatedUser->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            $request->session()->regenerate();

            // Redirect based on user role or department
            if ($authenticatedUser->hasRole('admin') || $authenticatedUser->hasRole('super_admin')) {
                return redirect()->intended(route('admin.dashboard'));
            }

            // Check user's department in their businesses
            return $this->redirectByDepartment($authenticatedUser);
        }

        // Increment failed login attempts
        if ($user) {
            $user->increment('failed_login_attempts');

            // Lock account after 5 failed attempts for 15 minutes
            if ($user->failed_login_attempts >= 5) {
                $user->update([
                    'locked_until' => now()->addMinutes(15),
                ]);

                return back()->withErrors([
                    'login' => 'Hisobingiz 15 daqiqaga bloklandi. Ko\'p marta noto\'g\'ri parol kiritildi.',
                ])->onlyInput('login');
            }

            $attemptsLeft = 5 - $user->failed_login_attempts;
            return back()->withErrors([
                'login' => "Login yoki parol noto'g'ri. Qolgan urinishlar: {$attemptsLeft}",
            ])->onlyInput('login');
        }

        return back()->withErrors([
            'login' => 'Login yoki parol noto\'g\'ri.',
        ])->onlyInput('login');
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'login' => $request->login,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        // Redirect new users to business dashboard
        return redirect()->route('business.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show 2FA verification page
     */
    public function showTwoFactorVerify(Request $request)
    {
        if (!session('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorVerify');
    }

    /**
     * Verify 2FA code
     */
    public function verifyTwoFactor(Request $request, TwoFactorAuthService $twoFactorService)
    {
        $request->validate([
            'code' => 'required|string',
            'recovery' => 'boolean',
        ]);

        $userId = session('two_factor_user_id');

        if (!$userId) {
            return redirect()->route('login')->withErrors(['message' => 'Sessiya tugagan.']);
        }

        $user = User::find($userId);

        if (!$user) {
            session()->forget(['two_factor_user_id', 'two_factor_remember']);
            return redirect()->route('login')->withErrors(['message' => 'Foydalanuvchi topilmadi.']);
        }

        $isValid = false;

        // Check if it's a recovery code or TOTP code
        if ($request->boolean('recovery')) {
            $isValid = $twoFactorService->verifyRecoveryCode($user, $request->code);
        } else {
            $isValid = $twoFactorService->verifyUserCode($user, $request->code);
        }

        if (!$isValid) {
            return back()->withErrors(['code' => 'Noto\'g\'ri kod.']);
        }

        // Clear 2FA session data
        $remember = session('two_factor_remember', false);
        session()->forget(['two_factor_user_id', 'two_factor_remember']);

        // Log the user in
        Auth::login($user, $remember);

        // Reset failed attempts and update login info
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        // Redirect based on user role or department
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Check user's department in their businesses
        return $this->redirectByDepartment($user);
    }

    /**
     * Redirect user based on their department
     */
    protected function redirectByDepartment(User $user)
    {
        // Get user's membership in any business
        $membership = BusinessUser::where('user_id', $user->id)
            ->whereNotNull('department')
            ->first();

        if ($membership) {
            // Set current business in session
            session(['current_business_id' => $membership->business_id]);

            // Redirect based on department
            switch ($membership->department) {
                case 'sales_head':
                    return redirect()->intended(route('sales-head.dashboard'));

                case 'sales_operator':
                    // TODO: Create sales operator panel later
                    return redirect()->intended(route('business.dashboard'));

                case 'marketing':
                    // TODO: Create marketing panel later
                    return redirect()->intended(route('business.dashboard'));

                case 'hr':
                    // TODO: Create HR panel later
                    return redirect()->intended(route('business.dashboard'));

                case 'finance':
                    // TODO: Create finance panel later
                    return redirect()->intended(route('business.dashboard'));

                default:
                    return redirect()->intended(route('business.dashboard'));
            }
        }

        // Default: business owner or no specific department
        return redirect()->intended(route('business.dashboard'));
    }
}
