<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Telegram\TelegramTriggerController as BaseTelegramTriggerController;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\TelegramBot;
use App\Models\TelegramTrigger;
use App\Models\TelegramFunnel;

class TelegramTriggerController extends BaseTelegramTriggerController
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
            ->map(fn($trigger) => [
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
        return Inertia::render('Marketing/Telegram/Triggers/Index', [
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
}
