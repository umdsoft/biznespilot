<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\PbxAccount;
use App\Models\SipuniAccount;
use App\Services\OnlinePbxService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPbxCalls extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pbx:sync-calls
                            {--business= : Specific business ID to sync}
                            {--days=1 : Number of days to sync back}
                            {--link-orphans : Also link orphan calls to leads}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically sync call history from all connected PBX/VoIP services';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting PBX calls synchronization...');

        $businessId = $this->option('business');
        $days = (int) $this->option('days');
        $linkOrphans = $this->option('link-orphans');

        $dateFrom = Carbon::now()->subDays($days);

        // Get businesses to sync
        $businesses = $businessId
            ? Business::where('id', $businessId)->get()
            : Business::where('status', 'active')->get();

        $totalSynced = 0;
        $totalLinked = 0;
        $errors = 0;

        foreach ($businesses as $business) {
            $this->line("Processing business: {$business->name} ({$business->id})");

            try {
                // Sync from OnlinePBX
                $result = $this->syncOnlinePbx($business, $dateFrom);
                $totalSynced += $result['synced'];

                // Sync from SipUni (if implemented)
                $sipuniResult = $this->syncSipuni($business, $dateFrom);
                $totalSynced += $sipuniResult['synced'];

                // Link orphan calls to leads
                if ($linkOrphans || true) { // Always link orphans
                    $linked = $this->linkOrphanCalls($business->id);
                    $totalLinked += $linked;
                }

                // Fix stale "ringing" calls (older than 5 minutes with 0 duration = missed)
                $fixedStale = $this->fixStaleRingingCalls($business->id);

                $this->info("  - Synced: {$result['synced']} calls, Linked: {$linked} orphans, Fixed stale: {$fixedStale}");

            } catch (\Exception $e) {
                $errors++;
                $this->error("  - Error: {$e->getMessage()}");
                Log::error('PBX sync error for business', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Sync completed!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Businesses processed', $businesses->count()],
                ['Total calls synced', $totalSynced],
                ['Orphan calls linked', $totalLinked],
                ['Errors', $errors],
            ]
        );

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Sync calls from OnlinePBX for a business
     */
    protected function syncOnlinePbx(Business $business, Carbon $dateFrom): array
    {
        $pbxAccount = PbxAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (! $pbxAccount) {
            return ['synced' => 0, 'error' => null];
        }

        try {
            $service = app(OnlinePbxService::class);
            $service->setAccount($pbxAccount);

            $result = $service->syncCallHistory($dateFrom);

            return [
                'synced' => $result['synced'] ?? 0,
                'error' => $result['error'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('OnlinePBX sync failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return ['synced' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync calls from SipUni for a business (placeholder for future implementation)
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function syncSipuni(Business $business, Carbon $dateFrom): array
    {
        $sipuniAccount = SipuniAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (! $sipuniAccount) {
            return ['synced' => 0, 'error' => null];
        }

        // TODO: Implement SipUni sync when service is ready
        // When implemented, use $dateFrom for filtering:
        // $service = app(SipuniService::class);
        // $service->setAccount($sipuniAccount);
        // return $service->syncCallHistory($dateFrom);
        unset($dateFrom); // Placeholder until implementation

        return ['synced' => 0, 'error' => null];
    }

    /**
     * Link orphan call logs to leads based on phone number
     */
    protected function linkOrphanCalls(string $businessId): int
    {
        $linked = 0;

        // Get orphan calls (calls without lead_id)
        $orphanCalls = CallLog::where('business_id', $businessId)
            ->whereNull('lead_id')
            ->get();

        foreach ($orphanCalls as $call) {
            try {
                // Determine phone number based on direction
                $phoneNumber = $call->direction === CallLog::DIRECTION_INBOUND
                    ? $call->from_number
                    : $call->to_number;

                if (empty($phoneNumber)) {
                    continue;
                }

                // Get last 9 digits for matching
                $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
                $last9 = substr($cleanPhone, -9);

                if (strlen($last9) < 7) {
                    continue; // Skip internal numbers
                }

                // Find lead by phone number
                $lead = Lead::where('business_id', $businessId)
                    ->where(function ($query) use ($last9, $phoneNumber, $cleanPhone) {
                        $query->where('phone', 'like', '%'.$last9)
                            ->orWhere('phone', $phoneNumber)
                            ->orWhere('phone', $cleanPhone)
                            ->orWhere('phone', '998'.$last9);
                    })
                    ->first();

                if ($lead) {
                    $call->update(['lead_id' => $lead->id]);
                    $linked++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to link orphan call', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $linked;
    }

    /**
     * Fix stale "ringing" calls that are older than 5 minutes
     * Calls with duration > 0 are marked as completed, others as missed
     */
    protected function fixStaleRingingCalls(string $businessId): int
    {
        $fixed = 0;

        // First, fix calls with duration > 0 - these were actually answered
        $fixed += CallLog::where('business_id', $businessId)
            ->where('status', CallLog::STATUS_RINGING)
            ->where('duration', '>', 0)
            ->update([
                'status' => CallLog::STATUS_COMPLETED,
                'ended_at' => Carbon::now(),
            ]);

        // Then, fix truly stale ringing calls (older than 5 minutes with 0 duration)
        $fixed += CallLog::where('business_id', $businessId)
            ->where('status', CallLog::STATUS_RINGING)
            ->where('duration', 0)
            ->where('created_at', '<', Carbon::now()->subMinutes(5))
            ->update([
                'status' => CallLog::STATUS_MISSED,
                'ended_at' => Carbon::now(),
            ]);

        return $fixed;
    }
}
