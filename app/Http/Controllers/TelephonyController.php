<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\MoiZvonkiAccount;
use App\Models\PbxAccount;
use App\Models\SipuniAccount;
use App\Models\UtelAccount;
use App\Services\MoiZvonkiService;
use App\Services\OnlinePbxService;
use App\Services\SipuniService;
use App\Services\UtelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TelephonyController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected SipuniService $sipuniService,
        protected OnlinePbxService $onlinePbxService,
        protected MoiZvonkiService $moiZvonkiService,
        protected UtelService $utelService
    ) {}

    /**
     * Get active telephony provider
     */
    protected function getActiveProvider($business): array
    {
        // Check OnlinePBX account first
        $pbxAccount = PbxAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($pbxAccount) {
            return [
                'provider' => 'onlinepbx',
                'account' => $pbxAccount,
                'service' => $this->onlinePbxService->setAccount($pbxAccount),
            ];
        }

        // Check SipUni account
        $sipuniAccount = SipuniAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($sipuniAccount) {
            return [
                'provider' => 'sipuni',
                'account' => $sipuniAccount,
                'service' => $this->sipuniService->setAccount($sipuniAccount),
            ];
        }

        // Check MoiZvonki account
        $moiZvonkiAccount = MoiZvonkiAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($moiZvonkiAccount) {
            return [
                'provider' => 'moizvonki',
                'account' => $moiZvonkiAccount,
                'service' => $this->moiZvonkiService->setAccount($moiZvonkiAccount),
            ];
        }

        // Check UTEL account (O'zbekiston)
        $utelAccount = UtelAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($utelAccount) {
            return [
                'provider' => 'utel',
                'account' => $utelAccount,
                'service' => $this->utelService->setAccount($utelAccount),
            ];
        }

        return [
            'provider' => null,
            'account' => null,
            'service' => null,
        ];
    }

    /**
     * Telephony settings page
     */
    public function settings()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        // OnlinePBX account
        $pbxAccount = PbxAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->where('provider', PbxAccount::PROVIDER_ONLINEPBX)
            ->first();

        $sipuniAccount = SipuniAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        $moiZvonkiAccount = MoiZvonkiAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        $utelAccount = UtelAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        // Get statistics from database
        $stats = $this->getDefaultStatistics($business->id);

        // Generate webhook URLs
        $webhookBaseUrl = config('app.url');

        return Inertia::render('Business/Settings/Telephony', [
            'pbxAccount' => $pbxAccount ? [
                'id' => $pbxAccount->id,
                'name' => $pbxAccount->name,
                'provider' => $pbxAccount->provider,
                'api_url' => $pbxAccount->api_url,
                'caller_id' => $pbxAccount->caller_id,
                'extension' => $pbxAccount->extension,
                'is_active' => $pbxAccount->is_active,
                'balance' => $pbxAccount->balance,
                'last_sync_at' => $pbxAccount->last_sync_at?->format('d.m.Y H:i'),
            ] : null,
            'sipuniAccount' => $sipuniAccount ? [
                'id' => $sipuniAccount->id,
                'name' => $sipuniAccount->name,
                'caller_id' => $sipuniAccount->caller_id,
                'extension' => $sipuniAccount->extension,
                'is_active' => $sipuniAccount->is_active,
                'balance' => $sipuniAccount->balance,
                'last_sync_at' => $sipuniAccount->last_sync_at?->format('d.m.Y H:i'),
            ] : null,
            'moiZvonkiAccount' => $moiZvonkiAccount ? [
                'id' => $moiZvonkiAccount->id,
                'name' => $moiZvonkiAccount->name,
                'email' => $moiZvonkiAccount->email,
                'api_url' => $moiZvonkiAccount->api_url,
                'is_active' => $moiZvonkiAccount->is_active,
                'last_sync_at' => $moiZvonkiAccount->last_sync_at?->format('d.m.Y H:i'),
            ] : null,
            'utelAccount' => $utelAccount ? [
                'id' => $utelAccount->id,
                'name' => $utelAccount->name,
                'email' => $utelAccount->email,
                'caller_id' => $utelAccount->caller_id,
                'extension' => $utelAccount->extension,
                'is_active' => $utelAccount->is_active,
                'balance' => $utelAccount->balance,
                'currency' => $utelAccount->currency,
                'last_sync_at' => $utelAccount->last_sync_at?->format('d.m.Y H:i'),
            ] : null,
            'stats' => $stats,
            'webhookUrls' => [
                'onlinepbx' => $webhookBaseUrl.'/api/webhooks/pbx/onlinepbx',
                'moizvonki' => $webhookBaseUrl.'/api/webhooks/moizvonki/'.$business->id,
                'utel' => $webhookBaseUrl.'/api/webhooks/utel/'.$business->id,
            ],
        ]);
    }

    /**
     * Connect PBX account
     */
    public function connectPbx(Request $request)
    {
        $validated = $request->validate([
            'api_url' => 'required|url',
            'api_key' => 'required|string',
            'api_secret' => 'nullable|string',
            'caller_id' => 'required|string|max:20',
            'extension' => 'nullable|string|max:10',
        ]);

        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Test connection using OnlinePBX service
        $result = $this->onlinePbxService->testConnection(
            $validated['api_url'],
            $validated['api_key'],
            $validated['api_secret'] ?? null
        );

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        // Deactivate existing PBX accounts
        PbxAccount::where('business_id', $business->id)->update(['is_active' => false]);

        // Create new OnlinePBX account
        PbxAccount::create([
            'business_id' => $business->id,
            'provider' => PbxAccount::PROVIDER_ONLINEPBX,
            'name' => 'OnlinePBX',
            'api_url' => $validated['api_url'],
            'api_key' => $validated['api_key'],
            'api_secret' => $validated['api_secret'],
            'caller_id' => $validated['caller_id'],
            'extension' => $validated['extension'],
            'is_active' => true,
            'last_sync_at' => now(),
        ]);

        return back()->with('success', 'OnlinePBX muvaffaqiyatli ulandi!');
    }

    /**
     * Disconnect PBX account
     */
    public function disconnectPbx()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        PbxAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return back()->with('success', 'PBX uzildi');
    }

    /**
     * Connect SipUni account
     */
    public function connectSipuni(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'caller_id' => 'required|string|max:20',
            'extension' => 'nullable|string|max:10',
        ]);

        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Test connection
        $result = $this->sipuniService->testConnection(
            $validated['api_key'],
            $validated['api_secret']
        );

        // Log the result for debugging
        Log::info('SipUni connection result', $result);

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        // Deactivate existing SipUni accounts
        SipuniAccount::where('business_id', $business->id)->update(['is_active' => false]);

        // Create new account
        SipuniAccount::create([
            'business_id' => $business->id,
            'name' => 'SipUni',
            'api_key' => $validated['api_key'],
            'api_secret' => $validated['api_secret'],
            'caller_id' => $validated['caller_id'],
            'extension' => $validated['extension'] ?? '100',
            'balance' => $result['balance'] ?? 0,
            'is_active' => true,
            'last_sync_at' => now(),
        ]);

        return back()->with('success', 'SipUni muvaffaqiyatli ulandi!');
    }

    /**
     * Disconnect SipUni account
     */
    public function disconnectSipuni()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        SipuniAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return back()->with('success', 'SipUni uzildi');
    }

    /**
     * Connect MoiZvonki account
     */
    public function connectMoiZvonki(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'api_url' => 'required|string',
            'api_key' => 'required|string',
        ]);

        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Test connection
        $result = $this->moiZvonkiService->testConnection(
            $validated['api_url'],
            $validated['api_key'],
            $validated['email']
        );

        // Log the result for debugging
        Log::info('MoiZvonki connection result', $result);

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        // Deactivate existing MoiZvonki accounts
        MoiZvonkiAccount::where('business_id', $business->id)->update(['is_active' => false]);

        // Create new account
        MoiZvonkiAccount::create([
            'business_id' => $business->id,
            'name' => 'Moi Zvonki',
            'email' => $validated['email'],
            'api_url' => $validated['api_url'],
            'api_key' => $validated['api_key'],
            'is_active' => true,
            'last_sync_at' => now(),
        ]);

        return back()->with('success', 'Moi Zvonki muvaffaqiyatli ulandi!');
    }

    /**
     * Disconnect MoiZvonki account
     */
    public function disconnectMoiZvonki()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        MoiZvonkiAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return back()->with('success', 'Moi Zvonki uzildi');
    }

    /**
     * Sync MoiZvonki call history
     */
    public function syncMoiZvonki()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $account = MoiZvonkiAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return back()->with('error', 'Moi Zvonki ulangan emas');
        }

        $result = $this->moiZvonkiService->setAccount($account)->syncCallHistory();

        if ($result['success']) {
            return back()->with('success', "Sinxronizatsiya tugadi: {$result['synced']} ta qo'ng'iroq, {$result['created']} ta yangi");
        }

        return back()->with('error', $result['error']);
    }

    /**
     * Connect UTEL account (O'zbekiston)
     */
    public function connectUtel(Request $request)
    {
        $validated = $request->validate([
            'subdomain' => 'required|string|max:50|regex:/^[a-zA-Z0-9]+$/',
            'email' => 'required|email',
            'password' => 'required|string|min:4',
        ]);

        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Build API URL from subdomain
        $apiUrl = 'https://api.' . strtolower($validated['subdomain']) . '.utel.uz/api';

        // Set API URL and test connection
        $this->utelService->setBaseUrl($apiUrl);
        $result = $this->utelService->testConnection(
            $validated['email'],
            $validated['password']
        );

        // Log the result for debugging
        Log::info('UTEL connection result', $result);

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        // Deactivate existing UTEL accounts
        UtelAccount::where('business_id', $business->id)->update(['is_active' => false]);

        // Create new account with API URL in settings
        $account = UtelAccount::create([
            'business_id' => $business->id,
            'name' => 'UTEL',
            'email' => $validated['email'],
            'password' => Crypt::encryptString($validated['password']),
            'access_token' => $result['token'] ?? null,
            'token_expires_at' => isset($result['expires_at']) ? \Carbon\Carbon::parse($result['expires_at']) : now()->addHours(24),
            'is_active' => true,
            'last_sync_at' => now(),
            'settings' => [
                'api_url' => $apiUrl,
                'subdomain' => strtolower($validated['subdomain']),
            ],
        ]);

        // Automatically configure webhook for real-time call notifications
        try {
            $webhookUrl = config('app.url') . '/api/webhooks/utel/' . $business->id;
            $this->utelService->setAccount($account);
            $webhookResult = $this->utelService->configureWebhook($webhookUrl);

            if ($webhookResult['success']) {
                Log::info('UTEL webhook configured successfully', [
                    'business_id' => $business->id,
                    'webhook_url' => $webhookUrl,
                ]);
            } else {
                Log::warning('UTEL webhook configuration failed', [
                    'business_id' => $business->id,
                    'error' => $webhookResult['error'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('UTEL webhook configuration error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'UTEL muvaffaqiyatli ulandi!');
    }

    /**
     * Disconnect UTEL account
     */
    public function disconnectUtel()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        UtelAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return back()->with('success', 'UTEL uzildi');
    }

    /**
     * Sync UTEL call history
     */
    public function syncUtel()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $account = UtelAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return back()->with('error', 'UTEL ulangan emas');
        }

        $result = $this->utelService->setAccount($account)->syncCallHistory();

        if ($result['success']) {
            return back()->with('success', "Sinxronizatsiya tugadi: {$result['synced']} ta qo'ng'iroq, {$result['created']} ta yangi");
        }

        return back()->with('error', $result['error']);
    }

    /**
     * Configure UTEL webhook for real-time call notifications
     */
    public function configureUtelWebhook()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $account = UtelAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return response()->json(['error' => 'UTEL ulangan emas'], 400);
        }

        try {
            $webhookUrl = config('app.url') . '/api/webhooks/utel/' . $business->id;
            $this->utelService->setAccount($account);
            $result = $this->utelService->configureWebhook($webhookUrl);

            if ($result['success']) {
                Log::info('UTEL webhook configured manually', [
                    'business_id' => $business->id,
                    'webhook_url' => $webhookUrl,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook muvaffaqiyatli sozlandi! Endi qo\'ng\'iroqlar avtomatik sinxronlanadi.',
                    'webhook_url' => $webhookUrl,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Webhook sozlashda xatolik',
            ], 400);
        } catch (\Exception $e) {
            Log::error('UTEL webhook configuration error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh UTEL balance
     */
    public function refreshUtelBalance()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $account = UtelAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return response()->json(['error' => 'UTEL ulangan emas'], 400);
        }

        $result = $this->utelService->setAccount($account)->getBalance();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'balance' => $result['data']['balance'],
                'currency' => $result['data']['currency'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Check telephony connection status
     */
    public function status()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['connected' => false]);
        }

        $provider = $this->getActiveProvider($business);

        if (! $provider['account']) {
            return response()->json(['connected' => false]);
        }

        return response()->json([
            'connected' => true,
            'provider' => $provider['provider'],
            'caller_id' => $provider['provider'] === 'pbx'
                ? $provider['account']->caller_id
                : $provider['account']->caller_id,
            'balance' => $provider['account']->balance,
        ]);
    }

    /**
     * Make a call to a lead
     */
    public function callLead(Request $request, Lead $lead)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        if ((string) $lead->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        if (empty($lead->phone)) {
            return response()->json(['error' => 'Lead telefon raqami yo\'q'], 400);
        }

        $provider = $this->getActiveProvider($business);

        if (! $provider['service']) {
            return response()->json([
                'error' => 'Telefoniya sozlanmagan. Avval Sozlamalar > Telefoniya bo\'limidan ulang.',
            ], 400);
        }

        $result = $provider['service']->makeCall($lead->phone, $lead);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'call_id' => $result['call_id'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Make a call to a phone number
     */
    public function makeCall(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'lead_id' => 'nullable|uuid',
        ]);

        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $provider = $this->getActiveProvider($business);

        if (! $provider['service']) {
            return response()->json([
                'error' => 'Telefoniya sozlanmagan',
            ], 400);
        }

        $lead = null;
        if (! empty($validated['lead_id'])) {
            $lead = Lead::where('id', $validated['lead_id'])
                ->where('business_id', $business->id)
                ->first();
        }

        $result = $provider['service']->makeCall($validated['phone'], $lead);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'call_id' => $result['call_id'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Get call history for a lead
     */
    public function leadCallHistory(Lead $lead)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        if ((string) $lead->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        $calls = CallLog::where('lead_id', $lead->id)
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'direction' => $call->direction,
                    'direction_label' => $call->direction_label,
                    'status' => $call->status,
                    'status_label' => $call->status_label,
                    'duration' => $call->duration,
                    'formatted_duration' => $call->formatted_duration,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'provider' => $call->provider,
                    'recording_url' => $call->recording_url,
                    'user' => $call->user?->name,
                    'created_at' => $call->created_at->format('d.m.Y H:i'),
                ];
            });

        return response()->json($calls);
    }

    /**
     * Get all call history for business
     */
    public function history(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('business.index');
        }

        $perPage = $request->input('per_page', 25);
        $status = $request->input('status');
        $direction = $request->input('direction');
        $search = $request->input('search');

        $query = CallLog::where('business_id', $business->id)
            ->with(['lead:id,name,phone', 'user:id,name']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($direction && $direction !== 'all') {
            $query->where('direction', $direction);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('from_number', 'like', "%{$search}%")
                    ->orWhere('to_number', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $calls = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $calls->getCollection()->transform(function ($call) {
            return [
                'id' => $call->id,
                'direction' => $call->direction,
                'direction_label' => $call->direction_label,
                'status' => $call->status,
                'status_label' => $call->status_label,
                'duration' => $call->duration,
                'formatted_duration' => $call->formatted_duration,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'provider' => $call->provider,
                'recording_url' => $call->recording_url,
                'lead' => $call->lead ? [
                    'id' => $call->lead->id,
                    'name' => $call->lead->name,
                ] : null,
                'user' => $call->user?->name,
                'created_at' => $call->created_at->format('d.m.Y H:i'),
            ];
        });

        return Inertia::render('Business/Telephony/History', [
            'calls' => $calls,
            'filters' => [
                'status' => $status,
                'direction' => $direction,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Get call statistics
     */
    public function statistics()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('business.index');
        }

        $provider = $this->getActiveProvider($business);

        // Get statistics from provider service or use default empty stats
        $stats = null;
        if ($provider['service'] && $provider['account']) {
            $stats = $provider['service']->getStatistics($business->id);
        }

        // If no stats from provider, get from database directly
        if (! $stats) {
            $stats = $this->getDefaultStatistics($business->id);
        }

        return Inertia::render('Business/Telephony/Statistics', [
            'stats' => $stats,
            'provider' => $provider['provider'],
        ]);
    }

    /**
     * Get default statistics from database
     */
    protected function getDefaultStatistics(string $businessId): array
    {
        $stats = \App\Models\CallDailyStat::where('business_id', $businessId)
            ->where('stat_date', '>=', now()->subDays(30))
            ->get();

        return [
            'total_calls' => $stats->sum('total_calls'),
            'outbound_calls' => $stats->sum('outbound_calls'),
            'inbound_calls' => $stats->sum('inbound_calls'),
            'answered_calls' => $stats->sum('answered_calls'),
            'missed_calls' => $stats->sum('missed_calls'),
            'failed_calls' => $stats->sum('failed_calls'),
            'total_duration' => $stats->sum('total_duration'),
            'avg_duration' => $stats->avg('avg_duration') ?? 0,
            'answer_rate' => $stats->sum('total_calls') > 0
                ? round(($stats->sum('answered_calls') / $stats->sum('total_calls')) * 100, 1)
                : 0,
        ];
    }

    /**
     * Refresh balance
     */
    public function refreshBalance()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $provider = $this->getActiveProvider($business);

        if (! $provider['service']) {
            return response()->json(['error' => 'Provayder ulanmagan'], 400);
        }

        $balance = $provider['service']->getBalance();

        return response()->json([
            'success' => true,
            'balance' => $balance,
        ]);
    }

    /**
     * Handle PBX webhook
     */
    public function pbxWebhook(Request $request)
    {
        $data = $request->all();

        Log::info('PBX Webhook received', $data);

        $this->pbxService->handleWebhook($data);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle OnlinePBX webhook - creates leads from incoming calls
     */
    public function onlinePbxWebhook(Request $request)
    {
        $data = $request->all();

        Log::info('OnlinePBX Webhook received', $data);

        // Use OnlinePBX service for lead creation with duplicate detection
        $this->onlinePbxService->handleWebhook($data);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Connect OnlinePBX account
     */
    public function connectOnlinePbx(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string',
            'api_key' => 'required|string',
            'key_id' => 'nullable|string',
            'caller_id' => 'required|string|max:20',
            'extension' => 'nullable|string|max:10',
        ]);

        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Build API URL from domain
        $domain = rtrim($validated['domain'], '/');
        if (! str_contains($domain, 'http')) {
            $apiUrl = 'https://'.$domain.'.onpbx.ru';
        } else {
            $apiUrl = $domain;
        }

        // Test connection
        $result = $this->onlinePbxService->testConnection(
            $apiUrl,
            $validated['api_key'],
            $validated['key_id'] ?? null
        );

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        // Deactivate existing PBX accounts
        PbxAccount::where('business_id', $business->id)->update(['is_active' => false]);

        // Create new account with OnlinePBX settings
        PbxAccount::create([
            'business_id' => $business->id,
            'name' => 'OnlinePBX',
            'api_url' => $apiUrl,
            'api_key' => $validated['api_key'],
            'caller_id' => $validated['caller_id'],
            'extension' => $validated['extension'],
            'is_active' => true,
            'settings' => [
                'provider' => 'onlinepbx',
                'key_id' => $validated['key_id'] ?? null,
                'domain' => $validated['domain'],
            ],
            'last_sync_at' => now(),
        ]);

        return back()->with('success', 'OnlinePBX muvaffaqiyatli ulandi!');
    }

    /**
     * Sync OnlinePBX call history
     */
    public function syncOnlinePbxHistory(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $pbxAccount = PbxAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (! $pbxAccount) {
            return response()->json(['error' => 'PBX sozlanmagan'], 400);
        }

        $this->onlinePbxService->setAccount($pbxAccount);
        $result = $this->onlinePbxService->syncCallHistory();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => "Sinxronlashtirildi: {$result['synced']} ta qo'ng'iroq, {$result['created']} ta yangi",
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Handle SipUni webhook
     */
    public function sipuniWebhook(Request $request)
    {
        $data = $request->all();

        Log::info('SipUni Webhook received', $data);

        $this->sipuniService->handleWebhook($data);

        return response()->json(['status' => 'ok']);
    }
}
