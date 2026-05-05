<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * LogAuthActivity
 *
 * Auth event'larini (Login, Logout, Failed login) `activity_logs` jadvaliga
 * yozadi. Bu admin paneldagi "Faoliyat jurnali" sahifasining asosiy ma'lumot
 * manbasi.
 *
 * Laravel auth event'larini avto-discover qiladi (handle() metodning typed
 * parametri orqali).
 */
class LogAuthActivity
{
    public function handleLogin(Login $event): void
    {
        try {
            $user = $event->user;
            ActivityLog::create([
                'business_id' => session('current_business_id'),
                'user_id' => $user->id ?? null,
                'type' => 'auth',
                'action' => 'login',
                'description' => "Kirish: {$user->name} ({$user->email})",
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'properties' => [
                    'guard' => $event->guard ?? null,
                    'remember' => $event->remember ?? false,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[LogAuthActivity] Login log failed', ['error' => $e->getMessage()]);
        }
    }

    public function handleLogout(Logout $event): void
    {
        try {
            $user = $event->user;
            if (! $user) {
                return;
            }
            ActivityLog::create([
                'business_id' => session('current_business_id'),
                'user_id' => $user->id,
                'type' => 'auth',
                'action' => 'logout',
                'description' => "Chiqish: {$user->name}",
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[LogAuthActivity] Logout log failed', ['error' => $e->getMessage()]);
        }
    }

    public function handleFailed(Failed $event): void
    {
        try {
            $email = $event->credentials['email'] ?? null;
            ActivityLog::create([
                'user_id' => null,
                'type' => 'auth',
                'action' => 'login_failed',
                'description' => "Muvaffaqiyatsiz kirish urinishi" . ($email ? ": {$email}" : ''),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'properties' => ['email' => $email],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[LogAuthActivity] Failed login log failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Subscribe listeners.
     * Laravel'da auto-discovery handle*() metodlari bo'yicha ishlaydi.
     */
    public function subscribe(\Illuminate\Events\Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
        ];
    }
}
