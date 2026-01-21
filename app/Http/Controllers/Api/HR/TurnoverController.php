<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\TurnoverRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Turnover API Controller
 *
 * Hodimlar ketishi (turnover) tahlili uchun API.
 * Exit interview va turnover metrikalarini qo'llab-quvvatlaydi.
 */
class TurnoverController extends Controller
{
    /**
     * Barcha turnover yozuvlari
     */
    public function index(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $year = $request->input('year', now()->year);
        $month = $request->input('month');
        $type = $request->input('type'); // voluntary, involuntary

        $query = TurnoverRecord::where('business_id', $businessId)
            ->with('user:id,name,email')
            ->whereYear('termination_date', $year)
            ->orderBy('termination_date', 'desc');

        if ($month) {
            $query->whereMonth('termination_date', $month);
        }

        if ($type) {
            $query->where('termination_type', $type);
        }

        $records = $query->paginate($perPage);

        $records->getCollection()->transform(function ($record) {
            return [
                'id' => $record->id,
                'user' => $record->user ? [
                    'id' => $record->user->id,
                    'name' => $record->user->name,
                    'email' => $record->user->email,
                ] : null,
                'termination_date' => $record->termination_date?->format('d.m.Y'),
                'termination_type' => $record->termination_type,
                'termination_type_label' => $this->getTerminationTypeLabel($record->termination_type),
                'termination_reason' => $record->termination_reason,
                'termination_reason_label' => $this->getReasonLabel($record->termination_reason),
                'department' => $record->department,
                'position' => $record->position,
                'tenure_months' => $record->tenure_months,
                'tenure_label' => $this->formatTenure($record->tenure_months),
                'is_regrettable' => $record->is_regrettable,
                'exit_interview_completed' => $record->exit_interview_completed,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    /**
     * Bitta turnover yozuvi tafsilotlari
     */
    public function show(Request $request, string $businessId, string $recordId): JsonResponse
    {
        $record = TurnoverRecord::where('business_id', $businessId)
            ->where('id', $recordId)
            ->with(['user', 'recordedBy', 'replacement'])
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Yozuv topilmadi',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'user' => $record->user ? [
                    'id' => $record->user->id,
                    'name' => $record->user->name,
                    'email' => $record->user->email,
                ] : null,
                'termination_date' => $record->termination_date?->format('Y-m-d'),
                'last_working_day' => $record->last_working_day?->format('Y-m-d'),
                'termination_type' => $record->termination_type,
                'termination_type_label' => $this->getTerminationTypeLabel($record->termination_type),
                'termination_reason' => $record->termination_reason,
                'termination_reason_label' => $this->getReasonLabel($record->termination_reason),
                'termination_reason_details' => $record->termination_reason_details,
                'department' => $record->department,
                'position' => $record->position,
                'tenure_months' => $record->tenure_months,
                'tenure_label' => $this->formatTenure($record->tenure_months),
                'is_regrettable' => $record->is_regrettable,
                'exit_interview' => [
                    'completed' => $record->exit_interview_completed,
                    'date' => $record->exit_interview_date?->format('d.m.Y'),
                    'notes' => $record->exit_interview_notes,
                    'feedback' => $record->exit_interview_feedback,
                ],
                'rehire_eligibility' => $record->rehire_eligibility,
                'replacement' => $record->replacement ? [
                    'id' => $record->replacement->id,
                    'name' => $record->replacement->name,
                ] : null,
                'replacement_status' => $record->replacement_status,
                'replacement_status_label' => $this->getReplacementStatusLabel($record->replacement_status),
                'notes' => $record->notes,
                'recorded_by' => $record->recordedBy ? [
                    'id' => $record->recordedBy->id,
                    'name' => $record->recordedBy->name,
                ] : null,
                'created_at' => $record->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Yangi turnover yozuv yaratish
     */
    public function store(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'termination_date' => 'required|date',
            'last_working_day' => 'nullable|date',
            'termination_type' => 'required|in:voluntary,involuntary',
            'termination_reason' => 'required|string|max:100',
            'termination_reason_details' => 'nullable|string|max:2000',
            'is_regrettable' => 'boolean',
            'rehire_eligibility' => 'nullable|in:eligible,not_eligible,with_conditions',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
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

        // User business_user ma'lumotlarini olish
        $businessUser = DB::table('business_user')
            ->where('business_id', $businessId)
            ->where('user_id', $request->user_id)
            ->first();

        // Tenure hisoblash
        $tenureMonths = 0;
        if ($businessUser && $businessUser->accepted_at) {
            $startDate = Carbon::parse($businessUser->accepted_at);
            $endDate = Carbon::parse($request->termination_date);
            $tenureMonths = $startDate->diffInMonths($endDate);
        }

        $record = TurnoverRecord::create([
            'business_id' => $businessId,
            'user_id' => $request->user_id,
            'termination_date' => $request->termination_date,
            'last_working_day' => $request->last_working_day ?? $request->termination_date,
            'termination_type' => $request->termination_type,
            'termination_reason' => $request->termination_reason,
            'termination_reason_details' => $request->termination_reason_details,
            'department' => $businessUser->department ?? null,
            'position' => $businessUser->position ?? null,
            'tenure_months' => $tenureMonths,
            'is_regrettable' => $request->is_regrettable ?? false,
            'rehire_eligibility' => $request->rehire_eligibility,
            'replacement_status' => 'not_started',
            'notes' => $request->notes,
            'recorded_by' => Auth::id(),
        ]);

        // Business user ni yangilash
        DB::table('business_user')
            ->where('business_id', $businessId)
            ->where('user_id', $request->user_id)
            ->update(['left_at' => $request->termination_date]);

        return response()->json([
            'success' => true,
            'message' => 'Turnover yozuvi yaratildi',
            'data' => [
                'id' => $record->id,
            ],
        ], 201);
    }

    /**
     * Exit interview ma'lumotlarini saqlash
     */
    public function storeExitInterview(Request $request, string $businessId, string $recordId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'interview_date' => 'required|date',
            'notes' => 'nullable|string|max:5000',
            'feedback' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $record = TurnoverRecord::where('business_id', $businessId)
            ->where('id', $recordId)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Yozuv topilmadi',
            ], 404);
        }

        $record->update([
            'exit_interview_completed' => true,
            'exit_interview_date' => $request->interview_date,
            'exit_interview_notes' => $request->notes,
            'exit_interview_feedback' => $request->feedback,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exit interview saqlandi',
        ]);
    }

    /**
     * Replacement holatini yangilash
     */
    public function updateReplacementStatus(Request $request, string $businessId, string $recordId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:not_started,in_progress,hired,not_needed',
            'replacement_user_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $record = TurnoverRecord::where('business_id', $businessId)
            ->where('id', $recordId)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Yozuv topilmadi',
            ], 404);
        }

        $updateData = [
            'replacement_status' => $request->status,
        ];

        if ($request->status === 'hired' && $request->replacement_user_id) {
            $updateData['replacement_user_id'] = $request->replacement_user_id;
            $updateData['replacement_hired_date'] = now();
        }

        $record->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Replacement holati yangilandi',
        ]);
    }

    /**
     * Turnover statistikasi
     */
    public function statistics(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $year = $request->input('year', now()->year);

        // Jami hodimlar soni
        $totalEmployees = DB::table('business_user')
            ->where('business_id', $businessId)
            ->whereNotNull('accepted_at')
            ->whereNull('left_at')
            ->count();

        // Yillik turnover
        $yearlyTurnover = TurnoverRecord::where('business_id', $businessId)
            ->whereYear('termination_date', $year)
            ->count();

        // Turnover rate
        $turnoverRate = $totalEmployees > 0
            ? round(($yearlyTurnover / $totalEmployees) * 100, 2)
            : 0;

        // Type bo'yicha taqsimot
        $typeDistribution = TurnoverRecord::where('business_id', $businessId)
            ->whereYear('termination_date', $year)
            ->selectRaw('termination_type, COUNT(*) as count')
            ->groupBy('termination_type')
            ->pluck('count', 'termination_type')
            ->toArray();

        // Reason bo'yicha taqsimot
        $reasonDistribution = TurnoverRecord::where('business_id', $businessId)
            ->whereYear('termination_date', $year)
            ->selectRaw('termination_reason, COUNT(*) as count')
            ->groupBy('termination_reason')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'reason' => $r->termination_reason,
                'reason_label' => $this->getReasonLabel($r->termination_reason),
                'count' => $r->count,
            ]);

        // Oylik trend
        $monthlyTrend = TurnoverRecord::where('business_id', $businessId)
            ->whereYear('termination_date', $year)
            ->selectRaw('MONTH(termination_date) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        // Regrettable turnover
        $regrettableCount = TurnoverRecord::where('business_id', $businessId)
            ->whereYear('termination_date', $year)
            ->where('is_regrettable', true)
            ->count();

        $regrettableRate = $yearlyTurnover > 0
            ? round(($regrettableCount / $yearlyTurnover) * 100, 1)
            : 0;

        // Tenure distribution
        $tenureDistribution = TurnoverRecord::where('business_id', $businessId)
            ->whereYear('termination_date', $year)
            ->selectRaw('
                CASE
                    WHEN tenure_months < 3 THEN "0-3 oy"
                    WHEN tenure_months < 6 THEN "3-6 oy"
                    WHEN tenure_months < 12 THEN "6-12 oy"
                    WHEN tenure_months < 24 THEN "1-2 yil"
                    ELSE "2+ yil"
                END as tenure_group,
                COUNT(*) as count
            ')
            ->groupBy('tenure_group')
            ->pluck('count', 'tenure_group')
            ->toArray();

        // O'rtacha tenure
        $avgTenure = TurnoverRecord::where('business_id', $businessId)
            ->whereYear('termination_date', $year)
            ->avg('tenure_months') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'overview' => [
                    'total_employees' => $totalEmployees,
                    'yearly_turnover' => $yearlyTurnover,
                    'turnover_rate' => $turnoverRate,
                    'avg_tenure_months' => round($avgTenure, 1),
                ],
                'type_distribution' => [
                    'voluntary' => $typeDistribution['voluntary'] ?? 0,
                    'involuntary' => $typeDistribution['involuntary'] ?? 0,
                ],
                'top_reasons' => $reasonDistribution,
                'monthly_trend' => $monthlyTrend,
                'regrettable' => [
                    'count' => $regrettableCount,
                    'rate' => $regrettableRate,
                ],
                'tenure_distribution' => $tenureDistribution,
            ],
        ]);
    }

    /**
     * Turnover hisoboti (cache dan)
     */
    public function report(Request $request, string $businessId): JsonResponse
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->subMonth()->month);

        $key = "hr_turnover_report:{$businessId}:{$year}-{$month}";
        $report = cache()->get($key);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Bu davr uchun hisobot mavjud emas',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Termination type label
     */
    protected function getTerminationTypeLabel(string $type): string
    {
        return match ($type) {
            'voluntary' => "O'z ixtiyori bilan",
            'involuntary' => 'Majburiy',
            default => $type,
        };
    }

    /**
     * Reason label
     */
    protected function getReasonLabel(string $reason): string
    {
        return match ($reason) {
            'better_opportunity' => 'Yaxshiroq ish imkoniyati',
            'compensation' => 'Maosh qoniqarsiz',
            'management' => 'Boshqaruv muammolari',
            'career_growth' => "O'sish imkoniyati yo'q",
            'work_life_balance' => 'Ish-hayot balansi',
            'relocation' => "Ko'chib ketish",
            'personal' => 'Shaxsiy sabablar',
            'retirement' => 'Pensiya',
            'performance' => "Ish samaradorligi bo'yicha",
            'restructuring' => "Tashkiliy o'zgarishlar",
            default => $reason,
        };
    }

    /**
     * Replacement status label
     */
    protected function getReplacementStatusLabel(?string $status): string
    {
        return match ($status) {
            'not_started' => 'Boshlanmagan',
            'in_progress' => 'Jarayonda',
            'hired' => 'Topildi',
            'not_needed' => 'Kerak emas',
            default => $status ?? 'Noma\'lum',
        };
    }

    /**
     * Tenure formatlash
     */
    protected function formatTenure(int $months): string
    {
        if ($months < 12) {
            return "{$months} oy";
        }

        $years = floor($months / 12);
        $remainingMonths = $months % 12;

        if ($remainingMonths === 0) {
            return "{$years} yil";
        }

        return "{$years} yil {$remainingMonths} oy";
    }
}
