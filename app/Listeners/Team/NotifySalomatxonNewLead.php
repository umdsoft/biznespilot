<?php

namespace App\Listeners\Team;

use App\Events\LeadStageChanged;
use App\Services\Team\InterAgentMessenger;

/**
 * Yangi lead yaratilganda Salomatxon ga xabar (bepul, AI yo'q).
 */
class NotifySalomatxonNewLead
{
    public function handle(LeadStageChanged $event): void
    {
        if (!isset($event->lead) || !$event->lead->business_id) return;

        InterAgentMessenger::send(
            businessId: $event->lead->business_id,
            fromAgent: 'jasurbek',
            toAgent: 'salomatxon',
            messageType: 'info',
            content: "Yangi lead: {$event->lead->name}. Darhol bog'laning!",
            entityType: 'lead',
            entityId: $event->lead->id,
        );
    }
}
