<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\MarketingAlert;
use App\Services\MarketingAlertService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlertController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        private MarketingAlertService $alertService
    ) {}

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $status = $request->get('status', 'active');
        $severity = $request->get('severity');
        $type = $request->get('type');

        $query = MarketingAlert::where('business_id', $business->id)
            ->with(['channel', 'campaign', 'user', 'acknowledgedByUser', 'resolvedByUser'])
            ->orderBy('created_at', 'desc');

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'unresolved') {
            $query->unresolved();
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $alerts = $query->paginate(20);

        $summary = $this->alertService->getAlertsSummary($business);

        return Inertia::render('Marketing/Alerts/Index', [
            'alerts' => $alerts,
            'summary' => $summary,
            'alertTypes' => MarketingAlert::TYPES,
            'filters' => [
                'status' => $status,
                'severity' => $severity,
                'type' => $type,
            ],
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function show(Request $request, MarketingAlert $alert)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $alert->business_id !== $business->id) {
            abort(404);
        }

        $alert->load(['channel', 'campaign', 'user', 'acknowledgedByUser', 'resolvedByUser']);

        return Inertia::render('Marketing/Alerts/Show', [
            'alert' => $alert,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function active(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $severity = $request->get('severity');

        $alerts = $this->alertService->getActiveAlerts($business, $severity);

        return response()->json([
            'alerts' => $alerts->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'type_label' => $a->getTypeLabel(),
                'severity' => $a->severity,
                'severity_color' => $a->getSeverityColor(),
                'title' => $a->title,
                'message' => $a->message,
                'channel_name' => $a->channel?->name,
                'created_at' => $a->created_at->diffForHumans(),
            ]),
            'count' => $alerts->count(),
        ]);
    }

    public function unresolved(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $alerts = $this->alertService->getUnresolvedAlerts($business);

        return response()->json([
            'alerts' => $alerts,
            'summary' => $this->alertService->getAlertsSummary($business),
        ]);
    }

    public function acknowledge(Request $request, MarketingAlert $alert)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $alert->business_id !== $business->id) {
            abort(404);
        }

        $this->alertService->acknowledgeAlert($alert, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Alert qabul qilindi',
        ]);
    }

    public function resolve(Request $request, MarketingAlert $alert)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $alert->business_id !== $business->id) {
            abort(404);
        }

        $notes = $request->input('notes');
        $this->alertService->resolveAlert($alert, auth()->id(), $notes);

        return response()->json([
            'success' => true,
            'message' => 'Alert hal qilindi',
        ]);
    }

    public function dismiss(Request $request, MarketingAlert $alert)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $alert->business_id !== $business->id) {
            abort(404);
        }

        $this->alertService->dismissAlert($alert);

        return response()->json([
            'success' => true,
            'message' => 'Alert o\'chirildi',
        ]);
    }

    public function checkNow(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $alerts = $this->alertService->checkAndCreateAlerts($business);

        return response()->json([
            'success' => true,
            'new_alerts' => $alerts->count(),
            'critical' => $alerts->where('severity', 'critical')->count(),
            'message' => $alerts->isEmpty()
                ? 'Hech qanday yangi alert topilmadi'
                : $alerts->count() . ' ta yangi alert topildi',
        ]);
    }

    public function bulkAcknowledge(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'exists:marketing_alerts,id',
        ]);

        $count = 0;
        foreach ($validated['alert_ids'] as $alertId) {
            $alert = MarketingAlert::find($alertId);
            if ($alert && $alert->business_id === $business->id && $alert->isActive()) {
                $this->alertService->acknowledgeAlert($alert, auth()->id());
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'acknowledged_count' => $count,
            'message' => $count . ' ta alert qabul qilindi',
        ]);
    }

    public function bulkResolve(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'exists:marketing_alerts,id',
            'notes' => 'nullable|string',
        ]);

        $count = 0;
        foreach ($validated['alert_ids'] as $alertId) {
            $alert = MarketingAlert::find($alertId);
            if ($alert && $alert->business_id === $business->id) {
                $this->alertService->resolveAlert($alert, auth()->id(), $validated['notes'] ?? null);
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'resolved_count' => $count,
            'message' => $count . ' ta alert hal qilindi',
        ]);
    }
}
