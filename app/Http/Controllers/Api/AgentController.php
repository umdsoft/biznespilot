<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAgentDeepLayersJob;
use App\Models\AgentConversation;
use App\Models\AgentMessage;
use App\Services\Agent\Access\AgentAccessService;
use App\Services\Agent\OrchestratorService;
use App\Services\Agent\Pipeline\AgentJobState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AI Agent API boshqaruvchisi.
 *
 * Barcha so'rovlar AgentAccessService orqali tekshiriladi (DRY).
 * Rol, bo'lim, tarif — barchasi bitta joyda tekshiriladi.
 */
class AgentController extends Controller
{
    public function __construct(
        private OrchestratorService $orchestrator,
        private AgentAccessService $accessService,
    ) {}

    /**
     * POST /api/v1/agent/ask — asosiy kirish nuqtasi
     *
     * Tekshiruvlar: rol + tarif + kunlik limit + agent ruxsati
     */
    public function ask(Request $request): JsonResponse
    {
        // Yangi LAYERED FLOW: Layer 1 (instant) + Layer 2 (primary) sync,
        // Layer 3 (secondary) + Layer 4 (director) async (dispatchAfterResponse).
        //
        // Foydalanuvchi 5-15s ichida javob oladi, qo'shimcha tahlillar
        // background'da yuklanadi va frontend polling orqali oladi.

        set_time_limit(45); // Faqat L1+L2 uchun — 504 oldini olish
        ini_set('max_execution_time', 45);

        $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|uuid',
        ]);

        $user = Auth::user();
        $business = $this->resolveBusiness($user);

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi. Avval biznes yarating.',
            ], 422);
        }

        // Markaziy ruxsat tekshiruvi — rol + tarif + limit
        $access = $this->accessService->checkAccess($user, $business, 'ask');
        if (! $access['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $access['reason'],
                'error_code' => 'AGENT_ACCESS_DENIED',
            ], 403);
        }

        $allowedAgents = $this->accessService->getAllowedAgents($access['context']);

        // ─── SYNC: Layer 1 (instant) + Layer 2 (primary agent) ──────────────
        $result = $this->orchestrator->handleQuickResponse(
            message: $request->input('message'),
            businessId: $business->id,
            userId: $user->id,
            conversationId: $request->input('conversation_id'),
            allowedAgents: $allowedAgents,
        );

        if (! ($result['success'] ?? false)) {
            return response()->json($result, 500);
        }

        // ─── ASYNC: Layer 3 (secondary) + Layer 4 (director) ────────────────
        // dispatchAfterResponse() — PHP response'ni clientga jo'natgandan keyin
        // background'da ishlaydi (queue worker shart emas, register_shutdown_function).
        if (! empty($result['pending_layers'])) {
            ProcessAgentDeepLayersJob::dispatchAfterResponse(
                $result['job_id'],
                $request->input('message'),
                $business->id,
                $result['conversation_id'],
                $result['routing'],
            );
        }

        return response()->json($result, 200);
    }

    /**
     * GET /api/v1/agent/job/{jobId} — async layer'lar holatini polling.
     *
     * Frontend bu endpointni har 3-5s'da chaqiradi va yangi tayyor bo'lgan
     * layer'larni renderlaydi. Tugagandan keyin polling to'xtaydi.
     */
    public function jobStatus(string $jobId): JsonResponse
    {
        $state = AgentJobState::toResponse($jobId);

        if (! $state) {
            return response()->json([
                'success' => false,
                'message' => 'Job topilmadi yoki muddati tugagan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            ...$state,
        ]);
    }

    /**
     * GET /api/v1/agent/conversations — suhbatlar ro'yxati
     *
     * owner/admin: barcha suhbatlar, boshqalar: faqat o'zining
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = Auth::user();
        $business = $this->resolveBusiness($user);

        if (! $business) {
            return response()->json(['success' => false, 'message' => 'Biznes topilmadi.'], 422);
        }

        $access = $this->accessService->checkAccess($user, $business, 'view_conversations');
        if (! $access['allowed']) {
            return response()->json(['success' => false, 'message' => $access['reason']], 403);
        }

        $query = AgentConversation::where('business_id', $business->id);

        // owner/admin barcha suhbatlarni ko'radi, boshqalar faqat o'zini
        $context = $access['context'];
        if (! in_array($context['role'], ['owner', 'admin'])) {
            $query->where('user_id', $user->id);
        }

        $conversations = $query
            ->orderByDesc('updated_at')
            ->limit($request->input('limit', 20))
            ->get(['id', 'user_id', 'status', 'started_at', 'closed_at', 'message_count', 'created_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * GET /api/v1/agent/conversations/{id} — bitta suhbat
     */
    public function conversation(string $id): JsonResponse
    {
        $user = Auth::user();
        $business = $this->resolveBusiness($user);

        $access = $this->accessService->checkAccess($user, $business, 'view_conversations');
        if (! $access['allowed']) {
            return response()->json(['success' => false, 'message' => $access['reason']], 403);
        }

        $query = AgentConversation::where('id', $id)->where('business_id', $business->id);

        // owner/admin har qanday suhbatni ko'radi
        if (! in_array($access['context']['role'], ['owner', 'admin'])) {
            $query->where('user_id', $user->id);
        }

        $conversation = $query->first();
        if (! $conversation) {
            return response()->json(['success' => false, 'message' => 'Suhbat topilmadi.'], 404);
        }

        return response()->json(['success' => true, 'data' => $conversation]);
    }

    /**
     * GET /api/v1/agent/conversations/{id}/messages — suhbat xabarlari
     */
    public function messages(string $id, Request $request): JsonResponse
    {
        $user = Auth::user();
        $business = $this->resolveBusiness($user);

        $access = $this->accessService->checkAccess($user, $business, 'view_conversations');
        if (! $access['allowed']) {
            return response()->json(['success' => false, 'message' => $access['reason']], 403);
        }

        // Suhbatni tekshirish (rol bo'yicha)
        $query = AgentConversation::where('id', $id)->where('business_id', $business->id);
        if (! in_array($access['context']['role'], ['owner', 'admin'])) {
            $query->where('user_id', $user->id);
        }

        if (! $query->exists()) {
            return response()->json(['success' => false, 'message' => 'Suhbat topilmadi.'], 404);
        }

        $messages = AgentMessage::where('conversation_id', $id)
            ->orderBy('created_at')
            ->limit($request->input('limit', 50))
            ->get(['id', 'role', 'content', 'agent_type', 'model_used', 'processing_time_ms', 'created_at']);

        return response()->json(['success' => true, 'data' => $messages]);
    }

    /**
     * Foydalanuvchining joriy biznesini aniqlash.
     * Session yoki bevosita egalik orqali.
     */
    private function resolveBusiness(object $user): ?object
    {
        // Avval session dagi biznesni tekshirish
        $businessId = session('current_business_id');

        if ($businessId) {
            $business = \App\Models\Business::find($businessId);
            if ($business) {
                return $business;
            }
        }

        // Fallback: foydalanuvchining birinchi biznesi
        return $user->business ?? $user->businesses()->first();
    }
}
