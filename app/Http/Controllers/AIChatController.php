<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\ClaudeAIService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AIChatController extends Controller
{
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Display chat interface
     */
    public function index()
    {
        $businessId = session('current_business_id');
        $userId = auth()->id();

        // Get recent conversations
        $conversations = AiConversation::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->active()
            ->orderBy('last_message_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Business/AI/Chat', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Get conversation with messages
     */
    public function show(AiConversation $conversation)
    {
        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    /**
     * Create new conversation
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'context_type' => 'nullable|string|in:general,strategy,insights,analytics,marketing,sales,competitor',
            'context_id' => 'nullable|integer',
        ]);

        $businessId = session('current_business_id');
        $userId = auth()->id();

        // Create conversation
        $conversation = AiConversation::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'context_type' => $request->context_type ?? 'general',
            'context_id' => $request->context_id,
            'messages' => [],
            'last_message_at' => now(),
        ]);

        // Add user message
        $conversation->addUserMessage($request->message);

        // Get AI response
        try {
            $messages = [
                ['role' => 'user', 'content' => $request->message],
            ];

            $response = $this->claudeAI->chat($messages);

            // Add assistant response
            $conversation->addAssistantMessage($response);

            // Generate title if not set
            $conversation->updateTitleIfNeeded();

            return response()->json([
                'conversation' => $conversation->fresh(),
                'message' => $response,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get AI response: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send message to existing conversation
     */
    public function sendMessage(Request $request, AiConversation $conversation)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        // Add user message
        $conversation->addUserMessage($request->message);

        // Get AI response
        try {
            // Prepare conversation history for Claude
            $messages = collect($conversation->messages)->map(function ($msg) {
                return [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            })->toArray();

            $response = $this->claudeAI->chat($messages);

            // Add assistant response
            $conversation->addAssistantMessage($response);

            return response()->json([
                'conversation' => $conversation->fresh(),
                'message' => $response,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get AI response: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive conversation
     */
    public function archive(AiConversation $conversation)
    {
        $conversation->archive();

        return response()->json([
            'message' => 'Conversation archived successfully',
        ]);
    }

    /**
     * Delete conversation
     */
    public function destroy(AiConversation $conversation)
    {
        $conversation->delete();

        return response()->json([
            'message' => 'Conversation deleted successfully',
        ]);
    }

    /**
     * Get conversation list
     */
    public function conversations(Request $request)
    {
        $businessId = session('current_business_id');
        $userId = auth()->id();

        $query = AiConversation::where('business_id', $businessId)
            ->where('user_id', $userId);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'archived') {
                $query->archived();
            }
        }

        // Filter by context type
        if ($request->has('context_type') && $request->context_type !== 'all') {
            $query->where('context_type', $request->context_type);
        }

        $conversations = $query->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return response()->json($conversations);
    }

    /**
     * Rate conversation
     */
    public function rate(Request $request, AiConversation $conversation)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $conversation->update([
            'rating' => $request->rating,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'message' => 'Thank you for your feedback!',
        ]);
    }
}
