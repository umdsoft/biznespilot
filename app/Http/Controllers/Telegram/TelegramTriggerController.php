<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\TelegramBot;
use App\Models\TelegramFunnel;
use App\Models\TelegramTrigger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TelegramTriggerController extends Controller
{
    /**
     * List all triggers for a bot
     */
    public function index(Request $request, string $botId): Response|JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $triggers = TelegramTrigger::where('telegram_bot_id', $bot->id)
            ->with(['funnel:id,name', 'step:id,name'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($trigger) => [
                'id' => $trigger->id,
                'name' => $trigger->name,
                'type' => $trigger->type,
                'value' => $trigger->value,
                'match_type' => $trigger->match_type,
                'is_active' => $trigger->is_active,
                'priority' => $trigger->priority,
                'funnel' => $trigger->funnel ? [
                    'id' => $trigger->funnel->id,
                    'name' => $trigger->funnel->name,
                ] : null,
                'step' => $trigger->step ? [
                    'id' => $trigger->step->id,
                    'name' => $trigger->step->name,
                ] : null,
                'created_at' => $trigger->created_at->format('d.m.Y'),
            ]);

        // Get funnels for dropdown
        $funnels = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->select('id', 'name')
            ->with('steps:id,funnel_id,name')
            ->get();

        // Return JSON for AJAX requests
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'triggers' => $triggers,
                'funnels' => $funnels,
            ]);
        }

        // Return Inertia view
        return Inertia::render('Business/Telegram/Triggers/Index', [
            'bot' => [
                'id' => $bot->id,
                'first_name' => $bot->first_name,
                'username' => $bot->bot_username,
                'is_active' => $bot->is_active,
            ],
            'triggers' => $triggers,
            'funnels' => $funnels,
        ]);
    }

    /**
     * Create new trigger
     */
    public function store(Request $request, string $botId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:command,keyword,callback,start_payload,text,event',
            'value' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with,ends_with,regex,wildcard',
            'funnel_id' => 'required|uuid|exists:telegram_funnels,id',
            'step_id' => 'nullable|uuid|exists:telegram_funnel_steps,id',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        // Validate command format
        if ($request->type === 'command' && ! str_starts_with($request->value, '/')) {
            return response()->json([
                'success' => false,
                'message' => 'Buyruq / bilan boshlanishi kerak',
            ], 400);
        }

        $trigger = TelegramTrigger::create([
            'business_id' => $business->id,
            'telegram_bot_id' => $bot->id,
            'name' => $request->name,
            'type' => $request->type,
            'value' => $request->value,
            'match_type' => $request->match_type,
            'funnel_id' => $request->funnel_id,
            'step_id' => $request->step_id,
            'priority' => $request->priority ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'trigger' => [
                'id' => $trigger->id,
                'name' => $trigger->name,
            ],
            'message' => 'Trigger yaratildi',
        ]);
    }

    /**
     * Update trigger
     */
    public function update(Request $request, string $botId, string $triggerId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $trigger = TelegramTrigger::where('telegram_bot_id', $bot->id)
            ->where('id', $triggerId)
            ->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:command,keyword,callback,start_payload,text,event',
            'value' => 'sometimes|string|max:255',
            'match_type' => 'sometimes|in:exact,contains,starts_with,ends_with,regex,wildcard',
            'funnel_id' => 'nullable|uuid|exists:telegram_funnels,id',
            'step_id' => 'nullable|uuid|exists:telegram_funnel_steps,id',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        // Validate command format
        if ($request->has('type') && $request->type === 'command') {
            $value = $request->value ?? $trigger->value;
            if (! str_starts_with($value, '/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Buyruq / bilan boshlanishi kerak',
                ], 400);
            }
        }

        $trigger->update($request->only([
            'name', 'type', 'value', 'match_type',
            'funnel_id', 'step_id', 'priority', 'is_active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Trigger yangilandi',
        ]);
    }

    /**
     * Toggle trigger active status
     */
    public function toggleActive(Request $request, string $botId, string $triggerId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $trigger = TelegramTrigger::where('telegram_bot_id', $bot->id)
            ->where('id', $triggerId)
            ->firstOrFail();

        $trigger->update(['is_active' => ! $trigger->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $trigger->is_active,
            'message' => $trigger->is_active ? 'Trigger faollashtirildi' : 'Trigger o\'chirildi',
        ]);
    }

    /**
     * Delete trigger
     */
    public function destroy(Request $request, string $botId, string $triggerId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $trigger = TelegramTrigger::where('telegram_bot_id', $bot->id)
            ->where('id', $triggerId)
            ->firstOrFail();

        $trigger->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trigger o\'chirildi',
        ]);
    }

    /**
     * Test trigger matching
     */
    public function test(Request $request, string $botId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $request->validate([
            'text' => 'required|string|max:4096',
        ]);

        $text = $request->text;

        // Find matching triggers
        $triggers = TelegramTrigger::where('telegram_bot_id', $bot->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        $matches = [];

        foreach ($triggers as $trigger) {
            if ($trigger->matches($text)) {
                $matches[] = [
                    'id' => $trigger->id,
                    'name' => $trigger->name,
                    'type' => $trigger->type,
                    'value' => $trigger->value,
                    'match_type' => $trigger->match_type,
                    'priority' => $trigger->priority,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'text' => $text,
            'matches' => $matches,
            'will_trigger' => ! empty($matches) ? $matches[0] : null,
        ]);
    }
}
