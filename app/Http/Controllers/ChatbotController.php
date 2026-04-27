<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatbotController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $user = Auth::user();
        // Team-member ham team membership business'ni ko'ra olishi uchun trait orqali.
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return redirect()->route('login');
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
            'panelType' => $this->detectPanelType($currentBusiness),
        ]);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = Auth::user();
        // HasCurrentBusiness: $user->businesses() faqat OWNED qaytaradi (team-member uchun null).
        $currentBusiness = $this->getCurrentBusiness();
        if (! $currentBusiness) {
            return response()->json(['success' => false, 'message' => 'Biznes topilmadi'], 403);
        }

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
        $currentBusiness = $this->getCurrentBusiness();
        if (! $currentBusiness) {
            return response()->json(['success' => false, 'message' => 'Biznes topilmadi'], 403);
        }

        ChatMessage::where('user_id', $user->id)
            ->where('business_id', $currentBusiness->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat tarixi tozalandi',
        ]);
    }
}
