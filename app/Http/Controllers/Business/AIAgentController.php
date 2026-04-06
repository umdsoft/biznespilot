<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\AgentConversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AIAgentController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('business.dashboard');
        }

        $conversations = AgentConversation::where('business_id', $business->id)
            ->where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'status', 'message_count', 'created_at', 'updated_at']);

        return Inertia::render('Business/AIAgent/Index', [
            'conversations' => $conversations,
        ]);
    }
}
