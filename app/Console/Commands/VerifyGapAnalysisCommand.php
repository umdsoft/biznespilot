<?php

namespace App\Console\Commands;

use App\Events\LeadStageChanged;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LostOpportunity;
use App\Models\MarketingChannel;
use App\Models\Order;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Sales\LostOpportunityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gap Analysis Verification Command
 *
 * Foydalanish: php artisan verify:gap-analysis
 *
 * Bu command "Black Box" konsepsiyasi implementatsiyasini tekshiradi.
 */
class VerifyGapAnalysisCommand extends Command
{
    protected $signature = 'verify:gap-analysis {--cleanup : Test ma\'lumotlarini o\'chirish}';

    protected $description = 'Verify Gap Analysis implementation (LostOpportunity & Order Attribution)';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║       BiznesPilot - Gap Analysis Verification                ║');
        $this->info('║       "Black Box" Implementation Test                        ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Cleanup option
        if ($this->option('cleanup')) {
            return $this->cleanup();
        }

        try {
            DB::beginTransaction();

            // Get or create test business
            $business = $this->getOrCreateTestBusiness();
            $user = $this->getOrCreateTestUser($business);
            $campaign = $this->getOrCreateCampaign($business);
            $channel = $this->getOrCreateChannel($business);
            $lostStage = $this->getOrCreateLostStage($business);

            $this->newLine();
            $this->info('📋 Test Setup Complete');
            $this->table(['Entity', 'ID'], [
                ['Business', $business->id],
                ['User', $user->id],
                ['Campaign', $campaign->id],
                ['Channel', $channel->id],
            ]);

            // ============================================
            // SCENARIO 1: Lost Money Check
            // ============================================
            $this->newLine();
            $this->warn('══════════════════════════════════════════════════════════════');
            $this->warn('  SCENARIO 1: The "Lost Money" Check');
            $this->warn('══════════════════════════════════════════════════════════════');

            $lead = $this->createTestLead($business, $user, $campaign, $channel);
            $this->info("✓ Lead created: {$lead->id}");
            $this->info("  Estimated Value: " . number_format($lead->estimated_value) . " UZS");

            // Simulate lost
            $lead->update([
                'status' => 'lost',
                'lost_reason' => 'competitor',
                'lost_reason_details' => 'Mijoz raqobatchi kompaniyasini tanladi',
            ]);

            // Call service directly (simulating what listener does)
            $lostOpportunityService = app(LostOpportunityService::class);
            $lostOpportunity = $lostOpportunityService->trackLostLead(
                lead: $lead,
                lostReason: $lead->lost_reason,
                lostReasonDetails: $lead->lost_reason_details,
                lostBy: $user,
                lostToCompetitor: 'Raqobatchi A'
            );

            $this->info("✓ LostOpportunity created: {$lostOpportunity->id}");

            // Verify
            $this->newLine();
            $this->info('📊 VERIFICATION RESULTS:');
            $this->table(
                ['Field', 'Expected', 'Actual', 'Status'],
                [
                    ['estimated_value', '5,000,000', number_format($lostOpportunity->estimated_value), $lostOpportunity->estimated_value == 5000000 ? '✅' : '❌'],
                    ['assigned_to', $user->id, $lostOpportunity->assigned_to, $lostOpportunity->assigned_to == $user->id ? '✅' : '❌'],
                    ['lost_reason', 'competitor', $lostOpportunity->lost_reason, $lostOpportunity->lost_reason == 'competitor' ? '✅' : '❌'],
                    ['campaign_id', $campaign->id, $lostOpportunity->campaign_id, $lostOpportunity->campaign_id == $campaign->id ? '✅' : '❌'],
                    ['channel_id', $channel->id, $lostOpportunity->marketing_channel_id, $lostOpportunity->marketing_channel_id == $channel->id ? '✅' : '❌'],
                    ['utm_source', 'instagram', $lostOpportunity->utm_source, $lostOpportunity->utm_source == 'instagram' ? '✅' : '❌'],
                    ['utm_medium', 'cpc', $lostOpportunity->utm_medium, $lostOpportunity->utm_medium == 'cpc' ? '✅' : '❌'],
                    ['lost_to_competitor', 'Raqobatchi A', $lostOpportunity->lost_to_competitor, $lostOpportunity->lost_to_competitor == 'Raqobatchi A' ? '✅' : '❌'],
                ]
            );

            $scenario1Pass = $lostOpportunity->estimated_value == 5000000
                && $lostOpportunity->campaign_id == $campaign->id
                && $lostOpportunity->utm_source == 'instagram';

            if ($scenario1Pass) {
                $this->info('🎉 SCENARIO 1: PASSED - Lost money is being tracked correctly!');
            } else {
                $this->error('❌ SCENARIO 1: FAILED');
            }

            // ============================================
            // SCENARIO 2: Marketing Truth Check
            // ============================================
            $this->newLine();
            $this->warn('══════════════════════════════════════════════════════════════');
            $this->warn('  SCENARIO 2: The "Marketing Truth" Check');
            $this->warn('══════════════════════════════════════════════════════════════');

            // Create a new lead (won)
            $lead2 = Lead::create([
                'business_id' => $business->id,
                'assigned_to' => $user->id,
                'name' => 'Test Customer Lead',
                'phone' => '+998901234568',
                'estimated_value' => 3000000,
                'status' => 'won',
                'campaign_id' => $campaign->id,
                'marketing_channel_id' => $channel->id,
                'utm_source' => 'instagram',
                'utm_medium' => 'cpc',
                'utm_campaign' => 'summer_promo',
                'utm_content' => 'carousel_ad',
                'utm_term' => 'sale',
            ]);
            $this->info("✓ Lead (won) created: {$lead2->id}");

            // Create Customer
            $customer = Customer::create([
                'business_id' => $business->id,
                'lead_id' => $lead2->id,
                'name' => $lead2->name,
                'phone' => $lead2->phone,
                'first_acquisition_channel_id' => $lead2->marketing_channel_id,
                'first_campaign_id' => $lead2->campaign_id,
                'first_acquisition_source' => 'instagram',
                'first_acquisition_source_type' => 'digital',
            ]);
            $this->info("✓ Customer created: {$customer->id}");

            // Create Order WITHOUT attribution (should be auto-populated)
            $order = Order::create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'order_number' => 'ORD-TEST-' . time(),
                'total_amount' => 1500000,
                'currency' => 'UZS',
                'status' => 'pending',
                'payment_status' => 'pending',
                'ordered_at' => now(),
                // NOTE: NO attribution fields set here!
            ]);
            $order->refresh();
            $this->info("✓ Order created: {$order->id} ({$order->order_number})");

