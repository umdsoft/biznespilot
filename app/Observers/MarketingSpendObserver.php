<?php

namespace App\Observers;

use App\Models\BudgetAllocation;
use App\Models\MarketingSpend;
use Illuminate\Support\Facades\Log;

/**
 * MarketingSpendObserver - MarketingSpend va BudgetAllocation sinxronlash
 *
 * Bu observer yangi marketing xarajat qo'shilganda avtomatik ravishda
 * tegishli BudgetAllocation recordni yangilaydi.
 *
 * DRY: Marketing va Finance modullarini bog'laydi
 */
class MarketingSpendObserver
{
    /**
     * Handle the MarketingSpend "created" event.
     */
    public function created(MarketingSpend $spend): void
    {
        $this->syncWithBudgetAllocation($spend, 'add');
    }

    /**
     * Handle the MarketingSpend "updated" event.
     */
    public function updated(MarketingSpend $spend): void
    {
        // Agar summa o'zgargan bo'lsa - farqni hisoblash
        if ($spend->isDirty('amount')) {
            $oldAmount = $spend->getOriginal('amount');
            $newAmount = $spend->amount;
            $diff = $newAmount - $oldAmount;

            if ($diff != 0) {
                $this->syncWithBudgetAllocation($spend, $diff > 0 ? 'add' : 'subtract', abs($diff));
            }
        }
    }

    /**
     * Handle the MarketingSpend "deleted" event.
     */
    public function deleted(MarketingSpend $spend): void
    {
        $this->syncWithBudgetAllocation($spend, 'subtract');
    }

    /**
     * Handle the MarketingSpend "restored" event.
     */
    public function restored(MarketingSpend $spend): void
    {
        $this->syncWithBudgetAllocation($spend, 'add');
    }

    /**
     * BudgetAllocation bilan sinxronlash
     */
    protected function syncWithBudgetAllocation(MarketingSpend $spend, string $action, ?float $customAmount = null): void
    {
        $amount = $customAmount ?? $spend->amount;

        // Channel nomini olish
        $channelName = $spend->channel?->type ?? $spend->channel?->name;

        if (!$channelName) {
            Log::debug('MarketingSpendObserver: No channel found for spend', [
                'spend_id' => $spend->id,
            ]);
            return;
        }

        // Tegishli BudgetAllocation ni topish
        $allocation = $this->findMatchingAllocation($spend, $channelName);

        if (!$allocation) {
            Log::debug('MarketingSpendObserver: No matching allocation found', [
                'spend_id' => $spend->id,
                'channel' => $channelName,
                'date' => $spend->date,
            ]);
            return;
        }

        // Budget yangilash
        if ($action === 'add') {
            $allocation->addSpending($amount, "MarketingSpend #{$spend->id}: {$spend->description}");
        } elseif ($action === 'subtract') {
            // Teskari amal - spent_amount ni kamaytirish
            $newSpent = max(0, $allocation->spent_amount - $amount);
            $allocation->update(['spent_amount' => $newSpent]);
        }

        Log::info('MarketingSpendObserver: Budget allocation synced', [
            'spend_id' => $spend->id,
            'allocation_id' => $allocation->id,
            'action' => $action,
            'amount' => $amount,
            'new_spent' => $allocation->fresh()->spent_amount,
        ]);
    }

    /**
     * Mos BudgetAllocation ni topish
     */
    protected function findMatchingAllocation(MarketingSpend $spend, string $channelName): ?BudgetAllocation
    {
        $date = $spend->date;
        $year = $date->year;
        $month = $date->month;
        $quarter = $date->quarter;

        // 1. Avval monthly allocation qidirish
        $allocation = BudgetAllocation::where('business_id', $spend->business_id)
            ->where('period_type', 'monthly')
            ->where('year', $year)
            ->where('month', $month)
            ->where('channel', $channelName)
            ->where('status', 'active')
            ->first();

        if ($allocation) {
            return $allocation;
        }

        // 2. Quarterly allocation qidirish
        $allocation = BudgetAllocation::where('business_id', $spend->business_id)
            ->where('period_type', 'quarterly')
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->where('channel', $channelName)
            ->where('status', 'active')
            ->first();

        if ($allocation) {
            return $allocation;
        }

        // 3. Annual allocation qidirish
        $allocation = BudgetAllocation::where('business_id', $spend->business_id)
            ->where('period_type', 'annual')
            ->where('year', $year)
            ->where('channel', $channelName)
            ->where('status', 'active')
            ->first();

        return $allocation;
    }
}
