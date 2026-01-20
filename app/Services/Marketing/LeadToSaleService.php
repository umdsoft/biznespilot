<?php

namespace App\Services\Marketing;

use App\Models\Lead;
use App\Models\Sale;
use App\Models\Customer;
use App\Events\LeadWon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeadToSaleService - Lead dan Sale yaratish (Marketing Attribution bilan)
 */
class LeadToSaleService
{
    public function __construct(
        private LeadAttributionService $attributionService
    ) {}

    /**
     * Lead dan Sale yaratish.
     */
    public function convertToSale(Lead $lead, array $saleData = []): Sale
    {
        // Tekshirish
        if (!$this->shouldConvert($lead)) {
            throw new \InvalidArgumentException(
                "Lead cannot be converted to sale. Status must be 'won'. Current status: {$lead->status}"
            );
        }

        // Allaqachon convert qilinganmi?
        if ($lead->sale) {
            Log::warning('LeadToSaleService: Lead already converted to sale', [
                'lead_id' => $lead->id,
                'sale_id' => $lead->sale->id,
            ]);
            return $lead->sale;
        }

        return DB::transaction(function () use ($lead, $saleData) {
            // Customer yaratish yoki topish
            $customer = $this->getOrCreateCustomer($lead);

            // Attribution ma'lumotlarini yig'ish
            $attributionData = $this->attributionService->buildFullAttribution($lead);

            // Sale yaratish
            $sale = Sale::create([
                'business_id' => $lead->business_id,
                'customer_id' => $customer->id,
                'lead_id' => $lead->id,
                'campaign_id' => $lead->campaign_id,
                'marketing_channel_id' => $lead->marketing_channel_id,
                'attribution_data' => $attributionData,
                'amount' => $saleData['amount'] ?? $lead->estimated_value ?? 0,
                'cost' => $saleData['cost'] ?? null,
                'profit' => $saleData['profit'] ?? null,
                'currency' => $saleData['currency'] ?? 'UZS',
                'status' => $saleData['status'] ?? 'completed',
                'notes' => $saleData['notes'] ?? "Converted from lead: {$lead->name}",
                'sale_date' => $saleData['sale_date'] ?? now()->toDateString(),
                'closed_at' => $saleData['closed_at'] ?? now(),
                'closed_by' => $saleData['closed_by'] ?? $lead->assigned_to ?? auth()->id(),
            ]);

            // Lead ni converted sifatida belgilash
            $lead->converted_at = now();
            $lead->saveQuietly();

            // Event dispatch
            event(new LeadWon(
                lead: $lead,
                sale: $sale,
                campaignId: $lead->campaign_id,
                channelId: $lead->marketing_channel_id,
                revenue: (float) $sale->amount
            ));

            Log::info('LeadToSaleService: Lead converted to sale', [
                'lead_id' => $lead->id,
                'sale_id' => $sale->id,
                'customer_id' => $customer->id,
                'amount' => $sale->amount,
                'campaign_id' => $sale->campaign_id,
                'channel_id' => $sale->marketing_channel_id,
            ]);

            return $sale;
        });
    }

    /**
     * Convert qilish mumkinmi?
     */
    public function shouldConvert(Lead $lead): bool
    {
        return $lead->status === 'won' || $lead->isWon();
    }

    /**
     * Customer yaratish yoki mavjudini topish.
     */
    private function getOrCreateCustomer(Lead $lead): Customer
    {
        // Avval mavjud customer ni tekshirish (lead orqali)
        $existingCustomer = Customer::where('lead_id', $lead->id)->first();
        if ($existingCustomer) {
            return $existingCustomer;
        }

        // Email yoki phone bo'yicha topish
        $existing = Customer::where('business_id', $lead->business_id)
            ->where(function ($q) use ($lead) {
                if ($lead->email) {
                    $q->where('email', $lead->email);
                }
                if ($lead->phone) {
                    $q->orWhere('phone', $lead->phone);
                }
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        // Yangi customer yaratish
        $customer = Customer::create([
            'business_id' => $lead->business_id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'lead_id' => $lead->id,
            'source' => 'lead_conversion',
            'first_purchase_at' => now(),
        ]);

        Log::info('LeadToSaleService: Customer created from lead', [
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
        ]);

        return $customer;
    }

    /**
     * Batch convert (ko'p leadlarni bir vaqtda).
     */
    public function batchConvert(array $leadIds, array $saleData = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($leadIds as $leadId) {
            try {
                $lead = Lead::findOrFail($leadId);
                $sale = $this->convertToSale($lead, $saleData);
                $results['success'][] = [
                    'lead_id' => $leadId,
                    'sale_id' => $sale->id,
                    'amount' => $sale->amount,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'lead_id' => $leadId,
                    'error' => $e->getMessage(),
                ];
                Log::error('LeadToSaleService: Batch convert failed', [
                    'lead_id' => $leadId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Sale dan lead attribution ma'lumotlarini olish.
     */
    public function getAttributionFromSale(Sale $sale): array
    {
        if ($sale->attribution_data) {
            return $sale->attribution_data;
        }

        if (!$sale->lead) {
            return [];
        }

        return $this->attributionService->buildFullAttribution($sale->lead);
    }

    /**
     * Check if lead can be converted.
     */
    public function canConvert(Lead $lead): array
    {
        $issues = [];

        if ($lead->status !== 'won') {
            $issues[] = "Lead status must be 'won', current: {$lead->status}";
        }

        if ($lead->sale) {
            $issues[] = "Lead already converted to sale #{$lead->sale->id}";
        }

        if (!$lead->estimated_value && !isset($saleData['amount'])) {
            $issues[] = 'Lead has no estimated value and no amount provided';
        }

        return [
            'can_convert' => empty($issues),
            'issues' => $issues,
        ];
    }
}
