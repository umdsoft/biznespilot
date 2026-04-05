<?php

namespace Tests\Unit\Services\Agent;

use App\Services\Agent\Sales\LeadScoring\RealTimeScorer;
use PHPUnit\Framework\TestCase;

/**
 * Lead baholash testlari — qoidaga asoslangan ball hisoblash.
 */
class RealTimeScorerTest extends TestCase
{
    private RealTimeScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new RealTimeScorer();
    }

    /** @test */
    public function it_adds_points_for_greeting(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'greeting', null, 0);

        $this->assertEquals(2, $result['new_score']);
        $this->assertEquals(2, $result['change']);
    }

    /** @test */
    public function it_adds_points_for_product_inquiry(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'product', null, 10);

        $this->assertEquals(20, $result['new_score']);
        $this->assertEquals(10, $result['change']);
    }

    /** @test */
    public function it_adds_high_points_for_price_inquiry(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'price', null, 20);

        $this->assertEquals(35, $result['new_score']);
        $this->assertEquals(15, $result['change']);
    }

    /** @test */
    public function it_adds_max_points_for_order(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'order', null, 50);

        $this->assertEquals(80, $result['new_score']);
        $this->assertEquals(30, $result['change']);
    }

    /** @test */
    public function it_subtracts_points_for_price_objection(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'objection', 'price', 30);

        $this->assertEquals(27, $result['new_score']);
        $this->assertEquals(-3, $result['change']);
    }

    /** @test */
    public function it_subtracts_points_for_timing_objection(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'objection', 'timing', 30);

        $this->assertEquals(25, $result['new_score']);
        $this->assertEquals(-5, $result['change']);
    }

    /** @test */
    public function it_never_goes_below_zero(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'objection', 'need', 3);

        $this->assertGreaterThanOrEqual(0, $result['new_score']);
    }

    /** @test */
    public function it_never_exceeds_100(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'order', null, 90);

        $this->assertLessThanOrEqual(100, $result['new_score']);
    }

    /** @test */
    public function it_recommends_operator_for_hot_leads(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'order', null, 60);

        $this->assertEquals('notify_operator_hot', $result['action']);
    }

    /** @test */
    public function it_recommends_nurture_for_cold_leads(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'greeting', null, 0);

        $this->assertEquals('nurture_sequence', $result['action']);
    }

    /** @test */
    public function it_recommends_intensify_for_interested_leads(): void
    {
        $result = $this->scorer->scoreMessage('biz-1', null, 'price', null, 40);

        $this->assertEquals('intensify_sales', $result['action']);
    }
}
