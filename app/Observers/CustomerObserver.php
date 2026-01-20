<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\Marketing\CrossModuleAttributionService;
use Illuminate\Support\Facades\Log;

/**
 * Customer Observer
 *
 * Customer yaratilganda acquisition source
 * va churn tracking ma'lumotlarini qo'shadi.
 */
class CustomerObserver
{
    /**
     * Customer yaratilganda
     */
    public function creating(Customer $customer): void
    {
        // Acquisition attribution qo'shish
        $this->addAcquisitionAttribution($customer);
    }

    /**
     * Customer yaratilgandan keyin
     */
    public function created(Customer $customer): void
    {
        Log::info('CustomerObserver: Customer created', [
            'customer_id' => $customer->id,
            'business_id' => $customer->business_id,
            'acquisition_source' => $customer->first_acquisition_source,
            'source_type' => $customer->first_acquisition_source_type,
            'lead_id' => $customer->lead_id,
        ]);
    }

    /**
     * Customer yangilanganda
     */
    public function updated(Customer $customer): void
    {
        // Last purchase yangilangan bo'lsa, churn risk qayta hisoblash
        if ($customer->isDirty('last_purchase_at')) {
            $this->updateChurnRisk($customer);
        }

        // Orders count o'zgargan bo'lsa
        if ($customer->isDirty('orders_count')) {
            $this->updatePurchaseFrequency($customer);
            $this->updateLifetimeValue($customer);
        }

        // Total spent o'zgargan bo'lsa
        if ($customer->isDirty('total_spent')) {
            $this->updateLifetimeValue($customer);
        }
    }

    /**
     * Acquisition attribution qo'shish
     */
    protected function addAcquisitionAttribution(Customer $customer): void
    {
        // Agar attribution allaqachon bor bo'lsa, skip
        if ($customer->first_acquisition_source) {
            return;
        }

        try {
            $attributionService = app(CrossModuleAttributionService::class);
            $attribution = $attributionService->attributeCustomer($customer);

            $customer->first_acquisition_source = $attribution['first_acquisition_source'];
            $customer->first_acquisition_source_type = $attribution['first_acquisition_source_type'];
            $customer->first_acquisition_channel_id = $attribution['first_acquisition_channel_id'];
            $customer->first_campaign_id = $attribution['first_campaign_id'];
            $customer->total_acquisition_cost = $attribution['total_acquisition_cost'];

            // Initial churn risk (yangi customer uchun low)
            $customer->churn_risk_score = 0;
            $customer->churn_risk_level = 'low';

        } catch (\Exception $e) {
            Log::error('CustomerObserver: Failed to add acquisition attribution', [
                'customer_id' => $customer->id ?? 'new',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Churn risk yangilash
     */
    protected function updateChurnRisk(Customer $customer): void
    {
        try {
            $attributionService = app(CrossModuleAttributionService::class);
            $churn = $attributionService->calculateChurnRisk($customer);

            $customer->updateQuietly([
                'churn_risk_score' => $churn['score'],
                'churn_risk_level' => $churn['level'],
                'days_since_last_purchase' => $churn['days_since_last_purchase'],
            ]);

        } catch (\Exception $e) {
            Log::error('CustomerObserver: Failed to update churn risk', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Purchase frequency yangilash
     */
    protected function updatePurchaseFrequency(Customer $customer): void
    {
        if ($customer->orders_count < 2 || !$customer->first_purchase_at || !$customer->last_purchase_at) {
            return;
        }

        try {
            $daysBetween = $customer->first_purchase_at->diffInDays($customer->last_purchase_at);
            $frequency = $daysBetween / ($customer->orders_count - 1);

            $customer->updateQuietly([
                'purchase_frequency_days' => round($frequency),
            ]);

        } catch (\Exception $e) {
            Log::error('CustomerObserver: Failed to update purchase frequency', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lifetime value yangilash
     */
    protected function updateLifetimeValue(Customer $customer): void
    {
        try {
            $attributionService = app(CrossModuleAttributionService::class);
            $ltv = $attributionService->calculateCustomerLtv($customer);

            $customer->updateQuietly([
                'lifetime_value' => $ltv,
            ]);

        } catch (\Exception $e) {
            Log::error('CustomerObserver: Failed to update lifetime value', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
