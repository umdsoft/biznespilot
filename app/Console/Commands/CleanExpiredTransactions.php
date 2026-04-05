<?php

namespace App\Console\Commands;

use App\Models\Billing\BillingTransaction;
use Illuminate\Console\Command;

class CleanExpiredTransactions extends Command
{
    protected $signature = 'billing:clean-expired';

    protected $description = 'Muddati o\'tgan to\'lov tranzaksiyalarni cancelled statusiga o\'tkazish';

    public function handle(): int
    {
        $count = BillingTransaction::where('status', BillingTransaction::STATUS_CREATED)
            ->where('expires_at', '<', now())
            ->update(['status' => BillingTransaction::STATUS_CANCELLED]);

        if ($count > 0) {
            $this->info("Cancelled {$count} expired transactions.");
        } else {
            $this->info('No expired transactions found.');
        }

        return self::SUCCESS;
    }
}
