<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\KpiDailyEntry;
use App\Models\KpiDailySourceDetail;
use App\Models\KpiWeeklySummary;
use App\Models\KpiMonthlySummary;
use App\Models\LeadSource;
use App\Models\SalesChannel;
use App\Models\PaymentMethod;
use App\Services\KPI\KpiEntryService;
use App\Services\KPI\KpiAggregationService;
use App\Services\KPI\KpiSourceAnalyzer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KpiEntryController extends Controller
{
    protected KpiEntryService $entryService;
    protected KpiAggregationService $aggregationService;
    protected KpiSourceAnalyzer $sourceAnalyzer;

    public function __construct(
        KpiEntryService $entryService,
        KpiAggregationService $aggregationService,
        KpiSourceAnalyzer $sourceAnalyzer
    ) {
        $this->entryService = $entryService;
        $this->aggregationService = $aggregationService;
        $this->sourceAnalyzer = $sourceAnalyzer;
    }

    /**
     * Справочnik ma'lumotlarini olish
     */
    public function getReferenceData(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $leadSources = LeadSource::active()
            ->forBusiness($business)
            ->ordered()
            ->get()
            ->groupBy('category');

        $salesChannels = SalesChannel::active()->ordered()->get();
        $paymentMethods = PaymentMethod::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'lead_sources' => $leadSources,
                'sales_channels' => $salesChannels,
                'payment_methods' => $paymentMethods,
            ],
        ]);
    }

    /**
     * Tezkor kiritish formasi uchun ma'lumot
     */
    public function getQuickEntryData(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        // Mavjud ma'lumotni olish
        $entry = KpiDailyEntry::where('business_id', $businessId)
            ->whereDate('date', $date)
            ->first();

        // Oxirgi 7 kun ma'lumotlari
        $recentEntries = KpiDailyEntry::where('business_id', $businessId)
            ->where('date', '>=', Carbon::parse($date)->subDays(7))
            ->where('date', '<', $date)
            ->orderBy('date', 'desc')
            ->get(['date', 'leads_total', 'spend_total', 'sales_total', 'revenue_total']);

        // O'rtacha qiymatlar
        $averages = [
            'leads' => $recentEntries->avg('leads_total') ?? 0,
            'spend' => $recentEntries->avg('spend_total') ?? 0,
            'sales' => $recentEntries->avg('sales_total') ?? 0,
            'revenue' => $recentEntries->avg('revenue_total') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'entry' => $entry,
                'recent_entries' => $recentEntries,
                'averages' => $averages,
                'has_data' => $entry !== null,
            ],
        ]);
    }

    /**
     * Tezkor kiritish (quick entry) - faqat bugungi kun uchun
     */
    public function storeQuickEntry(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            // Lidlar
            'leads_digital' => 'nullable|integer|min:0',
            'leads_offline' => 'nullable|integer|min:0',
            'leads_referral' => 'nullable|integer|min:0',
            'leads_organic' => 'nullable|integer|min:0',
            // Xarajatlar
            'spend_digital' => 'nullable|numeric|min:0',
            'spend_offline' => 'nullable|numeric|min:0',
            // Sotuvlar
            'sales_new' => 'nullable|integer|min:0',
            'sales_repeat' => 'nullable|integer|min:0',
            // Daromad
            'revenue_new' => 'nullable|numeric|min:0',
            'revenue_repeat' => 'nullable|numeric|min:0',
            // Izohlar
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Faqat bugungi kun uchun kiritish mumkin
        $requestDate = Carbon::parse($request->input('date'))->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');

        if ($requestDate !== $today) {
            return response()->json([
                'success' => false,
                'message' => 'Faqat bugungi kun uchun ma\'lumot kiritish mumkin',
            ], 422);
        }

        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        try {
            $entry = $this->entryService->saveQuickEntry($business, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Ma\'lumotlar saqlandi',
                'data' => [
                    'entry' => $entry,
                    'summary' => [
                        'leads_total' => $entry->leads_total,
                        'spend_total' => $entry->spend_total,
                        'sales_total' => $entry->sales_total,
                        'revenue_total' => $entry->revenue_total,
                        'conversion_rate' => $entry->conversion_rate,
                        'cpl' => $entry->cpl,
                        'avg_check' => $entry->avg_check,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xatolik yuz berdi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * To'liq kiritish formasi uchun ma'lumot
     */
    public function getFullEntryData(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        // Mavjud ma'lumotni olish
        $entry = KpiDailyEntry::where('business_id', $businessId)
            ->whereDate('date', $date)
            ->with('sourceDetails.leadSource')
            ->first();

        // Справочnik
        $leadSources = LeadSource::active()
            ->forBusiness($business)
            ->ordered()
            ->get()
            ->groupBy('category');

        $salesChannels = SalesChannel::active()->ordered()->get();
        $paymentMethods = PaymentMethod::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'entry' => $entry,
                'lead_sources' => $leadSources,
                'sales_channels' => $salesChannels,
                'payment_methods' => $paymentMethods,
                'has_data' => $entry !== null,
            ],
        ]);
    }

    /**
     * To'liq kiritish (full entry with source details)
     */
    public function storeFullEntry(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            // Asosiy ma'lumotlar
            'leads_digital' => 'nullable|integer|min:0',
            'leads_offline' => 'nullable|integer|min:0',
            'leads_referral' => 'nullable|integer|min:0',
            'leads_organic' => 'nullable|integer|min:0',
            'spend_digital' => 'nullable|numeric|min:0',
            'spend_offline' => 'nullable|numeric|min:0',
            'sales_new' => 'nullable|integer|min:0',
            'sales_repeat' => 'nullable|integer|min:0',
            'revenue_new' => 'nullable|numeric|min:0',
            'revenue_repeat' => 'nullable|numeric|min:0',
            // To'lov usullari
            'payment_cash' => 'nullable|numeric|min:0',
            'payment_card' => 'nullable|numeric|min:0',
            'payment_transfer' => 'nullable|numeric|min:0',
            'payment_credit' => 'nullable|numeric|min:0',
            // Manba detallari
            'source_details' => 'nullable|array',
            'source_details.*.lead_source_id' => 'required_with:source_details|integer|exists:lead_sources,id',
            'source_details.*.leads_count' => 'nullable|integer|min:0',
            'source_details.*.spend_amount' => 'nullable|numeric|min:0',
            'source_details.*.conversions' => 'nullable|integer|min:0',
            'source_details.*.revenue' => 'nullable|numeric|min:0',
            // Izohlar
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        try {
            $entry = $this->entryService->saveFullEntry($business, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Ma\'lumotlar saqlandi',
                'data' => [
                    'entry' => $entry->load('sourceDetails.leadSource'),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xatolik yuz berdi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kunlik ma'lumotlarni olish
     */
    public function getDailyEntries(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Sana chegarasi (90 kun)
        if ($request->has('start_date') && $request->has('end_date')) {
            $daysDiff = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));
            if ($daysDiff > 90) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sana oralig\'i 90 kundan oshmasligi kerak',
                ], 422);
            }
        }

        $query = KpiDailyEntry::where('business_id', $businessId)
            ->with('sourceDetails.leadSource');

        if ($request->has('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $query->orderBy('date', 'desc');

        $perPage = $request->input('per_page', 30);
        $entries = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $entries,
        ]);
    }

    /**
     * Bitta kunlik ma'lumotni olish
     */
    public function getDailyEntry(Request $request, string $businessId, string $date): JsonResponse
    {
        $entry = KpiDailyEntry::where('business_id', $businessId)
            ->whereDate('date', $date)
            ->with('sourceDetails.leadSource')
            ->first();

        if (!$entry) {
            return response()->json([
                'success' => false,
                'message' => 'Ma\'lumot topilmadi',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $entry,
        ]);
    }

    /**
     * Kunlik ma'lumotni o'chirish
     */
    public function deleteDailyEntry(Request $request, string $businessId, string $date): JsonResponse
    {
        $entry = KpiDailyEntry::where('business_id', $businessId)
            ->whereDate('date', $date)
            ->first();

        if (!$entry) {
            return response()->json([
                'success' => false,
                'message' => 'Ma\'lumot topilmadi',
            ], 404);
        }

        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ma\'lumot o\'chirildi',
        ]);
    }

    /**
     * Haftalik ma'lumotlarni olish
     */
    public function getWeeklyData(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $year = $request->input('year', Carbon::now()->year);
        $week = $request->input('week', Carbon::now()->weekOfYear);

        $data = $this->entryService->getWeekEntries($business, $year, $week);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Oylik ma'lumotlarni olish
     */
    public function getMonthlyData(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $data = $this->entryService->getCurrentMonthEntries($business);

        // Oylik yig'indi
        $summary = KpiMonthlySummary::where('business_id', $businessId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'entries' => $data['entries'],
                'summary' => $summary,
                'period' => [
                    'year' => $year,
                    'month' => $month,
                ],
            ],
        ]);
    }

    /**
     * Dashboard uchun agregatsiya
     */
    public function getDashboard(Request $request, string $businessId): JsonResponse
    {
        \Log::info('KPI Dashboard request', [
            'businessId' => $businessId,
            'user' => auth()->id(),
        ]);

        $business = Business::find($businessId);
        if (!$business) {
            \Log::error('Business not found', ['businessId' => $businessId]);
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $data = $this->aggregationService->getDashboardData($business);

        \Log::info('Dashboard data retrieved', [
            'leads' => $data['totals']['leads'] ?? 0,
            'sales' => $data['totals']['sales'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Trend ma'lumotlari
     */
    public function getTrends(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $days = $request->input('days', 7);
        if ($days < 1 || $days > 90) {
            return response()->json([
                'success' => false,
                'message' => 'Kunlar soni 1 dan 90 gacha bo\'lishi kerak',
            ], 422);
        }

        $data = $this->aggregationService->getTrendData($business, $days);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Manba tahlili
     */
    public function getSourceAnalysis(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $analysis = $this->sourceAnalyzer->analyzeSourcesForPeriod($business, $startDate, $endDate);
        $recommendations = $this->sourceAnalyzer->generateRecommendations($analysis);

        return response()->json([
            'success' => true,
            'data' => [
                'analysis' => $analysis,
                'recommendations' => $recommendations,
            ],
        ]);
    }

    /**
     * Kategoriya bo'yicha tahlil
     */
    public function getCategoryAnalysis(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $summary = $this->sourceAnalyzer->getCategorySummary($business, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Haftalik agregatsiya qilish (manual trigger)
     */
    public function triggerWeeklyAggregation(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $year = $request->input('year', Carbon::now()->year);
        $week = $request->input('week', Carbon::now()->weekOfYear);

        try {
            $summary = $this->aggregationService->aggregateWeekly($business, $year, $week);

            return response()->json([
                'success' => true,
                'message' => 'Haftalik agregatsiya bajarildi',
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xatolik yuz berdi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Oylik agregatsiya qilish (manual trigger)
     */
    public function triggerMonthlyAggregation(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        try {
            $summary = $this->aggregationService->aggregateMonthly($business, $year, $month);

            return response()->json([
                'success' => true,
                'message' => 'Oylik agregatsiya bajarildi',
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xatolik yuz berdi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kunlik ma'lumotni tasdiqlash
     */
    public function verifyEntry(Request $request, string $businessId, int $id): JsonResponse
    {
        $entry = KpiDailyEntry::where('business_id', $businessId)
            ->where('id', $id)
            ->first();

        if (!$entry) {
            return response()->json([
                'success' => false,
                'message' => 'Ma\'lumot topilmadi',
            ], 404);
        }

        $entry->verified_by = $request->user()?->id;
        $entry->verified_at = Carbon::now();
        $entry->save();

        return response()->json([
            'success' => true,
            'message' => 'Ma\'lumot tasdiqlandi',
            'data' => $entry,
        ]);
    }
}
