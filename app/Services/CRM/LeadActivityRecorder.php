<?php

namespace App\Services\CRM;

use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lead Activity Recorder — har bir lid faoliyatini avtomatik log qiladi.
 *
 * Activity turlari:
 *   - status_changed (status o'zgardi)
 *   - assigned (operator biriktirildi)
 *   - call (qo'ng'iroq)
 *   - note (izoh qo'shildi)
 *   - email (email yuborildi)
 *   - sms (sms yuborildi)
 *   - meeting (uchrashuv)
 *   - contacted (mijoz bilan bog'lanildi)
 *   - score_changed (score o'zgardi)
 *   - won/lost (yakuniy natija)
 */
class LeadActivityRecorder
{
    /**
     * Status o'zgarishi yozish
     */
    public function recordStatusChange(Lead $lead, string $oldStatus, string $newStatus, ?string $userId = null): void
    {
        $this->record($lead, 'status_changed', [
            'title' => "Status: {$oldStatus} → {$newStatus}",
            'description' => "Lid statusi \"{$oldStatus}\" dan \"{$newStatus}\" ga o'zgardi",
            'changes' => ['old' => $oldStatus, 'new' => $newStatus],
            'user_id' => $userId,
        ]);
    }

    /**
     * Operator biriktirish
     */
    public function recordAssignment(Lead $lead, ?string $oldOperatorId, string $newOperatorId): void
    {
        $newOperator = DB::table('users')->where('id', $newOperatorId)->value('name');

        $this->record($lead, 'assigned', [
            'title' => "Operator: {$newOperator}",
            'description' => "Lid {$newOperator} ga biriktirildi",
            'changes' => ['old' => $oldOperatorId, 'new' => $newOperatorId],
        ]);
    }

    /**
     * Qo'ng'iroq yozish
     */
    public function recordCall(Lead $lead, string $direction, int $duration, string $status, ?string $callLogId = null): void
    {
        $directionLabel = $direction === 'incoming' ? 'kiruvchi' : 'chiquvchi';
        $statusLabel = $status === 'answered' ? 'javob berildi' : 'javob berilmadi';

        $this->record($lead, 'call', [
            'title' => "Qo'ng'iroq: {$directionLabel}, {$statusLabel}",
            'description' => "Davomiyligi: " . round($duration / 60, 1) . " daq",
            'metadata' => [
                'direction' => $direction,
                'duration' => $duration,
                'status' => $status,
                'call_log_id' => $callLogId,
            ],
        ]);
    }

    /**
     * Izoh qo'shildi
     */
    public function recordNote(Lead $lead, string $note, ?string $userId = null): void
    {
        $this->record($lead, 'note', [
            'title' => 'Izoh qo\'shildi',
            'description' => mb_substr($note, 0, 200),
            'user_id' => $userId,
        ]);
    }

    /**
     * Score o'zgarishi
     */
    public function recordScoreChange(Lead $lead, int $oldScore, int $newScore, ?string $reason = null): void
    {
        $delta = $newScore - $oldScore;
        $sign = $delta > 0 ? '+' : '';

        $this->record($lead, 'score_changed', [
            'title' => "Score: {$oldScore} → {$newScore} ({$sign}{$delta})",
            'description' => $reason ?? 'Lid bali yangilandi',
            'changes' => ['old' => $oldScore, 'new' => $newScore, 'delta' => $delta],
        ]);
    }

    /**
     * Won/Lost yakuni
     */
    public function recordOutcome(Lead $lead, string $outcome, ?string $reason = null): void
    {
        $title = $outcome === 'won' ? '🏆 Lid yutildi' : '❌ Lid yo\'qotildi';

        $this->record($lead, $outcome, [
            'title' => $title,
            'description' => $reason ?? '',
            'metadata' => ['outcome' => $outcome, 'reason' => $reason],
        ]);
    }

    /**
     * Email yuborilgani
     */
    public function recordEmail(Lead $lead, string $subject): void
    {
        $this->record($lead, 'email', [
            'title' => 'Email yuborildi',
            'description' => $subject,
        ]);
    }

    /**
     * Mijoz bilan bog'lanildi
     */
    public function recordContact(Lead $lead, string $method, ?string $userId = null): void
    {
        $this->record($lead, 'contacted', [
            'title' => "Bog'lanildi ({$method})",
            'description' => '',
            'user_id' => $userId,
        ]);
    }

    /**
     * Universal yozish
     */
    public function record(Lead $lead, string $type, array $data): void
    {
        try {
            DB::table('lead_activities')->insert([
                'lead_id' => $lead->id,
                'user_id' => $data['user_id'] ?? Auth::id(),
                'type' => $type,
                'title' => $data['title'] ?? '',
                'description' => $data['description'] ?? null,
                'changes' => isset($data['changes']) ? json_encode($data['changes']) : null,
                'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('LeadActivity yozishda xato', [
                'lead_id' => $lead->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lid uchun barcha faoliyatlar (timeline)
     */
    public function getTimeline(string $leadId, int $limit = 50): array
    {
        return DB::table('lead_activities')
            ->leftJoin('users', 'lead_activities.user_id', '=', 'users.id')
            ->where('lead_id', $leadId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get([
                'lead_activities.*',
                'users.name as user_name',
            ])
            ->toArray();
    }
}
