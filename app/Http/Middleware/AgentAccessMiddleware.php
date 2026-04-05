<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\Agent\Access\AgentAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agent tizimiga kirish middleware — DRY markaziy nuqta.
 *
 * Tekshiradi:
 * 1. Foydalanuvchi autentifikatsiya qilinganmi
 * 2. Biznesi bormi va faolmi
 * 3. Roli bu amalga ruxsat beradimi
 * 4. Tarif limiti oshmaganmi
 *
 * Ishlatish: Route::middleware('agent.access:ask')
 * Yoki: Route::middleware('agent.access:view_conversations')
 */
class AgentAccessMiddleware
{
    public function __construct(
        private AgentAccessService $accessService,
    ) {}

    public function handle(Request $request, Closure $next, string $action = 'ask'): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Autentifikatsiya talab qilinadi.',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        // Biznesni aniqlash
        $businessId = session('current_business_id');
        $business = $businessId ? Business::find($businessId) : ($user->business ?? $user->businesses()->first());

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi.',
                'error_code' => 'NO_BUSINESS',
            ], 422);
        }

        // Markaziy ruxsat tekshiruvi
        $access = $this->accessService->checkAccess($user, $business, $action);

        if (! $access['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $access['reason'],
                'error_code' => 'AGENT_ACCESS_DENIED',
            ], 403);
        }

        // Kontekstni request ga biriktirish — controller larda ishlatish uchun
        $request->merge([
            'agent_business' => $business,
            'agent_context' => $access['context'],
            'agent_allowed_agents' => $this->accessService->getAllowedAgents($access['context']),
            'agent_data_scope' => $this->accessService->getDataScope($user, $business),
        ]);

        return $next($request);
    }
}
