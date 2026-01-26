<?php

/**
 * Gap Analysis Verification Script
 *
 * Foydalanish:
 * 1. php artisan tinker
 * 2. require 'scripts/verify_gap_analysis.php';
 *
 * Yoki to'g'ridan-to'g'ri:
 * php artisan tinker scripts/verify_gap_analysis.php
 */

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LostOpportunity;
use App\Models\MarketingChannel;
use App\Models\Order;
use App\Models\User;
use App\Services\Sales\LostOpportunityService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║       BiznesPilot - Gap Analysis Verification                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

try {
    DB::beginTransaction();

    // Get first business and user
    $business = Business::first();
    $user = User::first();

    if (!$business || !$user) {
        throw new Exception("Business yoki User topilmadi. Avval seed qiling.");
    }

    // Get or create campaign and channel
    $campaign = Campaign::firstOrCreate(
        ['business_id' => $business->id, 'name' => 'test_promo_summer'],
        ['type' => 'promotion', 'status' => 'active']
    );

    $channel = MarketingChannel::firstOrCreate(
        ['business_id' => $business->id, 'name' => 'Test Instagram Ads'],
        ['type' => 'paid_social']
    );

    echo "✓ Setup complete\n";
    echo "  Business: {$business->name} ({$business->id})\n";
    echo "  User: {$user->name}\n";
    echo "  Campaign: {$campaign->name}\n";
    echo "  Channel: {$channel->name}\n\n";

    // ============================================
    // SCENARIO 1: Lost Money Check
    // ============================================
    echo "══════════════════════════════════════════════════════════════\n";
    echo "  SCENARIO 1: The 'Lost Money' Check\n";
    echo "══════════════════════════════════════════════════════════════\n\n";

    // Create Lead
    $lead = Lead::create([
        'business_id' => $business->id,
        'assigned_to' => $user->id,
        'name' => 'Test Lead - ' . now()->timestamp,
        'phone' => '+998901234567',
        'estimated_value' => 5000000,
        'status' => 'negotiation',
        'campaign_id' => $campaign->id,
        'marketing_channel_id' => $channel->id,
        'utm_source' => 'instagram',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'summer_promo',
    ]);
    echo "✓ Lead created: {$lead->id}\n";
    echo "  Estimated Value: " . number_format($lead->estimated_value) . " UZS\n\n";

    // Mark as lost
    $lead->update([
        'status' => 'lost',
        'lost_reason' => 'competitor',
        'lost_reason_details' => 'Test: Mijoz raqobatchi kompaniyasini tanladi',
    ]);

    // Call service
    $service = app(LostOpportunityService::class);
    $lostOpp = $service->trackLostLead(
        lead: $lead,
        lostReason: $lead->lost_reason,
        lostReasonDetails: $lead->lost_reason_details,
        lostBy: $user,
        lostToCompetitor: 'Raqobatchi A'
    );

    echo "✓ LostOpportunity created: {$lostOpp->id}\n\n";

    // Verify
    echo "📊 RESULTS:\n";
    echo "  estimated_value: " . number_format($lostOpp->estimated_value) . " UZS " . ($lostOpp->estimated_value == 5000000 ? '✅' : '❌') . "\n";
    echo "  campaign_id: {$lostOpp->campaign_id} " . ($lostOpp->campaign_id == $campaign->id ? '✅' : '❌') . "\n";
    echo "  utm_source: {$lostOpp->utm_source} " . ($lostOpp->utm_source == 'instagram' ? '✅' : '❌') . "\n";
    echo "  lost_reason: {$lostOpp->lost_reason} " . ($lostOpp->lost_reason == 'competitor' ? '✅' : '❌') . "\n\n";

    $s1Pass = $lostOpp->estimated_value == 5000000 && $lostOpp->utm_source == 'instagram';
    echo ($s1Pass ? "🎉 SCENARIO 1: PASSED!\n" : "❌ SCENARIO 1: FAILED\n");

    // ============================================
    // SCENARIO 2: Marketing Truth Check
    // ============================================
    echo "\n══════════════════════════════════════════════════════════════\n";
    echo "  SCENARIO 2: The 'Marketing Truth' Check\n";
    echo "══════════════════════════════════════════════════════════════\n\n";

    // Create won Lead
    $lead2 = Lead::create([
        'business_id' => $business->id,
        'assigned_to' => $user->id,
        'name' => 'Test Customer Lead - ' . now()->timestamp,
        'phone' => '+998901234568',
        'status' => 'won',
        'campaign_id' => $campaign->id,
        'marketing_channel_id' => $channel->id,
        'utm_source' => 'instagram',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'summer_promo',
    ]);
    echo "✓ Lead (won) created: {$lead2->id}\n";

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
    echo "✓ Customer created: {$customer->id}\n";

    // Create Order WITHOUT attribution (should auto-populate)
    $order = Order::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'order_number' => 'ORD-TEST-' . time(),
        'total_amount' => 1500000,
        'currency' => 'UZS',
        'status' => 'pending',
        'payment_status' => 'pending',
        'ordered_at' => now(),
        // NO attribution set!
    ]);
    $order->refresh();
    echo "✓ Order created: {$order->id} ({$order->order_number})\n\n";

    // Verify
    echo "📊 RESULTS:\n";
    echo "  campaign_id: " . ($order->campaign_id ?? 'NULL') . " " . ($order->campaign_id == $campaign->id ? '✅' : '❌') . "\n";
    echo "  channel_id: " . ($order->marketing_channel_id ?? 'NULL') . " " . ($order->marketing_channel_id == $channel->id ? '✅' : '❌') . "\n";
    echo "  lead_id: " . ($order->lead_id ?? 'NULL') . " " . ($order->lead_id == $lead2->id ? '✅' : '❌') . "\n";
    echo "  utm_source: " . ($order->utm_source ?? 'NULL') . " " . ($order->utm_source == 'instagram' ? '✅' : '❌') . "\n";
    echo "  hasAttribution(): " . ($order->hasAttribution() ? 'true ✅' : 'false ❌') . "\n\n";

    $s2Pass = $order->campaign_id == $campaign->id && $order->utm_source == 'instagram';
    echo ($s2Pass ? "🎉 SCENARIO 2: PASSED!\n" : "❌ SCENARIO 2: FAILED\n");

    // ============================================
    // Final
    // ============================================
    echo "\n══════════════════════════════════════════════════════════════\n";
    if ($s1Pass && $s2Pass) {
        echo "  ✅ ALL TESTS PASSED! Black Box is working correctly.\n";
    } else {
        echo "  ❌ SOME TESTS FAILED\n";
    }
    echo "══════════════════════════════════════════════════════════════\n\n";

    // Rollback
    DB::rollBack();
    echo "🧹 Test data rolled back (no permanent changes)\n\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