            // Verify
            $this->newLine();
            $this->info('📊 VERIFICATION RESULTS:');
            $this->table(
                ['Field', 'Expected', 'Actual', 'Status'],
                [
                    ['campaign_id', $campaign->id, $order->campaign_id ?? 'NULL', $order->campaign_id == $campaign->id ? '✅' : '❌'],
                    ['channel_id', $channel->id, $order->marketing_channel_id ?? 'NULL', $order->marketing_channel_id == $channel->id ? '✅' : '❌'],
                    ['lead_id', $lead2->id, $order->lead_id ?? 'NULL', $order->lead_id == $lead2->id ? '✅' : '❌'],
                    ['utm_source', 'instagram', $order->utm_source ?? 'NULL', $order->utm_source == 'instagram' ? '✅' : '❌'],
                    ['utm_medium', 'cpc', $order->utm_medium ?? 'NULL', $order->utm_medium == 'cpc' ? '✅' : '❌'],
                    ['utm_campaign', 'summer_promo', $order->utm_campaign ?? 'NULL', $order->utm_campaign == 'summer_promo' ? '✅' : '❌'],
                    ['hasAttribution()', 'true', $order->hasAttribution() ? 'true' : 'false', $order->hasAttribution() ? '✅' : '❌'],
                ]
            );

