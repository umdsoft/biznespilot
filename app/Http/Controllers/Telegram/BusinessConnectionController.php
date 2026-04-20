<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\TelegramBot;
use App\Models\TelegramBusinessConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BusinessConnectionController extends Controller
{
    /**
     * List all business connections for a bot.
     */
    public function index(Request $request, string $botId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('id', $botId)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $connections = TelegramBusinessConnection::where('telegram_bot_id', $bot->id)
            ->orderBy('connected_at', 'desc')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'connection_id' => $c->connection_id,
                'owner_name' => $c->owner_full_name,
                'owner_username' => $c->owner_username,
                'can_reply' => $c->can_reply,
                'is_enabled' => $c->is_enabled,
                'ai_auto_reply' => $c->ai_auto_reply,
                'ai_mode' => $c->ai_mode,
                'ai_mode_label' => TelegramBusinessConnection::AI_MODES[$c->ai_mode] ?? $c->ai_mode,
                'is_active' => $c->isActive(),
                'persona_prompt' => $c->persona_prompt,
                'settings' => $c->settings ?? [],
                'connected_at' => $c->connected_at?->format('d.m.Y H:i'),
                'disconnected_at' => $c->disconnected_at?->format('d.m.Y H:i'),
                'last_activity_at' => $c->last_activity_at?->diffForHumans(),
                'conversations_count' => $c->conversations()->count(),
            ]);

        return Inertia::render('Business/Telegram/BusinessConnections/Index', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'connections' => $connections,
            'aiModes' => TelegramBusinessConnection::AI_MODES,
        ]);
    }

    /**
     * Update business connection settings.
     */
    public function update(Request $request, string $botId, string $connectionId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $connection = TelegramBusinessConnection::where('id', $connectionId)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $validated = $request->validate([
            'ai_auto_reply' => 'sometimes|boolean',
            'ai_mode' => 'sometimes|string|in:auto,hybrid,manual',
            'persona_prompt' => 'sometimes|nullable|string|max:2000',
            'settings.working_hours.enabled' => 'sometimes|boolean',
            'settings.working_hours.start' => 'sometimes|string',
            'settings.working_hours.end' => 'sometimes|string',
            'settings.away_message' => 'sometimes|nullable|string|max:500',
            'settings.welcome_message' => 'sometimes|nullable|string|max:500',
        ]);

        // Merge settings deeply
        if (isset($validated['settings'])) {
            $validated['settings'] = array_replace_recursive(
                $connection->settings ?? [],
                $validated['settings']
            );
        }

        $connection->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Sozlamalar yangilandi",
            'connection' => $connection->fresh(),
        ]);
    }

    /**
     * Toggle enabled status (pause/resume AI for this connection).
     */
    public function toggleEnabled(Request $request, string $botId, string $connectionId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $connection = TelegramBusinessConnection::where('id', $connectionId)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $connection->update(['is_enabled' => ! $connection->is_enabled]);

        return response()->json([
            'success' => true,
            'is_enabled' => $connection->is_enabled,
            'message' => $connection->is_enabled ? "AI yoqildi" : "AI to'xtatildi",
        ]);
    }

    /**
     * Delete (stop tracking) a business connection record.
     * Note: this does NOT disconnect from Telegram — user must do that in Telegram settings.
     */
    public function destroy(Request $request, string $botId, string $connectionId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $connection = TelegramBusinessConnection::where('id', $connectionId)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $connection->delete();

        return response()->json([
            'success' => true,
            'message' => "Ulanish yozuvi o'chirildi",
        ]);
    }
}
