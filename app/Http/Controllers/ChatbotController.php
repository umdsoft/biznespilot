<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatbotController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        // Get recent chat messages
        $messages = ChatMessage::where('user_id', $user->id)
            ->where('business_id', $currentBusiness->id)
            ->orderBy('created_at', 'asc')
            ->take(50) // Last 50 messages
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'message' => $msg->message,
                    'ai_model' => $msg->ai_model,
                    'created_at' => $msg->created_at->format('H:i'),
                ];
            });

        return Inertia::render('Business/Chatbot/Index', [
            'messages' => $messages,
            'hasApiKey' => $user->settings &&
                         ($user->settings->openai_api_key || $user->settings->claude_api_key),
        ]);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        // Save user message
        $userMessage = ChatMessage::create([
            'user_id' => $user->id,
            'business_id' => $currentBusiness->id,
            'role' => 'user',
            'message' => $validated['message'],
        ]);

        try {
            // Get AI response with business context
            $context = [
                'business_name' => $currentBusiness->name,
                'business_type' => $currentBusiness->type,
            ];

            $aiResponse = $this->aiService->getBusinessAdvice(
                $validated['message'],
                $context
            );

            // Get current AI model being used
            $userSettings = $user->settings;
            $aiModel = $userSettings ? $userSettings->preferred_ai_model : 'gpt-4';

            // Save AI response
            $assistantMessage = ChatMessage::create([
                'user_id' => $user->id,
                'business_id' => $currentBusiness->id,
                'role' => 'assistant',
                'message' => $aiResponse,
                'ai_model' => $aiModel,
                'context' => $context,
            ]);

            return response()->json([
                'success' => true,
                'userMessage' => [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'message' => $userMessage->message,
                    'created_at' => $userMessage->created_at->format('H:i'),
                ],
                'assistantMessage' => [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'message' => $assistantMessage->message,
                    'ai_model' => $assistantMessage->ai_model,
                    'created_at' => $assistantMessage->created_at->format('H:i'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function clearHistory(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        ChatMessage::where('user_id', $user->id)
            ->where('business_id', $currentBusiness->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat tarixi tozalandi',
        ]);
    }
}
