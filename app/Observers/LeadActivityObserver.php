<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\CRM\LeadActivityRecorder;

/**
 * Lead model observer — har Lead update'da activity log yozadi.
 *
 * Eski kod bilan parallel — ishlayotgan logikani buzmaydi.
 */
class LeadActivityObserver
{
    public function __construct(
        private LeadActivityRecorder $recorder,
    ) {}

    /**
     * Lid yangilanganda
     */
    public function updated(Lead $lead): void
    {
        try {
            // Status o'zgarishi
            if ($lead->wasChanged('status')) {
                $this->recorder->recordStatusChange(
                    $lead,
                    $lead->getOriginal('status') ?? 'new',
                    $lead->status,
                );
            }

            // Operator biriktirilishi
            if ($lead->wasChanged('assigned_to')) {
                $newOp = $lead->assigned_to;
                if ($newOp) {
                    $this->recorder->recordAssignment(
                        $lead,
                        $lead->getOriginal('assigned_to'),
                        $newOp,
                    );
                }
            }

            // Score o'zgarishi
            if ($lead->wasChanged('score')) {
                $this->recorder->recordScoreChange(
                    $lead,
                    (int) ($lead->getOriginal('score') ?? 0),
                    (int) $lead->score,
                );
            }

            // Won/Lost
            if ($lead->wasChanged('status') && in_array($lead->status, ['won', 'lost'])) {
                $reason = $lead->status === 'lost' ? ($lead->lost_reason ?? null) : null;
                $this->recorder->recordOutcome($lead, $lead->status, $reason);
            }
        } catch (\Exception $e) {
            // Activity log xatosi asosiy logikani buzmasin
        }
    }
}
