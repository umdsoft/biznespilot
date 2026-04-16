<?php

namespace App\Services\Telephony\Utel;

use App\Models\CallLog;
use App\Models\Lead;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Log;

/**
 * UTEL Lead Matcher — telefon orqali lid qidirish/yaratish
 * (CallLeadLinker bilan birlashtirilgan, false-positive verification bilan)
 */
class UtelLeadMatcher
{
    /**
     * Kelgan qo'ng'iroq uchun lid topish yoki yaratish
     */
    public function matchOrCreate(CallLog $callLog, string $phoneNumber): ?Lead
    {
        $businessId = $callLog->business_id;
        $cleanPhone = $this->normalizePhone($phoneNumber);
        $last9 = substr($cleanPhone, -9);

        if (strlen($last9) < 9) {
            Log::warning('UtelLeadMatcher: telefon juda qisqa', ['phone' => $phoneNumber]);
            return null;
        }

        // Mavjud lidlar orasidan qidirish
        $lead = $this->findExisting($businessId, $cleanPhone, $last9);

        // False positive tekshirish (oxirgi 9 raqam mos kelsa)
        if ($lead && !$this->verifyMatch($lead, $last9)) {
            Log::warning('UtelLeadMatcher: false positive', [
                'caller_phone' => $phoneNumber,
                'lead_phone' => $lead->phone,
            ]);
            $lead = null;
        }

        if ($lead) {
            $callLog->update(['lead_id' => $lead->id]);
            $lead->update(['last_contacted_at' => now()]);
            return $lead;
        }

        // Yangi lid yaratish (faqat incoming uchun)
        if ($callLog->direction === 'incoming') {
            return $this->createFromCall($callLog, $phoneNumber, $businessId);
        }

        return null;
    }

    /**
     * Faqat topish (yaratmaslik) — outbound uchun
     */
    public function matchOnly(string $businessId, string $phoneNumber): ?Lead
    {
        $cleanPhone = $this->normalizePhone($phoneNumber);
        $last9 = substr($cleanPhone, -9);

        if (strlen($last9) < 9) return null;

        $lead = $this->findExisting($businessId, $cleanPhone, $last9);

        if ($lead && !$this->verifyMatch($lead, $last9)) {
            return null;
        }

        return $lead;
    }

    /**
     * Mavjud lidlar orasidan qidirish
     */
    private function findExisting(string $businessId, string $cleanPhone, string $last9): ?Lead
    {
        return Lead::where('business_id', $businessId)
            ->where(function ($query) use ($cleanPhone, $last9) {
                $query->where('phone', $cleanPhone)
                    ->orWhere('phone', '+' . $cleanPhone)
                    ->orWhere('phone', '998' . $last9)
                    ->orWhere('phone', '+998' . $last9)
                    ->orWhere('phone', 'LIKE', '%' . $last9);
            })
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Match haqiqatan to'g'rimi tekshirish (false positive oldini olish)
     */
    private function verifyMatch(Lead $lead, string $last9): bool
    {
        $leadClean = preg_replace('/[^0-9]/', '', $lead->phone ?? '');
        $leadLast9 = substr($leadClean, -9);
        return $leadLast9 === $last9;
    }

    /**
     * Qo'ng'iroqdan yangi lid yaratish
     */
    private function createFromCall(CallLog $callLog, string $phoneNumber, string $businessId): ?Lead
    {
        try {
            $source = $this->resolvePhoneSource($businessId);

            $lead = Lead::create([
                'business_id' => $businessId,
                'name' => 'Telefon mijoz',
                'phone' => $phoneNumber,
                'source_id' => $source?->id,
                'status' => 'new',
                'created_via' => 'utel_call',
                'last_contacted_at' => now(),
            ]);

            $callLog->update(['lead_id' => $lead->id]);

            Log::info('UtelLeadMatcher: yangi lid yaratildi', [
                'lead_id' => $lead->id,
                'phone' => $phoneNumber,
            ]);

            return $lead;
        } catch (\Exception $e) {
            Log::error('UtelLeadMatcher: lid yaratishda xato', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Telefon qo'ng'iroq source'ini topish (yo'q bo'lsa yaratish)
     */
    private function resolvePhoneSource(string $businessId): ?LeadSource
    {
        $source = LeadSource::where('business_id', $businessId)
            ->where(function ($q) {
                $q->where('code', 'LIKE', '%phone%')
                    ->orWhere('code', 'LIKE', '%call%')
                    ->orWhere('name', 'LIKE', '%Telefon%');
            })
            ->first();

        if ($source) return $source;

        try {
            return LeadSource::create([
                'business_id' => $businessId,
                'code' => 'phone_call',
                'name' => 'Telefon qo\'ng\'iroq',
                'category' => 'offline',
                'icon' => 'phone',
                'color' => '#10B981',
                'is_active' => true,
                'is_paid' => false,
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Telefon raqamni normalize qilish
     */
    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean) === 9) {
            $clean = '998' . $clean;
        }
        return $clean;
    }
}
