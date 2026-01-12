<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Telegram\TelegramFunnelController as BaseTelegramFunnelController;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\Request;
use App\Models\TelegramBot;
use App\Models\TelegramFunnel;

class TelegramFunnelController extends BaseTelegramFunnelController
{
    /**
     * List all funnels for a bot
     */
    public function index(Request $request, string $botId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnels = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->withCount('steps')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($funnel) => [
                'id' => $funnel->id,
                'name' => $funnel->name,
                'description' => $funnel->description,
                'is_active' => $funnel->is_active,
                'steps_count' => $funnel->steps_count,
                'created_at' => $funnel->created_at->format('d.m.Y'),
            ]);

        return Inertia::render('Marketing/Telegram/Funnels/Index', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'funnels' => $funnels,
        ]);
    }

    /**
     * Show funnel builder
     */
    public function show(Request $request, string $botId, string $funnelId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnel = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->where('id', $funnelId)
            ->with('steps')
            ->firstOrFail();

        $steps = $funnel->steps->map(fn($step) => [
            'id' => $step->id,
            'name' => $step->name,
            'step_type' => $step->step_type,
            'content' => $step->content,
            'keyboard' => $step->keyboard,
            'input_type' => $step->input_type,
            'input_field' => $step->input_field,
            'validation' => $step->validation,
            'next_step_id' => $step->next_step_id,
            'action_type' => $step->action_type,
            'action_config' => $step->action_config,
            'condition' => $step->condition,
            'condition_true_step_id' => $step->condition_true_step_id,
            'condition_false_step_id' => $step->condition_false_step_id,
            'position_x' => $step->position_x,
            'position_y' => $step->position_y,
            'order' => $step->order,
            // Marketing features
            'subscribe_check' => $step->subscribe_check,
            'subscribe_true_step_id' => $step->subscribe_true_step_id,
            'subscribe_false_step_id' => $step->subscribe_false_step_id,
            'quiz' => $step->quiz,
            'ab_test' => $step->ab_test,
            'tag' => $step->tag,
        ]);

        return Inertia::render('Marketing/Telegram/Funnels/Builder', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'funnel' => [
                'id' => $funnel->id,
                'name' => $funnel->name,
                'description' => $funnel->description,
                'is_active' => $funnel->is_active,
                'first_step_id' => $funnel->first_step_id,
                'completion_message' => $funnel->completion_message,
            ],
            'steps' => $steps,
        ]);
    }
}
