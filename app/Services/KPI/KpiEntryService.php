<?php

namespace App\Services\KPI;

use App\Models\Business;
use App\Models\KpiDailyEntry;
use App\Models\KpiDailySourceDetail;
use App\Models\LeadSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KpiEntryService
{
    /**
     * Get or create daily entry for a specific date
     */
    public function getOrCreateDaily(Business $business, string $date): KpiDailyEntry
    {
        return KpiDailyEntry::firstOrCreate(
            [
                'business_id' => $business->id,
                'date' => $date,
            ],
            [
                'source' => 'manual',
                'created_by' => Auth::id(),
            ]
        );
    }

    /**
     * Save quick entry (minimal fields)
     */
    public function saveQuickEntry(Business $business, array $data): KpiDailyEntry
    {
        $entry = $this->getOrCreateDaily($business, $data['date']);

        $entry->fill([
            'leads_digital' => $data['leads_digital'] ?? 0,
            'leads_offline' => $data['leads_offline'] ?? 0,
            'leads_referral' => $data['leads_referral'] ?? 0,
            'leads_organic' => $data['leads_organic'] ?? 0,
            'spend_digital' => $data['spend_digital'] ?? 0,
            'spend_offline' => $data['spend_offline'] ?? 0,
            'spend_other' => $data['spend_other'] ?? 0,
            'sales_new' => $data['sales_new'] ?? 0,
            'sales_repeat' => $data['sales_repeat'] ?? 0,
            'revenue_new' => $data['revenue_new'] ?? 0,
            'revenue_repeat' => $data['revenue_repeat'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'source' => 'manual',
            'created_by' => Auth::id(),
        ]);

        $entry->save();

        return $entry;
    }

    /**
     * Save full entry with source details
     */
    public function saveFullEntry(Business $business, array $data): KpiDailyEntry
    {
        return DB::transaction(function () use ($business, $data) {
            // Save main entry
            $entry = $this->saveQuickEntry($business, $data);

            // Save payment breakdown if provided
            if (isset($data['payments'])) {
                $entry->update([
                    'payment_cash' => $data['payments']['cash'] ?? 0,
                    'payment_card' => $data['payments']['card'] ?? 0,
                    'payment_transfer' => $data['payments']['transfer'] ?? 0,
                    'payment_credit' => $data['payments']['credit'] ?? 0,
                    'payment_other' => $data['payments']['other'] ?? 0,
                ]);
            }

            // Save source details if provided
            if (isset($data['source_details']) && is_array($data['source_details'])) {
                $this->saveSourceDetails($entry, $data['source_details']);
            }

            return $entry->fresh();
        });
    }

    /**
     * Save source details for an entry
     */
    public function saveSourceDetails(KpiDailyEntry $entry, array $details): void
    {
        foreach ($details as $detail) {
            if (empty($detail['lead_source_id'])) {
                continue;
            }

            KpiDailySourceDetail::updateOrCreate(
                [
                    'daily_entry_id' => $entry->id,
                    'lead_source_id' => $detail['lead_source_id'],
                ],
                [
                    'leads_count' => $detail['leads_count'] ?? 0,
                    'spend_amount' => $detail['spend_amount'] ?? 0,
                    'conversions' => $detail['conversions'] ?? 0,
                    'revenue' => $detail['revenue'] ?? 0,
                ]
            );
        }
    }

    /**
     * Get daily entry with details
     */
    public function getDailyWithDetails(Business $business, string $date): ?KpiDailyEntry
    {
        return KpiDailyEntry::with('sourceDetails.leadSource')
            ->where('business_id', $business->id)
            ->where('date', $date)
            ->first();
    }

    /**
     * Get entries for a week
     */
    public function getWeekEntries(Business $business, int $year, int $week): array
    {
        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        $entries = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->orderBy('date')
            ->get()
            ->keyBy(fn($entry) => $entry->date->format('Y-m-d'));

        // Build complete week with empty entries for missing days
        $weekData = [];
        $currentDate = $startOfWeek->copy();

        while ($currentDate <= $endOfWeek) {
            $dateStr = $currentDate->format('Y-m-d');
            $weekData[$dateStr] = $entries->get($dateStr) ?? $this->getEmptyEntry($currentDate);
            $currentDate->addDay();
        }

        return [
            'year' => $year,
            'week' => $week,
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'entries' => $weekData,
            'totals' => $this->calculateWeekTotals($entries),
        ];
    }

    /**
     * Get empty entry structure for a date
     */
    protected function getEmptyEntry(Carbon $date): array
    {
        return [
            'date' => $date->format('Y-m-d'),
            'day_name' => $this->getDayName($date),
            'short_day' => $this->getShortDayName($date),
            'leads_digital' => 0,
            'leads_offline' => 0,
            'leads_referral' => 0,
            'leads_total' => 0,
            'spend_digital' => 0,
            'spend_offline' => 0,
            'spend_total' => 0,
            'sales_new' => 0,
            'sales_repeat' => 0,
            'sales_total' => 0,
            'revenue_total' => 0,
            'is_empty' => true,
        ];
    }

    /**
     * Calculate week totals from entries
     */
    protected function calculateWeekTotals($entries): array
    {
        return [
            'leads_digital' => $entries->sum('leads_digital'),
            'leads_offline' => $entries->sum('leads_offline'),
            'leads_referral' => $entries->sum('leads_referral'),
            'leads_total' => $entries->sum('leads_total'),
            'spend_digital' => $entries->sum('spend_digital'),
            'spend_offline' => $entries->sum('spend_offline'),
            'spend_total' => $entries->sum('spend_total'),
            'sales_new' => $entries->sum('sales_new'),
            'sales_repeat' => $entries->sum('sales_repeat'),
            'sales_total' => $entries->sum('sales_total'),
            'revenue_total' => $entries->sum('revenue_total'),
            'days_with_data' => $entries->where('is_complete', true)->count(),
        ];
    }

    /**
     * Get entries for current month
     */
    public function getCurrentMonthEntries(Business $business): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now();

        $entries = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startOfMonth, $today])
            ->orderBy('date')
            ->get();

        return [
            'entries' => $entries,
            'totals' => $this->calculateMonthTotals($entries),
            'days_passed' => $startOfMonth->diffInDays($today) + 1,
            'days_with_data' => $entries->where('is_complete', true)->count(),
        ];
    }

    /**
     * Calculate month totals
     */
    protected function calculateMonthTotals($entries): array
    {
        $totals = [
            'leads_digital' => $entries->sum('leads_digital'),
            'leads_offline' => $entries->sum('leads_offline'),
            'leads_referral' => $entries->sum('leads_referral'),
            'leads_organic' => $entries->sum('leads_organic'),
            'leads_total' => $entries->sum('leads_total'),
            'spend_digital' => $entries->sum('spend_digital'),
            'spend_offline' => $entries->sum('spend_offline'),
            'spend_total' => $entries->sum('spend_total'),
            'sales_new' => $entries->sum('sales_new'),
            'sales_repeat' => $entries->sum('sales_repeat'),
            'sales_total' => $entries->sum('sales_total'),
            'revenue_new' => $entries->sum('revenue_new'),
            'revenue_repeat' => $entries->sum('revenue_repeat'),
            'revenue_total' => $entries->sum('revenue_total'),
        ];

        // Calculate metrics
        if ($totals['sales_total'] > 0) {
            $totals['avg_check'] = $totals['revenue_total'] / $totals['sales_total'];
        } else {
            $totals['avg_check'] = 0;
        }

        if ($totals['leads_total'] > 0) {
            $totals['conversion_rate'] = ($totals['sales_total'] / $totals['leads_total']) * 100;
            $totals['cpl'] = $totals['spend_total'] / $totals['leads_total'];
        } else {
            $totals['conversion_rate'] = 0;
            $totals['cpl'] = 0;
        }

        if ($totals['sales_new'] > 0) {
            $totals['cac'] = $totals['spend_total'] / $totals['sales_new'];
        } else {
            $totals['cac'] = 0;
        }

        if ($totals['spend_total'] > 0) {
            $totals['roi'] = (($totals['revenue_total'] - $totals['spend_total']) / $totals['spend_total']) * 100;
            $totals['roas'] = $totals['revenue_total'] / $totals['spend_total'];
        } else {
            $totals['roi'] = 0;
            $totals['roas'] = 0;
        }

        return $totals;
    }

    /**
     * Delete entry
     */
    public function deleteEntry(KpiDailyEntry $entry): bool
    {
        return $entry->delete();
    }

    /**
     * Get lead sources for business
     */
    public function getLeadSources(Business $business): array
    {
        return LeadSource::forBusiness($business->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Get day name in Uzbek
     */
    protected function getDayName(Carbon $date): string
    {
        $days = [
            'Monday' => 'Dushanba',
            'Tuesday' => 'Seshanba',
            'Wednesday' => 'Chorshanba',
            'Thursday' => 'Payshanba',
            'Friday' => 'Juma',
            'Saturday' => 'Shanba',
            'Sunday' => 'Yakshanba',
        ];

        return $days[$date->format('l')] ?? '';
    }

    /**
     * Get short day name
     */
    protected function getShortDayName(Carbon $date): string
    {
        $days = [
            'Monday' => 'Du',
            'Tuesday' => 'Se',
            'Wednesday' => 'Ch',
            'Thursday' => 'Pa',
            'Friday' => 'Ju',
            'Saturday' => 'Sh',
            'Sunday' => 'Ya',
        ];

        return $days[$date->format('l')] ?? '';
    }
}
