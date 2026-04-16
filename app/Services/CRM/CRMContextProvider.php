<?php

namespace App\Services\CRM;

use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CRM Context Provider — AI Agent uchun lid kontekstini taqdim etadi.
 *
 * Salomatxon (Sales agent), Umidbek (Director) lid haqida to'liq tasavvurga ega bo'ladi:
 *   - Hozirgi status va vaqt
 *   - Activity tarixi
 *   - Qo'ng'iroqlar (so'nggi sentiment)
 *   - Score history
 *   - Bog'liq mahsulot/kampaniya
 */
class CRMContextProvider
{
    private const CACHE_TTL = 300;

    public function __construct(
        private LeadOrchestrator $orchestrator,
        private LeadLifecycleTracker $lifecycle,
    ) {}

    /**
     * Bitta lid uchun to'liq AI kontekst (matn formatida)
     */
    public function buildLeadContext(string $leadId): string
    {
        $cacheKey = "crm_lead_context:{$leadId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($leadId) {
            $lead = Lead::find($leadId);
            if (!$lead) return '';

            $parts = ['LID MA\'LUMOTI:'];
            $parts[] = "- Ismi: {$lead->name}";
            $parts[] = "- Telefon: " . ($lead->phone ?? 'yo\'q');
            $parts[] = "- Status: {$lead->status}";
            $parts[] = "- Score: " . ($lead->score ?? 0) . "/100";

            if ($lead->estimated_value) {
                $parts[] = "- Qiymat: " . number_format($lead->estimated_value) . " so'm";
            }

            // Activity statistikasi
            $activityCount = DB::table('lead_activities')->where('lead_id', $leadId)->count();
            $callCount = DB::table('call_logs')->where('lead_id', $leadId)->count();

            $parts[] = "- Activity: {$activityCount} ta yozuv, {$callCount} ta qo'ng'iroq";

            // So'nggi qo'ng'iroq tahlili
            $lastCall = DB::table('call_analyses as ca')
                ->join('call_logs as cl', 'ca.call_log_id', '=', 'cl.id')
                ->where('cl.lead_id', $leadId)
                ->orderByDesc('ca.created_at')
                ->first(['ca.overall_score', 'ca.sentiment_customer', 'ca.win_probability']);

            if ($lastCall) {
                $parts[] = "- So'nggi qo'ng'iroq tahlili:";
                $parts[] = "  - Ball: {$lastCall->overall_score}/100";
                $parts[] = "  - Mijoz kayfiyati: {$lastCall->sentiment_customer}";
                $parts[] = "  - Win probability: {$lastCall->win_probability}%";
            }

            // Lifecycle
            $lifecycle = $this->lifecycle->getLeadLifecycle($leadId);
            if (isset($lifecycle['total_age_human'])) {
                $parts[] = "- Yoshi: {$lifecycle['total_age_human']}";
                $parts[] = "- Hozirgi bosqichda: {$lifecycle['current_stage_duration']}";
                if ($lifecycle['is_stale']) {
                    $parts[] = "- ⚠️ STALE — 7+ kun e'tiborsiz";
                }
            }

            // Lost reason (agar lost bo'lsa)
            if ($lead->status === 'lost' && $lead->lost_reason) {
                $parts[] = "- Lost sabab: {$lead->lost_reason}";
            }

            return implode("\n", $parts);
        });
    }

    /**
     * Biznes uchun umumiy CRM kontekst
     */
    public function buildBusinessCRMContext(string $businessId): string
    {
        $snapshot = $this->orchestrator->getSnapshot($businessId);

        if (isset($snapshot['error'])) return '';

        $parts = ['CRM HOLATI:'];
        $parts[] = "- Sog'lik: {$snapshot['health']['overall']}/100 ({$snapshot['health']['grade']})";
        $parts[] = "- Jami lidlar: {$snapshot['stats']['total']}";
        $parts[] = "- In progress: {$snapshot['stats']['in_progress']}, Won: {$snapshot['stats']['won']}, Lost: {$snapshot['stats']['lost']}";
        $parts[] = "- Hot leads: {$snapshot['stats']['hot_leads']}";
        $parts[] = "- Pipeline value: " . number_format($snapshot['stats']['pipeline_value']) . " so'm";
        $parts[] = "- Conversion: {$snapshot['stats']['conversion_rate']}%";

        if ($snapshot['stats']['unassigned_count'] > 0) {
            $parts[] = "- ⚠️ UNASSIGNED: {$snapshot['stats']['unassigned_count']} ta lid biriktirilmagan";
        }

        if ($snapshot['stale_leads_count'] > 0) {
            $parts[] = "- ⚠️ STALE: {$snapshot['stale_leads_count']} ta lid 7+ kun e'tiborsiz";
        }

        if ($snapshot['bottleneck']) {
            $parts[] = "- BOTTLENECK: {$snapshot['bottleneck']['stage']} bosqichida o'rtacha {$snapshot['bottleneck']['avg_hours']} soat";
        }

        if ($snapshot['conversion_velocity']['avg_days'] > 0) {
            $parts[] = "- O'rtacha won bo'lish: {$snapshot['conversion_velocity']['avg_days']} kun";
        }

        return implode("\n", $parts);
    }

    /**
     * AgentContextEnricher orqali integratsiya
     */
    public function attachToAgentContext(string $businessId): string
    {
        return $this->buildBusinessCRMContext($businessId);
    }
}
