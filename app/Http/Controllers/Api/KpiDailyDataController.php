<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncDailyKpisFromIntegrationsJob;
use App\Models\Business;
use App\Models\KpiDailyActual;
use App\Models\KpiTemplate;
use App\Services\Integration\FacebookKpiSyncService;
use App\Services\Integration\InstagramKpiSyncService;
use App\Services\Integration\PosKpiSyncService;
use App\Services\Integration\SyncMonitor;
use App\Services\KpiAggregationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class KpiDailyDataController extends Controller
{
    protected $aggregationService;

    public function __construct(KpiAggregationService $aggregationService)
    {
        $this->aggregationService = $aggregationService;
    }

    /**
     * Get daily actuals for a business
     */
    public function index(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kpi_code' => 'nullable|string|exists:kpi_templates,kpi_code',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:green,yellow,red,grey',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // SECURITY: Prevent mass data extraction with date range limit (90 days max)
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'));
            $endDate = \Carbon\Carbon::parse($request->input('end_date'));
            $daysDiff = $startDate->diffInDays($endDate);

            if ($daysDiff > 90) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date range cannot exceed 90 days. Please reduce the range.',
                    'max_days' => 90,
                    'requested_days' => $daysDiff,
                ], 422);
            }
        }

        $query = KpiDailyActual::where('business_id', $businessId)
            ->with('kpiTemplate');

        if ($request->has('kpi_code')) {
            $query->where('kpi_code', $request->input('kpi_code'));
        }

        if ($request->has('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $query->orderBy('date', 'desc');

        $perPage = $request->input('per_page', 30);
        $dailyActuals = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $dailyActuals,
        ]);
    }

    /**
     * Get daily actual by ID
     */
    public function show(int $businessId, int $id): JsonResponse
    {
        $dailyActual = KpiDailyActual::where('business_id', $businessId)
            ->where('id', $id)
            ->with('kpiTemplate')
            ->first();

        if (!$dailyActual) {
            return response()->json([
                'success' => false,
                'message' => 'Daily actual not found',
            ], 404);
        }

        $trend = $dailyActual->getTrendVsPreviousDay();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_actual' => $dailyActual,
                'trend_vs_previous_day' => $trend,
                'summary' => $dailyActual->toSummaryArray(),
            ],
        ]);
    }

    /**
     * Create or update daily actual
     */
    public function store(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kpi_code' => 'required|string|exists:kpi_templates,kpi_code',
            'date' => 'required|date',
            'actual_value' => 'required|numeric',
            'planned_value' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
            'is_verified' => 'nullable|boolean',
            'is_estimated' => 'nullable|boolean',
            'estimation_method' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $template = KpiTemplate::where('kpi_code', $request->input('kpi_code'))->first();
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'KPI template not found',
            ], 404);
        }

        // Check if record already exists
        $dailyActual = KpiDailyActual::where('business_id', $businessId)
            ->where('kpi_code', $request->input('kpi_code'))
            ->whereDate('date', $request->input('date'))
            ->first();

        $isUpdate = $dailyActual !== null;

        if (!$dailyActual) {
            $dailyActual = new KpiDailyActual();
            $dailyActual->business_id = $businessId;
            $dailyActual->kpi_code = $request->input('kpi_code');
            $dailyActual->date = $request->input('date');
        }

        $dailyActual->actual_value = $request->input('actual_value');
        $dailyActual->planned_value = $request->input('planned_value', 0);
        $dailyActual->unit = $template->default_unit;
        $dailyActual->notes = $request->input('notes');
        $dailyActual->metadata = $request->input('metadata');
        $dailyActual->is_estimated = $request->input('is_estimated', false);
        $dailyActual->estimation_method = $request->input('estimation_method');
        $dailyActual->data_source = $request->input('data_source', 'manual_entry');
        $dailyActual->recorded_time = now();

        if ($request->has('is_verified') && $request->input('is_verified')) {
            $dailyActual->verify($request->user()?->id);
        }

        $dailyActual->save();

        // Trigger aggregation for the week and month
        $date = Carbon::parse($request->input('date'));
        $weekStart = $date->copy()->startOfWeek();

        // Aggregate weekly (async)
        \App\Jobs\AggregateWeeklyKpisJob::dispatch($businessId, $weekStart->format('Y-m-d'))->onQueue('kpi-aggregation');

        // If it's end of month or manual trigger, aggregate monthly
        if ($date->isLastOfMonth() || $request->input('trigger_monthly_aggregation', false)) {
            \App\Jobs\AggregateMonthlyKpisJob::dispatch($businessId, $date->year, $date->month)->onQueue('kpi-aggregation');
        }

        return response()->json([
            'success' => true,
            'message' => $isUpdate ? 'Daily actual updated successfully' : 'Daily actual created successfully',
            'data' => [
                'daily_actual' => $dailyActual->fresh(),
                'summary' => $dailyActual->toSummaryArray(),
            ],
        ], $isUpdate ? 200 : 201);
    }

    /**
     * Bulk create/update daily actuals
     */
    public function bulkStore(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actuals' => 'required|array|min:1',
            'actuals.*.kpi_code' => 'required|string|exists:kpi_templates,kpi_code',
            'actuals.*.date' => 'required|date',
            'actuals.*.actual_value' => 'required|numeric',
            'actuals.*.planned_value' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($request->input('actuals') as $actualData) {
            try {
                $template = KpiTemplate::where('kpi_code', $actualData['kpi_code'])->first();
                if (!$template) {
                    $results[] = [
                        'kpi_code' => $actualData['kpi_code'],
                        'date' => $actualData['date'],
                        'success' => false,
                        'error' => 'KPI template not found',
                    ];
                    $errorCount++;
                    continue;
                }

                $dailyActual = KpiDailyActual::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'kpi_code' => $actualData['kpi_code'],
                        'date' => $actualData['date'],
                    ],
                    [
                        'actual_value' => $actualData['actual_value'],
                        'planned_value' => $actualData['planned_value'] ?? 0,
                        'unit' => $template->default_unit,
                        'data_source' => 'bulk_import',
                        'recorded_time' => now(),
                    ]
                );

                $results[] = [
                    'kpi_code' => $actualData['kpi_code'],
                    'date' => $actualData['date'],
                    'success' => true,
                    'id' => $dailyActual->id,
                ];
                $successCount++;
            } catch (\Exception $e) {
                $results[] = [
                    'kpi_code' => $actualData['kpi_code'],
                    'date' => $actualData['date'],
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                $errorCount++;
            }
        }

        // Trigger aggregation
        if ($successCount > 0) {
            $dates = collect($request->input('actuals'))->pluck('date')->unique();
            foreach ($dates as $date) {
                $dateObj = Carbon::parse($date);
                $weekStart = $dateObj->copy()->startOfWeek();
                \App\Jobs\AggregateWeeklyKpisJob::dispatch($businessId, $weekStart->format('Y-m-d'))->onQueue('kpi-aggregation');
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk import completed: $successCount successful, $errorCount failed",
            'data' => [
                'total' => count($request->input('actuals')),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'results' => $results,
            ],
        ]);
    }

    /**
     * Update daily actual
     */
    public function update(Request $request, int $businessId, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actual_value' => 'nullable|numeric',
            'planned_value' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dailyActual = KpiDailyActual::where('business_id', $businessId)
            ->where('id', $id)
            ->first();

        if (!$dailyActual) {
            return response()->json([
                'success' => false,
                'message' => 'Daily actual not found',
            ], 404);
        }

        $dailyActual->update($request->only([
            'actual_value',
            'planned_value',
            'notes',
            'metadata',
        ]));

        // Trigger re-aggregation
        $date = Carbon::parse($dailyActual->date);
        $weekStart = $date->copy()->startOfWeek();
        \App\Jobs\AggregateWeeklyKpisJob::dispatch($businessId, $weekStart->format('Y-m-d'))->onQueue('kpi-aggregation');

        return response()->json([
            'success' => true,
            'message' => 'Daily actual updated successfully',
            'data' => [
                'daily_actual' => $dailyActual->fresh(),
            ],
        ]);
    }

    /**
     * Delete daily actual
     */
    public function destroy(int $businessId, int $id): JsonResponse
    {
        $dailyActual = KpiDailyActual::where('business_id', $businessId)
            ->where('id', $id)
            ->first();

        if (!$dailyActual) {
            return response()->json([
                'success' => false,
                'message' => 'Daily actual not found',
            ], 404);
        }

        $date = Carbon::parse($dailyActual->date);
        $kpiCode = $dailyActual->kpi_code;

        $dailyActual->delete();

        // Trigger re-aggregation
        $weekStart = $date->copy()->startOfWeek();
        \App\Jobs\AggregateWeeklyKpisJob::dispatch($businessId, $weekStart->format('Y-m-d'))->onQueue('kpi-aggregation');

        return response()->json([
            'success' => true,
            'message' => 'Daily actual deleted successfully',
        ]);
    }

    /**
     * Verify daily actual
     */
    public function verify(Request $request, int $businessId, int $id): JsonResponse
    {
        $dailyActual = KpiDailyActual::where('business_id', $businessId)
            ->where('id', $id)
            ->first();

        if (!$dailyActual) {
            return response()->json([
                'success' => false,
                'message' => 'Daily actual not found',
            ], 404);
        }

        $dailyActual->verify($request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Daily actual verified successfully',
            'data' => [
                'daily_actual' => $dailyActual,
            ],
        ]);
    }

    /**
     * Mark as anomaly
     */
    public function markAnomaly(Request $request, int $businessId, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'anomaly_type' => 'required|string|in:spike,drop,outlier,data_error',
            'anomaly_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dailyActual = KpiDailyActual::where('business_id', $businessId)
            ->where('id', $id)
            ->first();

        if (!$dailyActual) {
            return response()->json([
                'success' => false,
                'message' => 'Daily actual not found',
            ], 404);
        }

        $dailyActual->markAsAnomaly(
            $request->input('anomaly_type'),
            $request->input('anomaly_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Daily actual marked as anomaly',
            'data' => [
                'daily_actual' => $dailyActual,
            ],
        ]);
    }

    /**
     * Get daily actuals for a specific week
     */
    public function getWeekData(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'week_start_date' => 'required|date',
            'kpi_codes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $weekStart = Carbon::parse($request->input('week_start_date'))->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $query = KpiDailyActual::where('business_id', $businessId)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->with('kpiTemplate');

        if ($request->has('kpi_codes')) {
            $query->whereIn('kpi_code', $request->input('kpi_codes'));
        }

        $dailyActuals = $query->orderBy('date')->get();

        $groupedByKpi = $dailyActuals->groupBy('kpi_code')->map(function ($actuals, $kpiCode) {
            return [
                'kpi_code' => $kpiCode,
                'kpi_name' => $actuals->first()->kpiTemplate->kpi_name ?? $kpiCode,
                'daily_data' => $actuals->map->toSummaryArray(),
                'week_summary' => [
                    'total_days' => $actuals->count(),
                    'average_value' => $actuals->avg('actual_value'),
                    'total_value' => $actuals->sum('actual_value'),
                    'green_days' => $actuals->where('status', 'green')->count(),
                    'yellow_days' => $actuals->where('status', 'yellow')->count(),
                    'red_days' => $actuals->where('status', 'red')->count(),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'kpis' => $groupedByKpi->values(),
            ],
        ]);
    }

    /**
     * Get anomalies
     */
    public function getAnomalies(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'kpi_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = KpiDailyActual::where('business_id', $businessId)
            ->where('is_anomaly', true)
            ->with('kpiTemplate');

        if ($request->has('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        if ($request->has('kpi_code')) {
            $query->where('kpi_code', $request->input('kpi_code'));
        }

        $anomalies = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'anomalies' => $anomalies,
                'total_count' => $anomalies->count(),
                'by_type' => $anomalies->groupBy('anomaly_type')->map->count(),
            ],
        ]);
    }

    /**
     * Trigger manual sync from integrations
     */
    public function syncFromIntegrations(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'integration' => 'nullable|string|in:instagram,facebook,pos,all',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $date = $request->input('date', Carbon::yesterday()->format('Y-m-d'));
        $integration = $request->input('integration', 'all');

        // Dispatch sync job
        SyncDailyKpisFromIntegrationsJob::dispatch($businessId, $date)->onQueue('kpi-sync');

        return response()->json([
            'success' => true,
            'message' => 'Integration sync job dispatched successfully',
            'data' => [
                'business_id' => $businessId,
                'date' => $date,
                'integration' => $integration,
                'status' => 'pending',
            ],
        ], 202);
    }

    /**
     * Get integration sync status
     */
    public function getIntegrationSyncStatus(
        Request $request,
        int $businessId,
        InstagramKpiSyncService $instagramSync,
        FacebookKpiSyncService $facebookSync,
        PosKpiSyncService $posSync
    ): JsonResponse {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $date = $request->input('date', Carbon::yesterday()->format('Y-m-d'));

        // Get sync status from each integration
        $integrations = [
            'instagram' => $instagramSync->getSyncStatus($businessId),
            'facebook' => $facebookSync->getSyncStatus($businessId),
            'pos' => $posSync->getSyncStatus($businessId),
        ];

        // Get cached sync results if available
        $cachedResults = cache()->get("kpi_sync_results:business_{$businessId}:{$date}");

        // Get sync statistics for the date
        $syncedKpis = KpiDailyActual::where('business_id', $businessId)
            ->whereDate('record_date', $date)
            ->where('auto_calculated', true)
            ->where('sync_status', 'synced')
            ->count();

        $failedKpis = KpiDailyActual::where('business_id', $businessId)
            ->whereDate('record_date', $date)
            ->where('auto_calculated', true)
            ->where('sync_status', 'failed')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'business_id' => $businessId,
                'date' => $date,
                'integrations' => $integrations,
                'sync_statistics' => [
                    'synced_kpis' => $syncedKpis,
                    'failed_kpis' => $failedKpis,
                    'last_sync_results' => $cachedResults,
                ],
                'available_integrations_count' => collect($integrations)->where('available', true)->count(),
            ],
        ]);
    }

    /**
     * Manually override auto-calculated KPI value
     */
    public function manualOverride(Request $request, int $businessId, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actual_value' => 'required|numeric',
            'override_reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dailyActual = KpiDailyActual::where('business_id', $businessId)
            ->where('id', $id)
            ->first();

        if (!$dailyActual) {
            return response()->json([
                'success' => false,
                'message' => 'Daily actual not found',
            ], 404);
        }

        // Check if override is allowed
        if (!$dailyActual->can_override) {
            return response()->json([
                'success' => false,
                'message' => 'Manual override is not allowed for this KPI value',
            ], 403);
        }

        // Store original synced value before override
        if ($dailyActual->auto_calculated && !$dailyActual->overridden_at) {
            $dailyActual->original_synced_value = $dailyActual->actual_value;
        }

        // Update with manual override
        $dailyActual->actual_value = $request->input('actual_value');
        $dailyActual->data_source = 'manual_override';
        $dailyActual->sync_status = 'manual';
        $dailyActual->overridden_by = $request->user()?->id ?? 'system';
        $dailyActual->overridden_at = now();
        $dailyActual->notes = ($dailyActual->notes ?? '') . "\n[Override] " . $request->input('override_reason');
        $dailyActual->save();

        // Trigger re-aggregation
        $date = Carbon::parse($dailyActual->record_date);
        $weekStart = $date->copy()->startOfWeek();
        \App\Jobs\AggregateWeeklyKpisJob::dispatch($businessId, $weekStart->format('Y-m-d'))->onQueue('kpi-aggregation');

        return response()->json([
            'success' => true,
            'message' => 'KPI value manually overridden successfully',
            'data' => [
                'daily_actual' => $dailyActual->fresh(),
                'original_synced_value' => $dailyActual->original_synced_value,
                'override_reason' => $request->input('override_reason'),
            ],
        ]);
    }

    /**
     * Restore auto-calculated value after manual override
     */
    public function restoreAutoCalculated(Request $request, int $businessId, int $id): JsonResponse
    {
        $dailyActual = KpiDailyActual::where('business_id', $businessId)
            ->where('id', $id)
            ->first();

        if (!$dailyActual) {
            return response()->json([
                'success' => false,
                'message' => 'Daily actual not found',
            ], 404);
        }

        if (!$dailyActual->original_synced_value) {
            return response()->json([
                'success' => false,
                'message' => 'No original synced value available to restore',
            ], 400);
        }

        // Restore original value
        $dailyActual->actual_value = $dailyActual->original_synced_value;
        $dailyActual->data_source = $dailyActual->sync_metadata['synced_by'] ?? 'integration';
        $dailyActual->sync_status = 'synced';
        $dailyActual->overridden_by = null;
        $dailyActual->overridden_at = null;
        $dailyActual->original_synced_value = null;
        $dailyActual->notes = ($dailyActual->notes ?? '') . "\n[Restored] Auto-calculated value restored";
        $dailyActual->save();

        // Trigger re-aggregation
        $date = Carbon::parse($dailyActual->record_date);
        $weekStart = $date->copy()->startOfWeek();
        \App\Jobs\AggregateWeeklyKpisJob::dispatch($businessId, $weekStart->format('Y-m-d'))->onQueue('kpi-aggregation');

        return response()->json([
            'success' => true,
            'message' => 'Auto-calculated value restored successfully',
            'data' => [
                'daily_actual' => $dailyActual->fresh(),
            ],
        ]);
    }

    /**
     * Get list of KPI values that were manually overridden
     */
    public function getManualOverrides(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'kpi_code' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // SECURITY: Prevent mass data extraction with date range limit (90 days max)
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'));
            $endDate = \Carbon\Carbon::parse($request->input('end_date'));
            $daysDiff = $startDate->diffInDays($endDate);

            if ($daysDiff > 90) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date range cannot exceed 90 days. Please reduce the range.',
                    'max_days' => 90,
                    'requested_days' => $daysDiff,
                ], 422);
            }
        }

        $query = KpiDailyActual::where('business_id', $businessId)
            ->whereNotNull('overridden_at')
            ->with('kpiTemplate');

        if ($request->has('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        if ($request->has('kpi_code')) {
            $query->where('kpi_code', $request->input('kpi_code'));
        }

        // SECURITY: Add pagination to prevent mass data extraction
        $perPage = $request->input('per_page', 30);
        $overrides = $query->orderBy('overridden_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $overrides,
        ]);
    }

    /**
     * Get sync health status and monitoring dashboard
     */
    public function getSyncHealth(Request $request, SyncMonitor $monitor): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $healthStatus = $monitor->getHealthStatus($date);

        return response()->json([
            'success' => true,
            'data' => $healthStatus,
        ]);
    }

    /**
     * Get comprehensive monitoring dashboard
     */
    public function getSyncDashboard(Request $request, SyncMonitor $monitor): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $dashboard = $monitor->getDashboard($date);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }

    /**
     * Get batch statistics for a specific date
     */
    public function getBatchStats(Request $request, SyncMonitor $monitor): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $batchStats = $monitor->getBatchStats($date);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'batches' => $batchStats,
                'total_batches' => count($batchStats),
            ],
        ]);
    }

    /**
     * Get failed businesses for a specific date
     */
    public function getFailedBusinesses(Request $request, SyncMonitor $monitor): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $failedBusinesses = $monitor->getFailedBusinesses($date);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'failed_businesses' => $failedBusinesses,
                'total_failed' => count($failedBusinesses),
            ],
        ]);
    }

    /**
     * Get performance trends over time
     */
    public function getPerformanceTrends(Request $request, SyncMonitor $monitor): JsonResponse
    {
        $days = $request->input('days', 7);

        if ($days < 1 || $days > 90) {
            return response()->json([
                'success' => false,
                'message' => 'Days parameter must be between 1 and 90',
            ], 422);
        }

        $trends = $monitor->getPerformanceTrends($days);

        return response()->json([
            'success' => true,
            'data' => [
                'trends' => $trends,
                'period_days' => $days,
            ],
        ]);
    }

    /**
     * Get integration-specific statistics
     */
    public function getIntegrationStatistics(Request $request, SyncMonitor $monitor): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $stats = $monitor->getIntegrationStats($date);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'integrations' => $stats,
            ],
        ]);
    }

    /**
     * Check if sync is currently running
     */
    public function isSyncRunning(SyncMonitor $monitor): JsonResponse
    {
        $runningStatus = $monitor->isRunning();

        return response()->json([
            'success' => true,
            'data' => $runningStatus,
        ]);
    }
}
