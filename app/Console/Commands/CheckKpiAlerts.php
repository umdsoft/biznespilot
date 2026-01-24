<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\KpiAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckKpiAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpi:check-alerts
                            {--business= : Check alerts for a specific business}
                            {--dry-run : Show what would be triggered without creating alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check KPI alert rules and trigger notifications';

    /**
     * Execute the console command.
     */
    public function handle(KpiAlertService $alertService): int
    {
        $this->info('Checking KPI alerts...');

        $businessId = $this->option('business');
        $dryRun = $this->option('dry-run');

        if ($businessId) {
            $businesses = Business::where('id', $businessId)->get();
        } else {
            $businesses = Business::all();
        }

        $totalAlerts = 0;

        foreach ($businesses as $business) {
            $this->line("Checking business: {$business->name}");

            if ($dryRun) {
                $warnings = $alertService->getKpiWarnings($business);
                foreach ($warnings as $warning) {
                    $this->warn("  [{$warning['severity']}] {$warning['message']}");
                }
                continue;
            }

            try {
                $alerts = $alertService->checkAlerts($business);

                if (count($alerts) > 0) {
                    foreach ($alerts as $alert) {
                        $this->info("  Alert triggered: {$alert->title}");
                    }
                    $totalAlerts += count($alerts);
                } else {
                    $this->line('  No alerts triggered');
                }
            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('KPI alert check failed', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("KPI alert check completed. Total alerts triggered: {$totalAlerts}");

        return Command::SUCCESS;
    }
}
