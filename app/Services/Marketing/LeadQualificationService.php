<?php

namespace App\Services\Marketing;

use App\Models\Lead;
use App\Models\LeadQualification;
use App\Models\User;
use App\Events\LeadQualificationChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeadQualificationService - Lead MQL/SQL qualification boshqaruvi
 */
class LeadQualificationService
{
    /**
     * MQL shartlari.
     */
    private const MQL_MIN_SCORE = 50;

    /**
     * SQL shartlari.
     */
    private const SQL_MIN_SCORE = 70;

    /**
     * Lead ni MQL ga o'tkazish.
     */
    public function qualifyAsMql(Lead $lead, ?User $user = null, ?string $reason = null): void
    {
        if ($lead->qualification_status === 'mql') {
            return; // Allaqachon MQL
        }

        $fromStatus = $lead->qualification_status ?? 'new';

        DB::transaction(function () use ($lead, $user, $reason, $fromStatus) {
            // Lead yangilash
            $lead->qualification_status = 'mql';
            $lead->qualified_at = now();
            $lead->qualified_by = $user?->id;
            $lead->saveQuietly();

            // Log yozish
            LeadQualification::create([
                'business_id' => $lead->business_id,
                'lead_id' => $lead->id,
                'from_status' => $fromStatus,
                'to_status' => 'mql',
                'qualified_by' => $user?->id,
                'reason' => $reason ?? 'Manual qualification to MQL',
                'criteria_snapshot' => $this->buildCriteriaSnapshot($lead),
            ]);
        });

        // Event dispatch
        event(new LeadQualificationChanged($lead, $fromStatus, 'mql', $user));

        Log::info('LeadQualificationService: Lead qualified as MQL', [
            'lead_id' => $lead->id,
            'from' => $fromStatus,
            'by' => $user?->id,
        ]);
    }

    /**
     * Lead ni SQL ga o'tkazish.
     */
    public function qualifyAsSql(Lead $lead, ?User $user = null, ?string $reason = null): void
    {
        if ($lead->qualification_status === 'sql') {
            return;
        }

        $fromStatus = $lead->qualification_status ?? 'new';

        DB::transaction(function () use ($lead, $user, $reason, $fromStatus) {
            $lead->qualification_status = 'sql';
            $lead->qualified_at = now();
            $lead->qualified_by = $user?->id;
            $lead->saveQuietly();

            LeadQualification::create([
                'business_id' => $lead->business_id,
                'lead_id' => $lead->id,
                'from_status' => $fromStatus,
                'to_status' => 'sql',
                'qualified_by' => $user?->id,
                'reason' => $reason ?? 'Manual qualification to SQL',
                'criteria_snapshot' => $this->buildCriteriaSnapshot($lead),
            ]);
        });

        event(new LeadQualificationChanged($lead, $fromStatus, 'sql', $user));

        Log::info('LeadQualificationService: Lead qualified as SQL', [
            'lead_id' => $lead->id,
            'from' => $fromStatus,
            'by' => $user?->id,
        ]);
    }

    /**
     * Lead ni disqualify qilish.
     */
    public function disqualify(Lead $lead, ?User $user = null, string $reason = 'Not qualified'): void
    {
        if ($lead->qualification_status === 'disqualified') {
            return;
        }

        $fromStatus = $lead->qualification_status ?? 'new';

        DB::transaction(function () use ($lead, $user, $reason, $fromStatus) {
            $lead->qualification_status = 'disqualified';
            $lead->qualified_at = now();
            $lead->qualified_by = $user?->id;
            $lead->saveQuietly();

            LeadQualification::create([
                'business_id' => $lead->business_id,
                'lead_id' => $lead->id,
                'from_status' => $fromStatus,
                'to_status' => 'disqualified',
                'qualified_by' => $user?->id,
                'reason' => $reason,
                'criteria_snapshot' => $this->buildCriteriaSnapshot($lead),
            ]);
        });

        event(new LeadQualificationChanged($lead, $fromStatus, 'disqualified', $user));

        Log::info('LeadQualificationService: Lead disqualified', [
            'lead_id' => $lead->id,
            'from' => $fromStatus,
            'reason' => $reason,
        ]);
    }

