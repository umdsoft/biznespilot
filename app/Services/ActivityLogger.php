<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $changes = null,
        ?array $metadata = null
    ): ActivityLog {
        $businessId = session('current_business_id');
        $user = Auth::user();

        return ActivityLog::create([
            'business_id' => $businessId,
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $subject ? get_class($subject) : null,
            'model_id' => $subject?->id,
            'description' => $description ?? static::generateDescription($action, $subject),
            'changes' => $changes,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log a "created" event
     */
    public static function created(Model $subject, ?array $metadata = null): ActivityLog
    {
        return static::log('created', $subject, null, [
            'new' => $subject->getAttributes(),
        ], $metadata);
    }

    /**
     * Log an "updated" event
     */
    public static function updated(Model $subject, array $changes, ?array $metadata = null): ActivityLog
    {
        return static::log('updated', $subject, null, $changes, $metadata);
    }

    /**
     * Log a "deleted" event
     */
    public static function deleted(Model $subject, ?array $metadata = null): ActivityLog
    {
        return static::log('deleted', $subject, null, [
            'old' => $subject->getAttributes(),
        ], $metadata);
    }

    /**
     * Log a "restored" event (soft delete restore)
     */
    public static function restored(Model $subject, ?array $metadata = null): ActivityLog
    {
        return static::log('restored', $subject, null, null, $metadata);
    }

    /**
     * Log a login event
     */
    public static function login(?array $metadata = null): ActivityLog
    {
        return static::log('login', null, 'Tizimga kirdi', null, $metadata);
    }

    /**
     * Log a logout event
     */
    public static function logout(?array $metadata = null): ActivityLog
    {
        return static::log('logout', null, 'Tizimdan chiqdi', null, $metadata);
    }

    /**
     * Log a failed login attempt
     */
    public static function failedLogin(string $login, ?array $metadata = null): ActivityLog
    {
        return static::log('failed_login', null, "Muvaffaqiyatsiz login urinishi: {$login}", null, $metadata);
    }

    /**
     * Log a password change
     */
    public static function passwordChanged(?array $metadata = null): ActivityLog
    {
        return static::log('password_changed', null, 'Parol o\'zgartirildi', null, $metadata);
    }

    /**
     * Log 2FA enabled
     */
    public static function twoFactorEnabled(?array $metadata = null): ActivityLog
    {
        return static::log('2fa_enabled', null, 'Ikki faktorli autentifikatsiya yoqildi', null, $metadata);
    }

    /**
     * Log 2FA disabled
     */
    public static function twoFactorDisabled(?array $metadata = null): ActivityLog
    {
        return static::log('2fa_disabled', null, 'Ikki faktorli autentifikatsiya o\'chirildi', null, $metadata);
    }

    /**
     * Log subscription change
     */
    public static function subscriptionChanged(string $from, string $to, ?array $metadata = null): ActivityLog
    {
        return static::log('subscription_changed', null, "Subscription o'zgartirildi: {$from} -> {$to}", null, $metadata);
    }

    /**
     * Log export action
     */
    public static function exported(string $type, int $count, ?array $metadata = null): ActivityLog
    {
        return static::log('exported', null, "{$type} eksport qilindi ({$count} ta)", null, $metadata);
    }

    /**
     * Log import action
     */
    public static function imported(string $type, int $count, ?array $metadata = null): ActivityLog
    {
        return static::log('imported', null, "{$type} import qilindi ({$count} ta)", null, $metadata);
    }

    /**
     * Log permission change
     */
    public static function permissionChanged(Model $subject, array $changes, ?array $metadata = null): ActivityLog
    {
        return static::log('permission_changed', $subject, 'Ruxsatlar o\'zgartirildi', $changes, $metadata);
    }

    /**
     * Log business switch
     */
    public static function businessSwitched(int $fromBusinessId, int $toBusinessId, ?array $metadata = null): ActivityLog
    {
        return static::log('business_switched', null, "Business o'zgartirildi: {$fromBusinessId} -> {$toBusinessId}", null, $metadata);
    }

    /**
     * Log team member invitation
     */
    public static function teamMemberInvited(string $email, string $role, ?array $metadata = null): ActivityLog
    {
        return static::log('team_member_invited', null, "Jamoa a'zosi taklif qilindi: {$email} ({$role})", null, $metadata);
    }

    /**
     * Log team member removed
     */
    public static function teamMemberRemoved(string $name, ?array $metadata = null): ActivityLog
    {
        return static::log('team_member_removed', null, "Jamoa a'zosi olib tashlandi: {$name}", null, $metadata);
    }

    /**
     * Log API key created
     */
    public static function apiKeyCreated(string $name, ?array $metadata = null): ActivityLog
    {
        return static::log('api_key_created', null, "API kalit yaratildi: {$name}", null, $metadata);
    }

    /**
     * Log API key revoked
     */
    public static function apiKeyRevoked(string $name, ?array $metadata = null): ActivityLog
    {
        return static::log('api_key_revoked', null, "API kalit bekor qilindi: {$name}", null, $metadata);
    }

    /**
     * Log integration connected
     */
    public static function integrationConnected(string $service, ?array $metadata = null): ActivityLog
    {
        return static::log('integration_connected', null, "Integratsiya ulandi: {$service}", null, $metadata);
    }

    /**
     * Log integration disconnected
     */
    public static function integrationDisconnected(string $service, ?array $metadata = null): ActivityLog
    {
        return static::log('integration_disconnected', null, "Integratsiya uzildi: {$service}", null, $metadata);
    }

    /**
     * Generate a description for an activity
     */
    protected static function generateDescription(string $action, ?Model $subject): string
    {
        if (!$subject) {
            return ucfirst($action);
        }

        $modelName = class_basename($subject);
        $identifier = $subject->name ?? $subject->title ?? $subject->id;

        return match ($action) {
            'created' => "{$modelName} yaratildi: {$identifier}",
            'updated' => "{$modelName} yangilandi: {$identifier}",
            'deleted' => "{$modelName} o'chirildi: {$identifier}",
            'restored' => "{$modelName} qayta tiklandi: {$identifier}",
            default => "{$modelName} {$action}: {$identifier}",
        };
    }

    /**
     * Get activity logs for current business
     */
    public static function getRecentActivity(int $limit = 50)
    {
        return ActivityLog::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity logs for a specific user
     */
    public static function getUserActivity(int $userId, int $limit = 50)
    {
        return ActivityLog::where('user_id', $userId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity logs for a specific model
     */
    public static function getModelActivity(Model $model, int $limit = 50)
    {
        return ActivityLog::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity logs by action type
     */
    public static function getByAction(string $action, int $limit = 50)
    {
        return ActivityLog::where('action', $action)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean old activity logs (older than specified days)
     */
    public static function cleanOldLogs(int $daysToKeep = 90): int
    {
        return ActivityLog::where('created_at', '<', now()->subDays($daysToKeep))->delete();
    }
}
