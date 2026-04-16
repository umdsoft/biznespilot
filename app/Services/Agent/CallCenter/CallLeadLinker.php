<?php

namespace App\Services\Agent\CallCenter;

use App\Models\CallLog;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Qo'ng'iroqlarni lidlar bilan avtomatik bog'lash.
 *
 * Mantiq:
 *   1. CallLog.from_number yoki to_number ni Lead.phone bilan solishtirish
 *   2. Telefon raqamlarni normalizatsiya qilish (faqat raqamlar)
 *   3. Agar topilsa — lead_id ni yozish
 *   4. Topilmasa — null qoldirib log yozish
 */
class CallLeadLinker
{
    /**
     * Bitta qo'ng'iroqni lid bilan bog'lash
     */
    public function linkCallToLead(CallLog $call): ?string
    {
        // Agar allaqachon bog'langan bo'lsa — o'tkazib yuborish
        if ($call->lead_id) {
            return $call->lead_id;
        }

        $customerPhone = $this->extractCustomerPhone($call);
        if (!$customerPhone) {
            return null;
        }

        $normalized = $this->normalizePhone($customerPhone);
        if (!$normalized) {
            return null;
        }

        // Lid qidirish — shu biznesda va shu telefon raqami bilan
        $lead = Lead::where('business_id', $call->business_id)
            ->where(function ($q) use ($normalized, $customerPhone) {
                $q->where('phone', $customerPhone)
                  ->orWhere('phone', $normalized)
                  ->orWhere('phone', 'LIKE', '%' . substr($normalized, -9) . '%');
            })
            ->orderByDesc('created_at')
            ->first();

        if (!$lead) {
            Log::info('CallLeadLinker: lid topilmadi', [
                'call_id' => $call->id,
                'phone' => $customerPhone,
            ]);
            return null;
        }

        $call->update(['lead_id' => $lead->id]);

        Log::info('CallLeadLinker: lid bog\'landi', [
            'call_id' => $call->id,
            'lead_id' => $lead->id,
            'lead_name' => $lead->name,
        ]);

        return $lead->id;
    }

    /**
     * Barcha bog'lanmagan qo'ng'iroqlarni toplu ravishda bog'lash
     */
    public function linkAllUnlinked(string $businessId): array
    {
        $calls = CallLog::where('business_id', $businessId)
            ->whereNull('lead_id')
            ->whereNotNull('from_number')
            ->limit(500)
            ->get();

        $linked = 0;
        $notFound = 0;

        foreach ($calls as $call) {
            $result = $this->linkCallToLead($call);
            if ($result) {
                $linked++;
            } else {
                $notFound++;
            }
        }

        return [
            'total' => $calls->count(),
            'linked' => $linked,
            'not_found' => $notFound,
        ];
    }

    /**
     * Qo'ng'iroqdan mijoz telefonini ajratish.
     * direction='incoming' bo'lsa — from_number mijoz
     * direction='outgoing' bo'lsa — to_number mijoz
     */
    private function extractCustomerPhone(CallLog $call): ?string
    {
        return match ($call->direction) {
            'incoming' => $call->from_number,
            'outgoing' => $call->to_number,
            default => $call->from_number ?: $call->to_number,
        };
    }

    /**
     * Telefon raqamni normalizatsiya qilish.
     * "+998 90 123 45 67" → "998901234567"
     */
    private function normalizePhone(string $phone): ?string
    {
        $cleaned = preg_replace('/\D/', '', $phone);
        if (!$cleaned) return null;

        // Agar 9 raqam bo'lsa — 998 prefixi qo'shish
        if (strlen($cleaned) === 9) {
            $cleaned = '998' . $cleaned;
        }

        // Agar 12 raqamdan oshsa — oxirgi 12 ta
        if (strlen($cleaned) > 12) {
            $cleaned = substr($cleaned, -12);
        }

        return $cleaned;
    }
}
