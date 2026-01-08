<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatbotController extends Controller
{
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
            ->take(50)
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
            'hasApiKey' => false, // AI is disabled
            'aiDisabled' => true,
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

        // Return message that AI is not available
        $assistantMessage = ChatMessage::create([
            'user_id' => $user->id,
            'business_id' => $currentBusiness->id,
            'role' => 'assistant',
            'message' => 'AI yordamchi hozircha mavjud emas. Bu funksiya keyingi versiyalarda qo\'shiladi. Hozircha algoritmik tahlillar va hisobotlardan foydalanishingiz mumkin.',
            'ai_model' => 'disabled',
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
