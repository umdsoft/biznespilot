<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Lead;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->create(['user_id' => $this->user->id]);
        $this->user->businesses()->attach($this->business->id, ['role' => 'owner']);
    }

    /**
     * Test offer creation.
     */
    public function test_offer_can_be_created(): void
    {
        session(['current_business_id' => $this->business->id]);

        $offer = Offer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Offer',
            'description' => 'Test description',
            'pricing' => 1000000,
            'status' => 'draft',
        ]);

        $this->assertNotNull($offer->id);
        $this->assertEquals('Test Offer', $offer->name);
        $this->assertEquals($this->business->id, $offer->business_id);
    }

    /**
     * Test offer factory.
     */
    public function test_offer_factory_creates_valid_offer(): void
    {
        $offer = Offer::factory()->forBusiness($this->business)->create();

        $this->assertNotNull($offer->id);
        $this->assertNotNull($offer->name);
        $this->assertEquals($this->business->id, $offer->business_id);
    }

    /**
     * Test offer active status.
     */
    public function test_offer_is_active(): void
    {
        $offer = Offer::factory()->active()->forBusiness($this->business)->create();

        $this->assertTrue($offer->isActive());
        $this->assertEquals('active', $offer->status);
    }

    /**
     * Test offer draft status.
     */
    public function test_offer_is_draft(): void
    {
        $offer = Offer::factory()->draft()->forBusiness($this->business)->create();

        $this->assertFalse($offer->isActive());
        $this->assertEquals('draft', $offer->status);
    }

    /**
     * Test value score calculation.
     */
    public function test_value_score_calculation(): void
    {
        $offer = Offer::factory()->forBusiness($this->business)->create([
            'dream_outcome_score' => 10,
            'perceived_likelihood_score' => 8,
            'time_delay_days' => 10,
            'effort_score' => 4,
        ]);

        // (10 * 8) / (10 * 4) = 80 / 40 = 2.0
        $expectedScore = 2.0;
        $this->assertEquals($expectedScore, $offer->value_score);
    }

    /**
     * Test high value offer has good value score.
     */
    public function test_high_value_offer_has_good_score(): void
    {
        $offer = Offer::factory()->highValue()->forBusiness($this->business)->create();

        // (9 * 8) / (7 * 2) = 72 / 14 ≈ 5.14
        $this->assertGreaterThan(3, $offer->value_score);
    }

    /**
     * Test offer with guarantee.
     */
    public function test_offer_with_guarantee(): void
    {
        $offer = Offer::factory()->withGuarantee()->forBusiness($this->business)->create();

        $this->assertEquals('money_back', $offer->guarantee_type);
        $this->assertNotNull($offer->guarantee_terms);
        $this->assertEquals(30, $offer->guarantee_period_days);
    }

    /**
     * Test offer scopes.
     */
    public function test_offer_scopes(): void
    {
        session(['current_business_id' => $this->business->id]);

        Offer::factory()->active()->forBusiness($this->business)->create();
        Offer::factory()->draft()->forBusiness($this->business)->create();
        Offer::factory()->draft()->forBusiness($this->business)->create();

        $activeOffers = Offer::active()->get();
        $this->assertCount(1, $activeOffers);

        $draftOffers = Offer::draft()->get();
        $this->assertCount(2, $draftOffers);
    }

    /**
     * Test offer soft delete.
     */
    public function test_offer_soft_delete(): void
    {
        session(['current_business_id' => $this->business->id]);

        $offer = Offer::factory()->forBusiness($this->business)->create();
        $offerId = $offer->id;

        $offer->delete();

        $this->assertSoftDeleted('offers', ['id' => $offerId]);
    }

    /**
     * Test value score is auto-calculated on save.
     */
    public function test_value_score_auto_calculated(): void
    {
        $offer = new Offer([
            'business_id' => $this->business->id,
            'name' => 'Test Offer',
            'dream_outcome_score' => 8,
            'perceived_likelihood_score' => 6,
            'time_delay_days' => 12,
            'effort_score' => 3,
            'status' => 'draft',
        ]);

        $offer->save();

        // (8 * 6) / (12 * 3) = 48 / 36 ≈ 1.33
        $this->assertEquals(1.33, $offer->value_score);
    }

    /**
     * Test offer prevents division by zero.
     */
    public function test_value_score_handles_zero_values(): void
    {
        $offer = Offer::factory()->forBusiness($this->business)->create([
            'time_delay_days' => 0,
            'effort_score' => 0,
        ]);

        $this->assertEquals(0, $offer->value_score);
    }

    /**
     * Test offer leads relationship.
     */
    public function test_offer_leads_relationship(): void
    {
        session(['current_business_id' => $this->business->id]);

        $offer = Offer::factory()->forBusiness($this->business)->create();
        $lead = Lead::factory()->forBusiness($this->business)->create();

        $offer->leads()->attach($lead->id, [
            'status' => 'sent',
            'channel' => 'telegram',
            'sent_at' => now(),
            'tracking_code' => 'TEST123',
        ]);

        $this->assertCount(1, $offer->leads);
        $this->assertEquals($lead->id, $offer->leads->first()->id);
    }

    /**
     * Test offer is isolated by business.
     */
    public function test_offers_are_isolated_by_business(): void
    {
        $business2 = Business::factory()->create();

        Offer::factory()->forBusiness($this->business)->create(['name' => 'Offer 1']);
        Offer::factory()->forBusiness($business2)->create(['name' => 'Offer 2']);

        session(['current_business_id' => $this->business->id]);
        $offers = Offer::all();

        $this->assertCount(1, $offers);
        $this->assertEquals('Offer 1', $offers->first()->name);
    }
}
