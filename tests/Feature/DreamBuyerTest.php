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
        $this->user->businesses()->attach($this->business->id, ['role' => 'owner']);
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
            'priority' => 1,
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
        $this->assertEquals(1, $dreamBuyer->priority);
    }

    /**
     * Test Sell Like Crazy framework fields.
     */
    public function test_dream_buyer_sell_like_crazy_fields(): void
    {
        $dreamBuyer = DreamBuyer::factory()->forBusiness($this->business)->create([
            'where_spend_time' => 'Instagram va Telegram',
            'info_sources' => 'YouTube va bloglar',
            'frustrations' => 'Vaqt yetishmaydi',
            'dreams' => 'Biznesni o\'stirish',
            'fears' => 'Pulni yo\'qotish',
        ]);

        $this->assertNotNull($dreamBuyer->where_spend_time);
        $this->assertNotNull($dreamBuyer->info_sources);
        $this->assertNotNull($dreamBuyer->frustrations);
        $this->assertNotNull($dreamBuyer->dreams);
        $this->assertNotNull($dreamBuyer->fears);
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
     * Test dream buyer data cast to array.
     */
    public function test_dream_buyer_data_cast(): void
    {
        $dreamBuyer = DreamBuyer::factory()->forBusiness($this->business)->create([
            'data' => ['age_range' => '25-35', 'income' => 'high'],
        ]);

        $this->assertIsArray($dreamBuyer->data);
        $this->assertEquals('25-35', $dreamBuyer->data['age_range']);
        $this->assertEquals('high', $dreamBuyer->data['income']);
    }

    /**
     * Test dream buyer communication preferences.
     */
    public function test_dream_buyer_communication_preferences(): void
    {
        $dreamBuyer = DreamBuyer::factory()->forBusiness($this->business)->create([
            'communication_preferences' => 'telegram',
        ]);

        $this->assertEquals('telegram', $dreamBuyer->communication_preferences);
    }
}