    /**
     * MQL bo'lish shartlarini tekshirish.
     */
    public function canQualifyAsMql(Lead $lead): bool
    {
        // Minimum score
        if (($lead->score ?? 0) < self::MQL_MIN_SCORE) {
            return false;
        }

        // Contact ma'lumotlari (kamida bittasi)
        if (empty($lead->phone) && empty($lead->email)) {
            return false;
        }

        return true;
    }

    /**
     * SQL bo'lish shartlarini tekshirish.
     */
    public function canQualifyAsSql(Lead $lead): bool
    {
        // Minimum score
        if (($lead->score ?? 0) < self::SQL_MIN_SCORE) {
            return false;
        }

        // MQL bo'lishi kerak (yoki allaqachon SQL)
        if (!in_array($lead->qualification_status, ['mql', 'sql'])) {
            return false;
        }

        return true;
    }

    /**
     * Avtomatik qualification (score asosida).
     */
    public function autoQualify(Lead $lead): void
    {
        // Allaqachon qualified yoki disqualified
        if (in_array($lead->qualification_status, ['sql', 'disqualified'])) {
            return;
        }

        // SQL ga o'tkazish mumkinmi?
        if ($lead->qualification_status === 'mql' && $this->canQualifyAsSql($lead)) {
            $this->qualifyAsSql($lead, null, 'Auto-qualified based on score and criteria');
            return;
        }

        // MQL ga o'tkazish mumkinmi?
        if (($lead->qualification_status === 'new' || $lead->qualification_status === null) && $this->canQualifyAsMql($lead)) {
            $this->qualifyAsMql($lead, null, 'Auto-qualified based on score and contact info');
        }
    }

    /**
     * Requalify - qayta qualification tekshirish.
     */
    public function requalify(Lead $lead, ?User $user = null): void
    {
        $previousStatus = $lead->qualification_status;

        // SQL shartlarini tekshirish
        if ($this->canQualifyAsSql($lead) && $previousStatus !== 'sql') {
            $this->qualifyAsSql($lead, $user, 'Re-qualified based on updated criteria');
            return;
        }

        // MQL shartlarini tekshirish
        if ($this->canQualifyAsMql($lead) && !in_array($previousStatus, ['mql', 'sql'])) {
            $this->qualifyAsMql($lead, $user, 'Re-qualified based on updated criteria');
        }
    }

    /**
     * Qualification paytidagi lead ma'lumotlarini snapshot qilish.
     */
    private function buildCriteriaSnapshot(Lead $lead): array
    {
        return [
            'score' => $lead->score,
            'score_category' => $lead->score_category,
            'status' => $lead->status,
            'has_phone' => !empty($lead->phone),
            'has_email' => !empty($lead->email),
            'has_company' => !empty($lead->company),
            'estimated_value' => $lead->estimated_value,
            'source' => $lead->source?->name,
            'campaign' => $lead->campaign?->name,
            'channel' => $lead->marketingChannel?->name,
            'created_at' => $lead->created_at->toIso8601String(),
            'days_since_creation' => $lead->created_at->diffInDays(now()),
            'activities_count' => $lead->activities()->count(),
            'calls_count' => $lead->calls()->count(),
            'tasks_count' => $lead->tasks()->count(),
        ];
    }

    /**
     * Get qualification statistics for business.
     */
    public function getQualificationStats(string $businessId, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now();

        $query = Lead::where('business_id', $businessId)
            ->whereBetween('created_at', [$from, $to]);

        $total = (clone $query)->count();
        $newCount = (clone $query)->where('qualification_status', 'new')->count();
        $mqlCount = (clone $query)->where('qualification_status', 'mql')->count();
        $sqlCount = (clone $query)->where('qualification_status', 'sql')->count();
        $disqualifiedCount = (clone $query)->where('qualification_status', 'disqualified')->count();

        return [
            'total' => $total,
            'new' => $newCount,
            'mql' => $mqlCount,
            'sql' => $sqlCount,
            'disqualified' => $disqualifiedCount,
            'rates' => [
                'lead_to_mql' => $total > 0 ? round(($mqlCount + $sqlCount) / $total * 100, 2) : 0,
                'mql_to_sql' => ($mqlCount + $sqlCount) > 0 ? round($sqlCount / ($mqlCount + $sqlCount) * 100, 2) : 0,
                'disqualification' => $total > 0 ? round($disqualifiedCount / $total * 100, 2) : 0,
            ],
        ];
    }
}
