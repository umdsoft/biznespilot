<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Business;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function __construct(
        protected AlertService $alertService
    ) {}

    public function index(Request $request): Response
    {
        $business = $this->getCurrentBusiness();

        $query = Alert::where('business_id', $business->id)
            ->with('alertRule')
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low', 'info')")
            ->orderBy('triggered_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by severity
        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        $alerts = $query->paginate(20);
        $stats = $this->alertService->getAlertStats($business);

        return Inertia::render('Dashboard/Alerts/Index', [
            'alerts' => $alerts,
            'stats' => $stats,
            'filters' => [
                'status' => $request->status ?? 'all',
                'severity' => $request->severity ?? 'all',
            ],
        ]);
    }

    public function show(string $id): Response
    {
        $business = $this->getCurrentBusiness();
        $alert = Alert::where('business_id', $business->id)
            ->with('alertRule')
            ->findOrFail($id);

        return Inertia::render('Dashboard/Alerts/Show', [
            'alert' => $alert,
        ]);
    }

    public function acknowledge(Request $request, string $id)
    {
        $business = $this->getCurrentBusiness();
        $alert = Alert::where('business_id', $business->id)->findOrFail($id);

        $this->alertService->acknowledgeAlert($alert, Auth::id());

        return response()->json(['success' => true, 'alert' => $alert->fresh()]);
    }

    public function resolve(Request $request, string $id)
    {
        $request->validate([
            'resolution' => 'nullable|string|max:1000',
        ]);

        $business = $this->getCurrentBusiness();
        $alert = Alert::where('business_id', $business->id)->findOrFail($id);

        $this->alertService->resolveAlert($alert, Auth::id(), $request->resolution);

        return response()->json(['success' => true, 'alert' => $alert->fresh()]);
    }

    public function snooze(Request $request, string $id)
    {
        $request->validate([
            'hours' => 'nullable|integer|min:1|max:168', // Max 1 week
        ]);

        $business = $this->getCurrentBusiness();
        $alert = Alert::where('business_id', $business->id)->findOrFail($id);

        $this->alertService->snoozeAlert($alert, $request->hours ?? 24);

        return response()->json(['success' => true, 'alert' => $alert->fresh()]);
    }

    public function dismiss(string $id)
    {
        $business = $this->getCurrentBusiness();
        $alert = Alert::where('business_id', $business->id)->findOrFail($id);

        $this->alertService->dismissAlert($alert);

        return response()->json(['success' => true]);
    }

    public function getActive()
    {
        $business = $this->getCurrentBusiness();
        $alerts = $this->alertService->getActiveAlerts($business);

        return response()->json(['alerts' => $alerts]);
    }

    // Alert Rules Management
    public function rules(): Response
    {
        $rules = AlertRule::orderBy('severity')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboard/Alerts/Rules', [
            'rules' => $rules,
        ]);
    }

    public function updateRule(Request $request, string $id)
    {
        $request->validate([
            'is_active' => 'boolean',
            'threshold_value' => 'nullable|numeric',
            'notification_channels' => 'nullable|array',
        ]);

        $rule = AlertRule::findOrFail($id);
        $rule->update($request->only(['is_active', 'threshold_value', 'notification_channels']));

        return response()->json(['success' => true, 'rule' => $rule->fresh()]);
    }

    public function createRule(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:threshold,metric_change,pattern',
            'metric' => 'required|string|max:100',
            'condition' => 'required|string|in:greater_than,less_than,equals,change_percent',
            'threshold_value' => 'required|numeric',
            'severity' => 'required|string|in:critical,high,medium,low,info',
            'message_template' => 'required|string|max:500',
        ]);

        $rule = AlertRule::create($request->all());

        return response()->json(['success' => true, 'rule' => $rule]);
    }

    public function deleteRule(string $id)
    {
        $rule = AlertRule::findOrFail($id);
        $rule->delete();

        return response()->json(['success' => true]);
    }

    protected function getCurrentBusiness(): Business
    {
        return Auth::user()->currentBusiness ?? Auth::user()->businesses()->firstOrFail();
    }
}