            $scenario2Pass = $order->campaign_id == $campaign->id
                && $order->marketing_channel_id == $channel->id
                && $order->utm_source == 'instagram';

            if ($scenario2Pass) {
                $this->info('🎉 SCENARIO 2: PASSED - Order inherits marketing attribution correctly!');
            } else {
                $this->error('❌ SCENARIO 2: FAILED');
            }

            // ============================================
            // Final Summary
            // ============================================
            $this->newLine();
            $this->warn('══════════════════════════════════════════════════════════════');
            $this->warn('  FINAL SUMMARY');
            $this->warn('══════════════════════════════════════════════════════════════');

            if ($scenario1Pass && $scenario2Pass) {
                $this->info('');
                $this->info('  ✅ ALL TESTS PASSED!');
                $this->info('');
                $this->info('  The "Black Box" is recording:');
                $this->info('  • Lost money: ' . number_format($lostOpportunity->estimated_value) . ' UZS tracked');
                $this->info('  • Marketing source: Instagram → Campaign → Order chain works');
                $this->info('');
            } else {
                $this->error('  ❌ SOME TESTS FAILED');
            }

            // Rollback test data
            DB::rollBack();
            $this->info('🧹 Test data rolled back (no permanent changes)');

            return $scenario1Pass && $scenario2Pass ? self::SUCCESS : self::FAILURE;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    private function getOrCreateTestBusiness(): Business
    {
        return Business::first() ?? Business::create([
            'name' => 'Test Business',
            'slug' => 'test-business-' . time(),
        ]);
    }

    private function getOrCreateTestUser(Business $business): User
    {
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        return $user;
    }

    private function getOrCreateCampaign(Business $business): Campaign
    {
        return Campaign::where('business_id', $business->id)->first()
            ?? Campaign::create([
                'business_id' => $business->id,
                'name' => 'promo_summer',
                'type' => 'promotion',
                'status' => 'active',
            ]);
    }

    private function getOrCreateChannel(Business $business): MarketingChannel
    {
        return MarketingChannel::where('business_id', $business->id)->first()
            ?? MarketingChannel::create([
                'business_id' => $business->id,
                'name' => 'Instagram Ads',
                'type' => 'paid_social',
            ]);
    }

    private function getOrCreateLostStage(Business $business): PipelineStage
    {
        return PipelineStage::where('business_id', $business->id)
            ->where('is_lost', true)
            ->first()
            ?? PipelineStage::create([
                'business_id' => $business->id,
                'name' => 'Lost',
                'slug' => 'lost',
                'is_lost' => true,
                'order' => 99,
            ]);
    }

    private function createTestLead(Business $business, User $user, Campaign $campaign, MarketingChannel $channel): Lead
    {
        return Lead::create([
            'business_id' => $business->id,
            'assigned_to' => $user->id,
            'name' => 'Test Lead - Gap Analysis',
            'phone' => '+998901234567',
            'estimated_value' => 5000000, // 5 million UZS
            'status' => 'negotiation',
            'campaign_id' => $campaign->id,
            'marketing_channel_id' => $channel->id,
            'utm_source' => 'instagram',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'summer_promo',
        ]);
    }

    private function cleanup(): int
    {
        $this->info('Cleaning up test data...');

        LostOpportunity::where('lost_reason_details', 'LIKE', '%test%')->delete();
        Lead::where('name', 'LIKE', '%Test%Gap Analysis%')->forceDelete();
        Order::where('order_number', 'LIKE', 'ORD-TEST-%')->forceDelete();
        Customer::where('name', 'LIKE', '%Test Customer%')->forceDelete();

        $this->info('✓ Test data cleaned up');
        return self::SUCCESS;
    }
}
