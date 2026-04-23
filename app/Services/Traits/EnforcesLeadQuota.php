<?php

declare(strict_types=1);

namespace App\Services\Traits;

use App\Exceptions\NoActiveSubscriptionException;
use App\Exceptions\QuotaExceededException;
use App\Models\Business;
use App\Models\Lead;
use App\Services\SubscriptionGate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * EnforcesLeadQuota — Webhook / telephony / chatbot servislari uchun
 * markazlashgan `monthly_leads` quota tekshiruvi.
 *
 * Bu trait'ni Lead::create chaqiradigan servislarga qo'shing va Lead::create
 * o'rniga $this->createLeadWithQuotaCheck(...) ni ishlating. Shunda:
 * 1. Tarif limiti oshgan bo'lsa — Lead yaratilmaydi, null qaytariladi, log yoziladi.
 * 2. Obunasi yo'q bo'lsa — Lead yaratilmaydi.
 * 3. Muammo Telegram/PBX/Instagram botga ma'lumot yo'qotilmasligi uchun graceful,
 *    exception otmaydi — faqat null qaytariladi. Chaqiruvchi kerakli reaksiyani
 *    qaradi (masalan fallback javob yuborish).
 *
 * Nima uchun trait? Lead::create 16 joyda chaqiriladi. Har biriga alohida
 * SubscriptionGate chaqiruvini qo'shish o'rniga — shu trait orqali DRY.
 */
trait EnforcesLeadQuota
{
    /**
     * Quota tekshirilgan holda Lead yaratadi.
     *
     * @param array $payload Lead::create uchun data (business_id majburiy)
     * @return Lead|null Yaratildi: Lead, limit tugagan: null, business yo'q: null
     */
    protected function createLeadWithQuotaCheck(array $payload): ?Lead
    {
        $businessId = $payload['business_id'] ?? null;

        if (!$businessId) {
            Log::warning('EnforcesLeadQuota: business_id missing in payload', [
                'source' => static::class,
            ]);
            return null;
        }

        $business = Business::find($businessId);
        if (!$business) {
            Log::warning('EnforcesLeadQuota: business not found', [
                'business_id' => $businessId,
                'source' => static::class,
            ]);
            return null;
        }

        // PERFORMANCE / ATOMICITY: Concurrent webhook'lar parallel kelganida
        // monthly_leads limitidan chiqib ketmaslik uchun atomic lock bilan
        // quota tekshiruvi va yaratish bitta tranzaksiyaga o'xshaydi.
        // Cache driver `array` bo'lsa (test) — lock hech narsa qilmaydi, shunchaki
        // passthrough.
        $lock = Cache::lock("lead_create_lock:{$businessId}", 5);
        try {
            if (!$lock->block(3)) {
                Log::warning('EnforcesLeadQuota: could not acquire lock (contended)', [
                    'business_id' => $businessId,
                    'source' => static::class,
                ]);
                return null;
            }
        } catch (\Throwable $e) {
            // Lock olinmadi (timeout) — xavfsiz tomon: lead yaratmaymiz
            Log::warning('EnforcesLeadQuota: lock timeout', [
                'business_id' => $businessId,
                'source' => static::class,
            ]);
            return null;
        }

        try {
            // Quota tekshiruvi — obunasi yo'q yoki limit to'lgan bo'lsa block qiladi
            if (!$this->canCreateLead($business)) {
                Log::info('EnforcesLeadQuota: lead skipped due to quota', [
                    'business_id' => $businessId,
                    'source' => static::class,
                    'lead_source' => $payload['source'] ?? null,
                ]);
                return null;
            }

            return Lead::create($payload);
        } finally {
            // Lock doim release qilinadi
            optional($lock)->release();
        }
    }

    /**
     * Business uchun Lead qo'shish mumkinligini tekshirish (exception'siz).
     */
    protected function canCreateLead(Business $business): bool
    {
        try {
            /** @var SubscriptionGate $gate */
            $gate = app(SubscriptionGate::class);
            return $gate->canAdd($business, 'monthly_leads');
        } catch (NoActiveSubscriptionException $e) {
            // Obunasi yo'q — lead yaratishga ruxsat yo'q
            return false;
        } catch (QuotaExceededException $e) {
            return false;
        } catch (\Throwable $e) {
            // Boshqa kutilmagan xato — avval xavfsiz bo'lgan:
            // trial tugagandan keyin ham lead'lar kelishiga ruxsat bermaslik afzal
            Log::error('EnforcesLeadQuota: unexpected error in canAdd', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'source' => static::class,
            ]);
            return false;
        }
    }
}
