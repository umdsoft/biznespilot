<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\EskizAccount;
use App\Models\PlayMobileAccount;
use App\Models\Lead;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Services\EskizSmsService;
use App\Services\PlayMobileSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SmsController extends Controller
{
    use HasCurrentBusiness;

    protected EskizSmsService $eskizService;
    protected PlayMobileSmsService $playmobileService;

    public function __construct(EskizSmsService $eskizService, PlayMobileSmsService $playmobileService)
    {
        $this->eskizService = $eskizService;
        $this->playmobileService = $playmobileService;
    }

    /**
     * Get active SMS provider for business
     */
    protected function getActiveProvider($business): array
    {
        $eskizAccount = EskizAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($eskizAccount) {
            return [
                'provider' => 'eskiz',
                'account' => $eskizAccount,
                'service' => $this->eskizService->setAccount($eskizAccount),
            ];
        }

        $playmobileAccount = PlayMobileAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($playmobileAccount) {
            return [
                'provider' => 'playmobile',
                'account' => $playmobileAccount,
                'service' => $this->playmobileService->setAccount($playmobileAccount),
            ];
        }

        return [
            'provider' => null,
            'account' => null,
            'service' => null,
        ];
    }

    /**
     * SMS Settings page
     */
    public function settings()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        $eskizAccount = EskizAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        $playmobileAccount = PlayMobileAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        $templates = SmsTemplate::where('business_id', $business->id)
            ->active()
            ->orderBy('usage_count', 'desc')
            ->get();

        // Get combined statistics from all providers
        $stats = $this->eskizService->getStatistics($business->id);

        return Inertia::render('Business/Settings/Sms', [
            'eskizAccount' => $eskizAccount ? [
                'id' => $eskizAccount->id,
                'email' => $eskizAccount->email,
                'sender_name' => $eskizAccount->sender_name,
                'is_active' => $eskizAccount->is_active,
                'balance' => $eskizAccount->balance,
                'last_sync_at' => $eskizAccount->last_sync_at?->format('d.m.Y H:i'),
                'token_valid' => $eskizAccount->isTokenValid(),
            ] : null,
            'playmobileAccount' => $playmobileAccount ? [
                'id' => $playmobileAccount->id,
                'login' => $playmobileAccount->login,
                'originator' => $playmobileAccount->originator,
                'is_active' => $playmobileAccount->is_active,
                'balance' => $playmobileAccount->balance,
                'last_sync_at' => $playmobileAccount->last_sync_at?->format('d.m.Y H:i'),
            ] : null,
            'templates' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'content' => $template->content,
                    'category' => $template->category,
                    'usage_count' => $template->usage_count,
                ];
            }),
            'stats' => $stats,
        ]);
    }

    /**
     * Connect Eskiz account
     */
    public function connect(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'sender_name' => 'required|string|max:11|regex:/^[a-zA-Z0-9]+$/',
        ], [
            'sender_name.regex' => 'Yuboruvchi nomi faqat lotin harflari va raqamlardan iborat bo\'lishi kerak',
            'sender_name.max' => 'Yuboruvchi nomi maksimum 11 ta belgidan iborat bo\'lishi kerak',
        ]);

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Test connection first
        $result = $this->eskizService->testConnection($validated['email'], $validated['password']);

        if (!$result['success']) {
            $errorMsg = $result['error'] ?? 'Noma\'lum xato';

            // Provide helpful guidance based on error
            if (str_contains($errorMsg, 'email') || str_contains($errorMsg, 'пароль') || str_contains($errorMsg, 'password')) {
                $errorMsg .= '. Eslatma: Eskiz.uz API uchun alohida parol kerak bo\'lishi mumkin. Eskiz kabinetingizdan API parolini tekshiring.';
            }

            return back()->with('error', 'Eskiz bilan bog\'lanishda xatolik: ' . $errorMsg);
        }

        // Disable PlayMobile if active (only one provider at a time)
        PlayMobileAccount::where('business_id', $business->id)->update(['is_active' => false]);

        // Create or update account
        EskizAccount::updateOrCreate(
            ['business_id' => $business->id],
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'sender_name' => strtoupper($validated['sender_name']),
                'access_token' => $result['token'],
                'token_expires_at' => $result['expires_at'],
                'is_active' => true,
                'last_sync_at' => now(),
                'last_error' => null,
            ]
        );

        // Clear cache
        Cache::forget("sms_status_{$business->id}");

        return back()->with('success', 'Eskiz muvaffaqiyatli ulandi!');
    }

    /**
     * Disconnect Eskiz account
     */
    public function disconnect()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        EskizAccount::where('business_id', $business->id)->update([
            'is_active' => false,
            'access_token' => null,
        ]);

        Cache::forget("sms_status_{$business->id}");

        return back()->with('success', 'Eskiz uzildi');
    }

    /**
     * Connect PlayMobile account
     */
    public function connectPlaymobile(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string|min:4',
            'originator' => 'required|string|max:20',
        ]);

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Test connection first
        $result = $this->playmobileService->testConnection($validated['login'], $validated['password']);

        if (!$result['success']) {
            return back()->with('error', 'PlayMobile bilan bog\'lanishda xatolik: ' . ($result['error'] ?? 'Noma\'lum xato'));
        }

        // Disable Eskiz if active (only one provider at a time)
        EskizAccount::where('business_id', $business->id)->update([
            'is_active' => false,
            'access_token' => null,
        ]);

        // Create or update account
        PlayMobileAccount::updateOrCreate(
            ['business_id' => $business->id],
            [
                'login' => $validated['login'],
                'password' => $validated['password'],
                'originator' => $validated['originator'],
                'is_active' => true,
                'last_sync_at' => now(),
                'last_error' => null,
            ]
        );

        // Clear cache
        Cache::forget("sms_status_{$business->id}");

        return back()->with('success', 'PlayMobile muvaffaqiyatli ulandi!');
    }

    /**
     * Disconnect PlayMobile account
     */
    public function disconnectPlaymobile()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        PlayMobileAccount::where('business_id', $business->id)->update([
            'is_active' => false,
        ]);

        Cache::forget("sms_status_{$business->id}");

        return back()->with('success', 'PlayMobile uzildi');
    }

    /**
     * Refresh balance (Eskiz only)
     */
    public function refreshBalance()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $account = EskizAccount::where('business_id', $business->id)->active()->first();

        if (!$account) {
            return response()->json(['error' => 'Eskiz hisobi topilmadi'], 404);
        }

        $balance = $this->eskizService->setAccount($account)->getBalance();

        if ($balance === null) {
            return response()->json(['error' => 'Balansni olishda xatolik'], 500);
        }

        return response()->json(['balance' => $balance]);
    }

    /**
     * Check if SMS is configured for the business
     */
    public function status()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['connected' => false]);
        }

        $provider = $this->getActiveProvider($business);

        if (!$provider['account']) {
            return response()->json(['connected' => false]);
        }

        $senderName = $provider['provider'] === 'eskiz'
            ? $provider['account']->sender_name
            : $provider['account']->originator;

        return response()->json([
            'connected' => true,
            'provider' => $provider['provider'],
            'sender_name' => $senderName,
            'balance' => $provider['account']->balance,
        ]);
    }

    /**
     * Send SMS to a lead
     */
    public function sendToLead(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1600',
            'template_id' => 'nullable|uuid|exists:sms_templates,id',
        ]);

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        // Check lead belongs to business
        if ((string) $lead->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        if (!$lead->phone) {
            return response()->json(['error' => 'Lead telefon raqami yo\'q'], 400);
        }

        $provider = $this->getActiveProvider($business);

        if (!$provider['service']) {
            return response()->json([
                'error' => 'SMS provayder sozlanmagan. Avval Sozlamalar > SMS bo\'limidan ulang.',
            ], 400);
        }

        // If template was used, increment usage
        if ($validated['template_id']) {
            $template = SmsTemplate::find($validated['template_id']);
            if ($template) {
                $template->incrementUsage();
            }
        }

        $result = $provider['service']->sendSms(
            $lead->phone,
            $validated['message'],
            $lead,
            $validated['template_id'] ?? null
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 500);
        }

        // Update lead's last_contacted_at
        $lead->update(['last_contacted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'SMS muvaffaqiyatli yuborildi!',
            'parts_count' => $result['parts_count'] ?? 1,
            'provider' => $provider['provider'],
        ]);
    }

    /**
     * Send bulk SMS to multiple leads
     */
    public function bulkSend(Request $request)
    {
        $validated = $request->validate([
            'lead_ids' => 'required|array|min:1|max:500',
            'lead_ids.*' => 'required|uuid',
            'message' => 'required|string|max:1600',
            'template_id' => 'nullable|uuid|exists:sms_templates,id',
        ]);

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $provider = $this->getActiveProvider($business);

        if (!$provider['service']) {
            return response()->json([
                'error' => 'SMS provayder sozlanmagan. Avval Sozlamalar > SMS bo\'limidan ulang.',
            ], 400);
        }

        // Get leads that belong to this business and have phone numbers
        $leads = Lead::whereIn('id', $validated['lead_ids'])
            ->where('business_id', $business->id)
            ->whereNotNull('phone')
            ->get();

        if ($leads->isEmpty()) {
            return response()->json(['error' => 'Telefon raqamli lidlar topilmadi'], 400);
        }

        // If template was used, increment usage
        if ($validated['template_id']) {
            $template = SmsTemplate::find($validated['template_id']);
            if ($template) {
                $template->incrementUsage();
            }
        }

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($leads as $lead) {
            // Replace placeholders in message for each lead
            $personalizedMessage = $validated['message'];
            $personalizedMessage = str_replace('{name}', $lead->name ?? '', $personalizedMessage);
            $personalizedMessage = str_replace('{phone}', $lead->phone ?? '', $personalizedMessage);
            $personalizedMessage = str_replace('{company}', $lead->company ?? '', $personalizedMessage);
            $personalizedMessage = str_replace('{email}', $lead->email ?? '', $personalizedMessage);

            $result = $provider['service']->sendSms(
                $lead->phone,
                $personalizedMessage,
                $lead,
                $validated['template_id'] ?? null
            );

            if ($result['success']) {
                $sent++;
                // Update lead's last_contacted_at
                $lead->update(['last_contacted_at' => now()]);
            } else {
                $failed++;
                $errors[] = [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'error' => $result['error'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $leads->count(),
            'errors' => $errors,
            'message' => "{$sent} ta SMS yuborildi" . ($failed > 0 ? ", {$failed} ta xatolik" : ''),
        ]);
    }

    /**
     * Get SMS history for a lead
     */
    public function leadHistory(Lead $lead)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        if ((string) $lead->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        $messages = SmsMessage::where('lead_id', $lead->id)
            ->with(['sender:id,name', 'template:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'status' => $msg->status,
                    'parts_count' => $msg->parts_count,
                    'provider' => $msg->provider ?? 'eskiz',
                    'sender' => $msg->sender?->name,
                    'template' => $msg->template?->name,
                    'sent_at' => $msg->sent_at?->format('d.m.Y H:i'),
                    'created_at' => $msg->created_at->format('d.m.Y H:i'),
                ];
            });

        return response()->json($messages);
    }

    /**
     * Get all SMS history for business
     */
    public function history(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $perPage = $request->input('per_page', 25);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = SmsMessage::where('business_id', $business->id)
            ->with(['lead:id,name,phone', 'sender:id,name', 'template:id,name']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $messages->getCollection()->transform(function ($msg) {
            return [
                'id' => $msg->id,
                'phone' => $msg->phone,
                'message' => $msg->message,
                'status' => $msg->status,
                'parts_count' => $msg->parts_count,
                'provider' => $msg->provider ?? 'eskiz',
                'error_message' => $msg->error_message,
                'lead' => $msg->lead ? [
                    'id' => $msg->lead->id,
                    'name' => $msg->lead->name,
                ] : null,
                'sender' => $msg->sender?->name,
                'template' => $msg->template?->name,
                'sent_at' => $msg->sent_at?->format('d.m.Y H:i'),
                'created_at' => $msg->created_at->format('d.m.Y H:i'),
            ];
        });

        return Inertia::render('Business/Sms/History', [
            'messages' => $messages,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Get SMS statistics
     */
    public function statistics()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $provider = $this->getActiveProvider($business);

        $stats = $provider['service']
            ? $provider['service']->getStatistics($business->id)
            : $this->eskizService->getStatistics($business->id);

        $dailyStats = $this->eskizService->getDailyStatistics($business->id, 30);

        return Inertia::render('Business/Sms/Statistics', [
            'stats' => $stats,
            'dailyStats' => $dailyStats,
            'balance' => $provider['account']?->balance ?? 0,
            'provider' => $provider['provider'],
        ]);
    }

    // ==================== TEMPLATES ====================

    /**
     * List templates
     */
    public function templates()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $templates = SmsTemplate::where('business_id', $business->id)
            ->orderBy('usage_count', 'desc')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'content' => $template->content,
                    'category' => $template->category,
                    'is_active' => $template->is_active,
                    'usage_count' => $template->usage_count,
                ];
            });

        return response()->json($templates);
    }

    /**
     * Store template
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:1600',
            'category' => 'nullable|string|max:50',
        ]);

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $template = SmsTemplate::create([
            'business_id' => $business->id,
            ...$validated,
        ]);

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'content' => $template->content,
            'category' => $template->category,
            'usage_count' => 0,
        ], 201);
    }

    /**
     * Update template
     */
    public function updateTemplate(Request $request, SmsTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || (string) $template->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:1600',
            'category' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'content' => $template->content,
            'category' => $template->category,
            'is_active' => $template->is_active,
            'usage_count' => $template->usage_count,
        ]);
    }

    /**
     * Delete template
     */
    public function destroyTemplate(SmsTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || (string) $template->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        $template->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Preview template with lead data
     */
    public function previewTemplate(Request $request, SmsTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || (string) $template->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        $leadId = $request->input('lead_id');
        $lead = $leadId ? Lead::find($leadId) : null;

        $preview = $lead ? $template->renderForLead($lead) : $template->content;
        $partsCount = $this->eskizService->calculateSmsParts($preview);

        return response()->json([
            'preview' => $preview,
            'parts_count' => $partsCount,
        ]);
    }

    /**
     * Calculate SMS parts for a message
     */
    public function calculateParts(Request $request)
    {
        $message = $request->input('message', '');
        $partsCount = $this->eskizService->calculateSmsParts($message);

        return response()->json([
            'parts_count' => $partsCount,
            'char_count' => mb_strlen($message),
        ]);
    }
}
