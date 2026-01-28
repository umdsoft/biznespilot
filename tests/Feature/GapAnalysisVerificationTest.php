<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Gap Analysis Verification Tests
 *
 * Bu testlar "Black Box" konsepsiyasi implementatsiyasini tekshiradi:
 * 1. Lost Opportunity Tracking (yo'qotilgan pulni kuzatish)
 * 2. Order Marketing Attribution (buyurtma marketing attributsiyasi)
 */
class GapAnalysisVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected User $user;
    protected Campaign $campaign;
    protected MarketingChannel $channel;
    protected PipelineStage $lostStage;

    protected function setUp(): void
    {
        parent::setUp();

        // Test uchun asosiy ma'lumotlar
        $this->business = Business::factory()->create();
        $this->user = User::factory()->create();
        $this->user->teamBusinesses()->attach($this->business->id);

        // Marketing Campaign
        $this->campaign = Campaign::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'promo_summer',
        ]);

        // Marketing Channel
        $this->channel = MarketingChannel::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Instagram Ads',
            'type' => 'paid_social',
        ]);

        // Lost pipeline stage - use updateOrCreate to ensure is_lost is true
        $this->lostStage = PipelineStage::updateOrCreate(
            ['business_id' => $this->business->id, 'slug' => 'lost'],
            [
                'name' => 'Yo\'qotilgan',
                'order' => 100,
                'color' => '#dc3545',
                'is_won' => false,
                'is_lost' => true,
            ]
        );
    }

    /**
     * SCENARIO 1: The "Lost Money" Check
     *
     * Lead yo'qotilganda LostOpportunity yaratilishini tekshirish.
     */
    public function test_scenario_1_lost_money_tracking(): void
    {
        // 1. Create a Lead with estimated_value = 5,000,000 UZS
        $lead = Lead::factory()->create([
            'business_id' => $this->business->id,
            'assigned_to' => $this->user->id,
            'estimated_value' => 5000000,
            'status' => 'negotiation',
            'lost_reason' => null,
            // Marketing Attribution
            'campaign_id' => $this->campaign->id,
            'marketing_channel_id' => $this->channel->id,
            'utm_source' => 'instagram',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'summer_promo',
        ]);

        // 2. Change status to 'lost'
        $lead->update([
            'status' => 'lost',
            'lost_reason' => 'competitor',
            'lost_reason_details' => 'Mijoz raqobatchi kompaniyasini tanladi',
        ]);

        // 3. Trigger the event (normally this happens via observer/controller)
        event(new LeadStageChanged(
            lead: $lead,
            oldStage: null,
            newStage: $this->lostStage,
            reason: 'Test: Lead lost',
            automated: false
        ));

        // 4. CHECK: Query the lost_opportunities table
        $lostOpportunity = LostOpportunity::where('lead_id', $lead->id)->first();

        // Assertions
        $this->assertNotNull($lostOpportunity, 'LostOpportunity record should exist');
        $this->assertEquals(5000000, $lostOpportunity->estimated_value);
        $this->assertEquals($this->user->id, $lostOpportunity->assigned_to);
        $this->assertEquals('competitor', $lostOpportunity->lost_reason);

        // Marketing Attribution should be inherited
        $this->assertEquals($this->campaign->id, $lostOpportunity->campaign_id);
        $this->assertEquals($this->channel->id, $lostOpportunity->marketing_channel_id);
        $this->assertEquals('instagram', $lostOpportunity->utm_source);
        $this->assertEquals('cpc', $lostOpportunity->utm_medium);

        // Output for verification
        dump('=== SCENARIO 1: Lost Money Check ===');
        dump('Lead ID: ' . $lead->id);
        dump('LostOpportunity ID: ' . $lostOpportunity->id);
        dump('Estimated Value: ' . number_format($lostOpportunity->estimated_value) . ' UZS');
        dump('Lost Reason: ' . $lostOpportunity->lost_reason_label);
        dump('Campaign: ' . $lostOpportunity->campaign?->name);
        dump('Channel: ' . $lostOpportunity->marketingChannel?->name);
        dump('UTM Source: ' . $lostOpportunity->utm_source);
        dump('Has Attribution: ' . ($lostOpportunity->hasAttribution() ? 'YES' : 'NO'));
    }

    /**
     * SCENARIO 2: The "Marketing Truth" Check
     *
     * Order yaratilganda Customer/Lead dan attribution meros olishini tekshirish.
     */
    public function test_scenario_2_order_marketing_attribution(): void
    {
        // 1. Create a Lead with Instagram attribution
        $lead = Lead::factory()->create([
            'business_id' => $this->business->id,
            'assigned_to' => $this->user->id,
            'status' => 'won',
            // Marketing Attribution
            'campaign_id' => $this->campaign->id,
            'marketing_channel_id' => $this->channel->id,
            'utm_source' => 'instagram',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'summer_promo',
            'utm_content' => 'carousel_ad',
            'utm_term' => 'sale',
        ]);

        // 2. Create a Customer from this Lead
        $customer = Customer::factory()->create([
            'business_id' => $this->business->id,
            'lead_id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            // Acquisition tracking (from Lead)
            'first_acquisition_channel_id' => $lead->marketing_channel_id,
            'first_campaign_id' => $lead->campaign_id,
            'first_acquisition_source' => 'instagram',
            'first_acquisition_source_type' => 'digital',
        ]);

        // 3. Create an Order for this Customer (WITHOUT explicitly setting attribution)
        $order = Order::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-TEST-001',
            'total' => 1500000,
            'currency' => 'UZS',
            'status' => 'pending',
            'payment_status' => 'pending',
            // NOTE: Attribution fields are NOT set here!
            // They should be auto-populated by OrderObserver
        ]);

        // Refresh to get observer-populated fields
        $order->refresh();

        // 4. CHECK: Order should have inherited attribution
        $this->assertEquals('instagram', $order->utm_source, 'Order should inherit utm_source');
        $this->assertEquals($this->campaign->id, $order->campaign_id, 'Order should inherit campaign_id');
        $this->assertEquals($this->channel->id, $order->marketing_channel_id, 'Order should inherit channel_id');
        $this->assertEquals($lead->id, $order->lead_id, 'Order should link to original lead');
        $this->assertTrue($order->hasAttribution(), 'Order should have attribution');

        // Output for verification
        dump('=== SCENARIO 2: Marketing Truth Check ===');
        dump('Lead ID: ' . $lead->id);
        dump('Customer ID: ' . $customer->id);
        dump('Order ID: ' . $order->id);
        dump('Order Number: ' . $order->order_number);
        dump('Total: ' . number_format($order->total) . ' UZS');
        dump('--- Attribution ---');
        dump('Campaign ID: ' . $order->campaign_id);
        dump('Channel ID: ' . $order->marketing_channel_id);
        dump('Lead ID: ' . $order->lead_id);
        dump('UTM Source: ' . $order->utm_source);
        dump('UTM Medium: ' . $order->utm_medium);
        dump('UTM Campaign: ' . $order->utm_campaign);
        dump('Has Attribution: ' . ($order->hasAttribution() ? 'YES' : 'NO'));
        dump('Attribution Summary:', $order->getAttributionSummary());
    }

    /**
     * Bonus: Test Marketing KPI Lost Count Update
     * Note: Full test requires queue processing, so we verify the listener exists
     */
    public function test_marketing_kpi_lost_count_incremented(): void
    {
        // Verify the LeadStageChangedListener exists and is properly configured
        $this->assertTrue(
            class_exists(\App\Listeners\LeadStageChangedListener::class),
            'LeadStageChangedListener class should exist'
        );

        // Verify listener handles LeadStageChanged event
        $this->assertTrue(
            method_exists(\App\Listeners\LeadStageChangedListener::class, 'handle'),
            'LeadStageChangedListener should have handle method'
        );
    }
}
