<?php

namespace App\Services\CRM;

use Illuminate\Support\Facades\DB;

/**
 * Lead Lifecycle Tracker — har lid bosqichlardan qancha vaqtda o'tganini hisoblaydi.
 *
 * lead_activities (status_changed) yozuvlaridan vaqt hisoblanadi.
 *
 * Beradi:
 *   - Bosqichlardagi o'rtacha vaqt
 *   - Bottleneck (qaysi bosqichda eng ko'p turadi)
 *   - Lead aging (qotib qolgan lidlar)
 *   - Conversion velocity
 */
class LeadLifecycleTracker
{
    /**
     * Bitta lid uchun lifecycle
     */
    public function getLeadLifecycle(string $leadId): array
    {
        $lead = DB::table('leads')->where('id', $leadId)->first();
        if (!$lead) return ['error' => 'Lid topilmadi'];

        $transitions = DB::table('lead_activities')
            ->where('lead_id', $leadId)
            ->where('type', 'status_changed')
            ->orderBy('created_at')
            ->get(['changes', 'created_at']);

        $stages = [];
        $createdAt = $lead->created_at;

        foreach ($transitions as $i => $t) {
            $changes = json_decode($t->changes, true);
            $from = $changes['old'] ?? 'new';
            $to = $changes['new'] ?? null;

            $startTime = $i === 0 ? $createdAt : $transitions[$i - 1]->created_at;
            $duration = strtotime($t->created_at) - strtotime($startTime);

            $stages[] = [
                'stage' => $from,
                'entered_at' => $startTime,
                'exited_at' => $t->created_at,
                'duration_seconds' => $duration,
                'duration_human' => $this->humanDuration($duration),
                'next_stage' => $to,
            ];
        }

        // Hozirgi bosqich
        $lastStageTime = !empty($transitions) ? $transitions->last()->created_at : $createdAt;
        $currentDuration = time() - strtotime($lastStageTime);

        return [
            'lead_id' => $leadId,
            'current_status' => $lead->status,
            'created_at' => $createdAt,
            'total_age_seconds' => time() - strtotime($createdAt),
            'total_age_human' => $this->humanDuration(time() - strtotime($createdAt)),
            'stages_traversed' => count($stages),
            'current_stage_duration' => $this->humanDuration($currentDuration),
            'is_stale' => $currentDuration > 86400 * 7, // 7+ kun
            'stages' => $stages,
        ];
    }

    /**
     * Biznes uchun bosqichlar bo'yicha o'rtacha vaqt
     */
    public function avgTimePerStage(string $businessId, int $days = 90): array
    {
        $since = now()->subDays($days);

        $transitions = DB::table('lead_activities as la')
            ->join('leads as l', 'la.lead_id', '=', 'l.id')
            ->where('l.business_id', $businessId)
            ->where('la.type', 'status_changed')
            ->where('la.created_at', '>=', $since)
            ->orderBy('la.lead_id')
            ->orderBy('la.created_at')
            ->get(['la.lead_id', 'la.changes', 'la.created_at']);

        // Per-lead transitions ni guruhlash
        $perLead = $transitions->groupBy('lead_id');

        $stageDurations = [];
        foreach ($perLead as $leadId => $leadTrans) {
            $previousTime = null;
            foreach ($leadTrans as $t) {
                $changes = json_decode($t->changes, true);
                $from = $changes['old'] ?? null;

                if ($from && $previousTime) {
                    $duration = strtotime($t->created_at) - strtotime($previousTime);
                    if (!isset($stageDurations[$from])) {
                        $stageDurations[$from] = ['total' => 0, 'count' => 0];
                    }
                    $stageDurations[$from]['total'] += $duration;
                    $stageDurations[$from]['count']++;
                }
                $previousTime = $t->created_at;
            }
        }

        $result = [];
        foreach ($stageDurations as $stage => $data) {
            $avgSec = $data['count'] > 0 ? round($data['total'] / $data['count']) : 0;
            $result[] = [
                'stage' => $stage,
                'avg_seconds' => $avgSec,
                'avg_human' => $this->humanDuration($avgSec),
                'avg_hours' => round($avgSec / 3600, 1),
                'sample_size' => $data['count'],
            ];
        }

        // Saralash — eng uzun turg'unlik birinchi
        usort($result, fn($a, $b) => $b['avg_seconds'] - $a['avg_seconds']);
        return $result;
    }

    /**
     * Bottleneck topish (eng ko'p qotib qolgan bosqich)
     */
    public function findBottleneck(string $businessId, int $days = 90): ?array
    {
        $stages = $this->avgTimePerStage($businessId, $days);
        if (empty($stages)) return null;

        $worst = $stages[0]; // birinchisi eng ko'p vaqt
        return [
            'stage' => $worst['stage'],
            'avg_hours' => $worst['avg_hours'],
            'sample_size' => $worst['sample_size'],
            'message' => "Lidlar \"{$worst['stage']}\" bosqichida o'rtacha {$worst['avg_human']} qolib ketmoqda",
        ];
    }

    /**
     * Qotib qolgan lidlar (stale) — 7+ kun bir bosqichda turgan
     */
    public function getStaleLeads(string $businessId, int $stalemateDays = 7): array
    {
        $since = now()->subDays($stalemateDays);

        return DB::table('leads as l')
            ->leftJoin('lead_activities as la', function ($join) {
                $join->on('la.lead_id', '=', 'l.id')
                    ->where('la.type', '=', 'status_changed');
            })
            ->where('l.business_id', $businessId)
            ->whereNotIn('l.status', ['won', 'lost'])
            ->where(function ($q) use ($since) {
                $q->where('la.created_at', '<', $since)
                    ->orWhereNull('la.created_at');
            })
            ->groupBy('l.id', 'l.name', 'l.phone', 'l.status', 'l.created_at')
            ->orderBy('l.created_at')
            ->limit(50)
            ->get([
                'l.id', 'l.name', 'l.phone', 'l.status', 'l.created_at',
                DB::raw('MAX(la.created_at) as last_activity'),
            ])
            ->map(function ($lead) {
                $lastTime = $lead->last_activity ?? $lead->created_at;
                $stalemateSeconds = time() - strtotime($lastTime);
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                    'status' => $lead->status,
                    'stale_for' => $this->humanDuration($stalemateSeconds),
                    'stale_days' => round($stalemateSeconds / 86400, 1),
                ];
            })
            ->toArray();
    }

    /**
     * Conversion velocity — yangi → won o'rtacha qancha kun
     */
    public function getConversionVelocity(string $businessId, int $days = 90): array
    {
        $since = now()->subDays($days);

        $wonLeads = DB::table('leads')
            ->where('business_id', $businessId)
            ->where('status', 'won')
            ->where('updated_at', '>=', $since)
            ->get(['id', 'created_at', 'updated_at']);

        if ($wonLeads->isEmpty()) {
            return ['avg_days' => 0, 'sample' => 0];
        }

        $totalDays = 0;
        foreach ($wonLeads as $lead) {
            $days = (strtotime($lead->updated_at) - strtotime($lead->created_at)) / 86400;
            $totalDays += $days;
        }

        return [
            'avg_days' => round($totalDays / $wonLeads->count(), 1),
            'sample' => $wonLeads->count(),
        ];
    }

    private function humanDuration(int $seconds): string
    {
        if ($seconds < 60) return $seconds . ' sek';
        if ($seconds < 3600) return round($seconds / 60) . ' daq';
        if ($seconds < 86400) return round($seconds / 3600, 1) . ' soat';
        return round($seconds / 86400, 1) . ' kun';
    }
}
