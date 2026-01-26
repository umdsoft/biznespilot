<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DreamBuyerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->create(['user_id' => $this->user->id]);
        $this->user->teamBusinesses()->attach($this->business->id, ['role' => 'owner']);
    }

    /**
     * Test dream buyer creation.
     */
    public function test_dream_buyer_can_be_created(): void
    {
        session(['current_business_id' => $this->business->id]);

        $dreamBuyer = DreamBuyer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Dream Buyer',
            'description' => 'Premium customer profile',
            'priority' => 'high',
        ]);

        $this->assertNotNull($dreamBuyer->id);
        $this->assertEquals('Test Dream Buyer', $dreamBuyer->name);
        $this->assertEquals($this->business->id, $dreamBuyer->business_id);
    }

    /**
     * Test dream buyer factory.
     */
    public function test_dream_buyer_factory_creates_valid_record(): void
    {
        $dreamBuyer = DreamBuyer::factory()->forBusiness($this->business)->create();

        $this->assertNotNull($dreamBuyer->id);
        $this->assertNotNull($dreamBuyer->name);
        $this->assertEquals($this->business->id, $dreamBuyer->business_id);
    }

    /**
     * Test primary dream buyer.
     */
    public function test_dream_buyer_primary_status(): void
    {
        $dreamBuyer = DreamBuyer::factory()->primary()->forBusiness($this->business)->create();

        $this->assertTrue($dreamBuyer->is_primary);
        $this->assertEquals('high', $dreamBuyer->priority);
    }

    /**
     * Test Sell Like Crazy framework fields (Nine Questions).
     */
    public function test_dream_buyer_sell_like_crazy_fields(): void
    {
        $dreamBuyer = DreamBuyer::factory()->forBusiness($this->business)->create([
            'q3_where_do_they_hang_out' => 'Instagram va Telegram',
            'q5_what_are_they_afraid_of' => 'Pulni yo\'qotish',
            'q6_what_are_they_frustrated_with' => 'Vaqt yetishmaydi',
            'q8_what_do_they_secretly_want' => 'Biznesni o\'stirish',
            'pain_points' => 'Marketing qiyinchiliklari',
            'goals' => 'Sotuvlarni oshirish',
        ]);

        $this->assertNotNull($dreamBuyer->q3_where_do_they_hang_out);
        $this->assertNotNull($dreamBuyer->q5_what_are_they_afraid_of);
        $this->assertNotNull($dreamBuyer->q6_what_are_they_frustrated_with);
        $this->assertNotNull($dreamBuyer->q8_what_do_they_secretly_want);
        $this->assertNotNull($dreamBuyer->pain_points);
        $this->assertNotNull($dreamBuyer->goals);
    }

    /**
     * Test dream buyer is isolated by business.
     */
    public function test_dream_buyers_are_isolated_by_business(): void
    {
        $business2 = Business::factory()->create();

        DreamBuyer::factory()->forBusiness($this->business)->create(['name' => 'Buyer 1']);
        DreamBuyer::factory()->forBusiness($business2)->create(['name' => 'Buyer 2']);

        session(['current_business_id' => $this->business->id]);
        $dreamBuyers = DreamBuyer::all();

        $this->assertCount(1, $dreamBuyers);
        $this->assertEquals('Buyer 1', $dreamBuyers->first()->name);
    }

    /**
     * Test dream buyer soft delete.
     */
    public function test_dream_buyer_soft_delete(): void
    {
        session(['current_business_id' => $this->business->id]);

        $dreamBuyer = DreamBuyer::factory()->forBusiness($this->business)->create();
        $dreamBuyerId = $dreamBuyer->id;

        $dreamBuyer->delete();

        $this->assertSoftDeleted('dream_buyers', ['id' => $dreamBuyerId]);
    }

    /**
     * Test dream buyer demographic fields.
     */
    public function test_dream_buyer_demographic_fields(): void
    {
        $dreamBuyer = DreamBuyer::factory()->forBusiness($this->business)->create([
            'age_range' => '25-35',
            'income_level' => 'high',
            'location' => 'Toshkent',
            'occupation' => 'Tadbirkor',
        ]);

        $this->assertEquals('25-35', $dreamBuyer->age_range);
        $this->assertEquals('high', $dreamBuyer->income_level);
        $this->assertEquals('Toshkent', $dreamBuyer->location);
        $this->assertEquals('Tadbirkor', $dreamBuyer->occupation);
    }

    /**
     * Test dream buyer preferred channels.
     */
    public function test_dream_buyer_preferred_channels(): void
    {
        $dreamBuyer = DreamBuyer::factory()->forBusiness($this->business)->create([
            'preferred_channels' => 'telegram',
        ]);

        $this->assertEquals('telegram', $dreamBuyer->preferred_channels);
    }
}
