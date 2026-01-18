<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'login' => $request->login,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user with login/phone and password
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Determine if login is phone (starts with +) or username
            $loginField = str_starts_with($request->login, '+') ? 'phone' : 'login';

            // Find user by login or phone
            $user = User::where($loginField, $request->login)->first();

            // Check if account is locked
            if ($user && $user->locked_until && now()->lt($user->locked_until)) {
                $minutesLeft = now()->diffInMinutes($user->locked_until);

                return response()->json([
                    'success' => false,
                    'message' => 'Account temporarily locked',
                    'error' => "Hisobingiz vaqtincha bloklangan. {$minutesLeft} daqiqadan so'ng qayta urinib ko'ring.",
                    'locked_until' => $user->locked_until,
                ], 423);
            }

            // Check if user exists and password is correct
            if (! $user || ! Hash::check($request->password, $user->password)) {
                // Increment failed login attempts
                if ($user) {
                    $user->increment('failed_login_attempts');

                    // Lock account after 5 failed attempts for 15 minutes
                    if ($user->failed_login_attempts >= 5) {
                        $user->update([
                            'locked_until' => now()->addMinutes(15),
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Account locked',
                            'error' => 'Hisobingiz 15 daqiqaga bloklandi. Ko\'p marta noto\'g\'ri parol kiritildi.',
                            'locked_until' => $user->locked_until,
                        ], 423);
                    }

                    $attemptsLeft = 5 - $user->failed_login_attempts;
                    throw ValidationException::withMessages([
                        'login' => ["Login yoki parol noto'g'ri. Qolgan urinishlar: {$attemptsLeft}"],
                    ]);
                }

                throw ValidationException::withMessages([
                    'login' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Reset failed attempts on successful login
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout current user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // If using Sanctum token auth, revoke the token
            if ($user && method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            // Also logout from web session if applicable
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user with businesses
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['businesses', 'teamBusinesses']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
