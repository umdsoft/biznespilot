<?php

namespace App\Services\Store;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Store\StoreOrder;
use App\Models\Store\StoreCustomer;
use App\Services\Marketing\LeadToSaleService;
use Illuminate\Support\Facades\Log;

class StoreOrderLeadService
{
    public function __construct(
        private LeadToSaleService $leadToSaleService
    ) {}

    /**
     * Yangi buyurtma tushganda Lead yaratish yoki yangilash
     */
    public function createLeadFromOrder(StoreOrder $order): ?Lead
    {
        try {
            $store = $order->store;
            if (!$store || !$store->business_id) {
                return null;
            }

            $customer = $order->customer;
            if (!$customer) {
                return null;
            }

            // Telefon raqami bo'lmasa, lead yaratish mumkin emas
            $phone = $customer->phone;
            if (!$phone) {
                Log::info('StoreOrderLeadService: Customer has no phone, skipping lead creation', [
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                ]);
                return null;
            }

            // Mavjud leadni tekshirish (shu mijoz, shu biznes, terminal bo'lmagan)
            $existingLead = Lead::withoutGlobalScope('business')
                ->where('business_id', $store->business_id)
                ->where('phone', $phone)
                ->whereNotIn('status', Lead::TERMINAL_STATUSES)
                ->latest()
                ->first();

            if ($existingLead) {
                return $this->updateLeadWithOrder($existingLead, $order);
            }

            return $this->createNewLead($store, $customer, $order);
        } catch (\Exception $e) {
            Log::error('StoreOrderLeadService: Failed to create lead from order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Yangi Lead yaratish
     */
    protected function createNewLead($store, StoreCustomer $customer, StoreOrder $order): Lead
    {
        $customerName = $customer->getDisplayName();

        // LeadSource ni topish yoki yaratish (telegram_store uchun)
        $sourceId = $this->getOrCreateLeadSourceId($store->business_id);

        $orderTotal = (string) $order->total;

        return Lead::withoutGlobalScope('business')->create([
            'business_id' => $store->business_id,
            'source_id' => $sourceId,
            'name' => $customerName,
            'phone' => $customer->phone ?? '',
            'status' => Lead::STATUS_NEW,
            'estimated_value' => $order->total,
            'notes' => "Telegram do'kon buyurtmasi #{$order->order_number}. Summa: {$orderTotal} so'm.",
            'data' => [
                'source_type' => 'telegram_store',
                'store_id' => $store->id,
                'store_name' => $store->name,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'telegram_user_id' => $customer->telegram_user_id,
            ],
        ]);
    }

    /**
     * Mavjud leadni buyurtma bilan yangilash
     */
    protected function updateLeadWithOrder(Lead $lead, StoreOrder $order): Lead
    {
        $currentValue = (float) ($lead->estimated_value ?? 0);
        $orderTotal = (string) $order->total;

        $lead->update([
            'estimated_value' => $currentValue + (float) $order->total,
            'notes' => ($lead->notes ? $lead->notes . "\n" : '') .
                "Yangi buyurtma #{$order->order_number}. Summa: {$orderTotal} so'm.",
        ]);

        return $lead;
    }

    /**
     * Buyurtma holati o'zgarganda Lead statusini yangilash
     */
    public function syncLeadStatusFromOrder(StoreOrder $order): void
    {
        try {
            $lead = $this->findLeadForOrder($order);
            if (!$lead) {
                return;
            }

            $newStatus = $this->mapOrderStatusToLeadStatus($order->status);
            if (!$newStatus || $newStatus === $lead->status) {
                return;
            }

            // Terminal statusga o'tishdan oldin tekshirish
            if (in_array($lead->status, Lead::TERMINAL_STATUSES)) {
                return;
            }

            $lead->update(['status' => $newStatus]);

            // Agar 'won' bo'lsa, Sale yaratish
            if ($newStatus === Lead::STATUS_WON) {
                $this->convertLeadToSale($lead, $order);
            }
        } catch (\Exception $e) {
            Log::error('StoreOrderLeadService: Failed to sync lead status from order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Buyurtma statusini Lead statusiga mapping
     */
    protected function mapOrderStatusToLeadStatus(string $orderStatus): ?string
    {
        return match ($orderStatus) {
            StoreOrder::STATUS_PENDING => Lead::STATUS_NEW,
            StoreOrder::STATUS_CONFIRMED => Lead::STATUS_CONTACTED,
            StoreOrder::STATUS_PROCESSING => Lead::STATUS_QUALIFIED,
            StoreOrder::STATUS_SHIPPED => Lead::STATUS_PROPOSAL,
            StoreOrder::STATUS_DELIVERED => Lead::STATUS_WON,
            StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED => Lead::STATUS_LOST,
            default => null,
        };
    }

    /**
     * Lead ni Sale ga convert qilish
     */
    protected function convertLeadToSale(Lead $lead, StoreOrder $order): void
    {
        try {
            $this->leadToSaleService->convertToSale($lead, [
                'amount' => $order->total,
                'currency' => 'UZS',
                'notes' => "Telegram do'kon buyurtmasi #{$order->order_number}",
            ]);
        } catch (\Exception $e) {
            Log::error('StoreOrderLeadService: Failed to convert lead to sale', [
                'lead_id' => $lead->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Buyurtma uchun tegishli Leadni topish
     */
    protected function findLeadForOrder(StoreOrder $order): ?Lead
    {
        $store = $order->store;
        if (!$store) {
            return null;
        }

        // data da order_id orqali qidirish
        $lead = Lead::withoutGlobalScope('business')
            ->where('business_id', $store->business_id)
            ->where('data->order_id', $order->id)
            ->first();

        if ($lead) {
            return $lead;
        }

        // Mijoz telefoni orqali qidirish (terminal bo'lmagan)
        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return null;
        }

        return Lead::withoutGlobalScope('business')
            ->where('business_id', $store->business_id)
            ->where('phone', $customer->phone)
            ->whereNotIn('status', Lead::TERMINAL_STATUSES)
            ->latest()
            ->first();
    }

    /**
     * Telegram store uchun LeadSource ID ni olish yoki yaratish
     */
    protected function getOrCreateLeadSourceId(string $businessId): ?string
    {
        $source = LeadSource::withoutGlobalScope('business')
            ->where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)
                    ->orWhereNull('business_id');
            })
            ->where('code', 'telegram_store')
            ->first();

        if ($source) {
            return $source->id;
        }

        // Yangi source yaratish
        try {
            $source = LeadSource::create([
                'business_id' => $businessId,
                'code' => 'telegram_store',
                'name' => 'Telegram Do\'kon',
                'category' => 'digital',
                'icon' => 'shopping-bag',
                'color' => '#0088cc',
                'is_paid' => false,
                'is_trackable' => true,
                'is_active' => true,
            ]);

            return $source->id;
        } catch (\Exception $e) {
            Log::warning('StoreOrderLeadService: Could not create LeadSource', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
