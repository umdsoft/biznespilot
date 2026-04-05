<?php

namespace App\Services\Team;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Agentlararo xabar almashish xizmati.
 * Event/Listener orqali chaqiriladi — AI chaqirilMAYDI (bepul).
 */
class InterAgentMessenger
{
    /**
     * Agent xabar yuborish
     */
    public static function send(
        string $businessId,
        string $fromAgent,
        string $toAgent,
        string $messageType,
        string $content,
        ?string $entityType = null,
        ?string $entityId = null,
    ): void {
        DB::table('team_messages')->insert([
            'id' => Str::uuid()->toString(),
            'business_id' => $businessId,
            'from_agent' => $fromAgent,
            'to_agent' => $toAgent,
            'message_type' => $messageType,
            'content' => $content,
            'related_entity_type' => $entityType,
            'related_entity_id' => $entityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * O'qilmagan xabarlarni olish
     */
    public static function getUnread(string $businessId, string $agentId): array
    {
        return DB::table('team_messages')
            ->where('business_id', $businessId)
            ->where('to_agent', $agentId)
            ->whereNull('read_at')
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Xabarlarni o'qilgan deb belgilash
     */
    public static function markRead(string $businessId, string $agentId): void
    {
        DB::table('team_messages')
            ->where('business_id', $businessId)
            ->where('to_agent', $agentId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
