<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Marketing\CrossModuleAttributionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Mavjud ma'lumotlar uchun attribution hisoblash
 *
 * php artisan attribution:batch --business=xxx
 * php artisan attribution:batch --all
 */
class BatchAttributionCommand extends Command
{
    protected $signature = 'attribution:batch
                            {--business= : Bitta biznes ID}
                            {--all : Barcha bizneslar uchun}
                            {--from= : Boshlanish sanasi (Y-m-d)}
                            {--type=all : leads, sales, customers yoki all}';

    protected $description = 'Mavjud lead, sale va customerlar uchun attribution ma\'lumotlarini hisoblash';

    public function handle(CrossModuleAttributionService $service): int
    {
        $businessId = $this->option('business');
        $processAll = $this->option('all');
        $fromDate = $this->option('from') ? Carbon::parse($this->option('from')) : null;
        $type = $this->option('type');

        if (!$businessId && !$processAll) {
            $this->error('--business=xxx yoki --all parametrlaridan birini ko\'rsating');
            return Command::FAILURE;
        }

        $businesses = $processAll
            ? Business::where('status', 'active')->get()
            : Business::where('id', $businessId)->get();

        if ($businesses->isEmpty()) {
            $this->error('Biznes topilmadi');
            return Command::FAILURE;
        }

        $this->info("Attribution hisoblash boshlanmoqda...\n");

        foreach ($businesses as $business) {
            $this->info("Biznes: {$business->name}");
            $this->newLine();

            // Leads
            if ($type === 'all' || $type === 'leads') {
                $this->info('  Lead attribution...');
                $leadsUpdated = $service->batchCalculateLeadCosts($business->id, $fromDate);
                $this->info("    {$leadsUpdated} ta lead yangilandi");
            }

            // Sales
            if ($type === 'all' || $type === 'sales') {
                $this->info('  Sale attribution...');
                $salesUpdated = $service->batchAttributeSales($business->id, $fromDate);
                $this->info("    {$salesUpdated} ta sale yangilandi");
            }

            // Customers
            if ($type === 'all' || $type === 'customers') {
                $this->info('  Customer attribution...');
                $customersUpdated = $service->batchAttributeCustomers($business->id);
                $this->info("    {$customersUpdated} ta customer yangilandi");
            }

            // KPI Daily Revenue yangilash
            if ($type === 'all' || $type === 'sales') {
                $this->info('  KPI Daily Revenue yangilash...');
                $days = $fromDate ? $fromDate->diffInDays(now()) : 30;
                $days = min($days, 90); // Max 90 kun

                for ($i = $days; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $service->updateKpiDailyRevenue($business->id, $date);
                }
                $this->info("    {$days} kunlik revenue yangilandi");
            }

            $this->newLine();
        }

        $this->info('Attribution hisoblash tugadi!');

        return Command::SUCCESS;
    }
}
