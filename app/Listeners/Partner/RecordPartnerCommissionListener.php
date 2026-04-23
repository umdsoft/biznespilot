<?php

namespace App\Listeners\Partner;

use App\Events\PaymentSuccessEvent;
use App\Services\Partner\PartnerCommissionService;
use Illuminate\Support\Facades\Log;

/**
 * PaymentSuccessEvent → partner commission yaratish (agar biznes referral bo'lsa).
 *
 * Idempotent — duplikat yozuvlar yaratmaydi.
 * Failure-tolerant — commission yozishda xato bo'lsa ham, subscription oqimi
 * buzilmaydi (alohida log chiqaradi).
 */
class RecordPartnerCommissionListener
{
    public function __construct(
        protected PartnerCommissionService $service
    ) {}

    public function handle(PaymentSuccessEvent $event): void
    {
        try {
            $this->service->recordForBillingTransaction($event->getTransaction());
        } catch (\Throwable $e) {
            Log::error('RecordPartnerCommissionListener: failed', [
                'transaction_id' => $event->getTransaction()->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
