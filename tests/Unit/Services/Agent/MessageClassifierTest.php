<?php

namespace Tests\Unit\Services\Agent;

use App\Services\Agent\Sales\ChatHandler\MessageClassifier;
use PHPUnit\Framework\TestCase;

/**
 * MessageClassifier testlari — qoidaga asoslangan xabar aniqlash.
 */
class MessageClassifierTest extends TestCase
{
    private MessageClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new MessageClassifier();
    }

    /** @test */
    public function it_classifies_greeting_uzbek(): void
    {
        $result = $this->classifier->classify('Assalomu alaykum');
        $this->assertEquals(MessageClassifier::TYPE_GREETING, $result['type']);
    }

    /** @test */
    public function it_classifies_greeting_russian(): void
    {
        $result = $this->classifier->classify('Привет');
        $this->assertEquals(MessageClassifier::TYPE_GREETING, $result['type']);
    }

    /** @test */
    public function it_classifies_price_inquiry(): void
    {
        $result = $this->classifier->classify('Narxi qancha?');
        $this->assertEquals(MessageClassifier::TYPE_PRICE, $result['type']);
    }

    /** @test */
    public function it_classifies_price_russian(): void
    {
        $result = $this->classifier->classify('Сколько стоит?');
        $this->assertEquals(MessageClassifier::TYPE_PRICE, $result['type']);
    }

    /** @test */
    public function it_classifies_order(): void
    {
        $result = $this->classifier->classify('Buyurtma bermoqchiman');
        $this->assertEquals(MessageClassifier::TYPE_ORDER, $result['type']);
    }

    /** @test */
    public function it_classifies_operator_request(): void
    {
        $result = $this->classifier->classify('Operator bilan gaplashsam');
        $this->assertEquals(MessageClassifier::TYPE_OPERATOR, $result['type']);
    }

    /** @test */
    public function it_classifies_price_objection(): void
    {
        $result = $this->classifier->classify('Juda qimmat ekan');
        $this->assertEquals(MessageClassifier::TYPE_OBJECTION, $result['type']);
        $this->assertEquals('price', $result['objection_type']);
    }

    /** @test */
    public function it_classifies_timing_objection(): void
    {
        $result = $this->classifier->classify("O'ylab ko'raman");
        $this->assertEquals(MessageClassifier::TYPE_OBJECTION, $result['type']);
        $this->assertEquals('timing', $result['objection_type']);
    }

    /** @test */
    public function it_classifies_trust_objection(): void
    {
        $result = $this->classifier->classify('Bilmayman ishonchli ekanini');
        $this->assertEquals(MessageClassifier::TYPE_OBJECTION, $result['type']);
        $this->assertEquals('trust', $result['objection_type']);
    }

    /** @test */
    public function it_classifies_menu_request(): void
    {
        $result = $this->classifier->classify('Menyu ko\'rsating');
        $this->assertEquals(MessageClassifier::TYPE_MENU, $result['type']);
    }

    /** @test */
    public function it_classifies_complex_message(): void
    {
        $result = $this->classifier->classify('Bu kurs qaysi yoshdagilarga mos keladi va qanday natijalar kutish mumkin?');
        $this->assertEquals(MessageClassifier::TYPE_COMPLEX, $result['type']);
    }

    /** @test */
    public function it_prioritizes_operator_over_other_types(): void
    {
        $result = $this->classifier->classify('Operator bering, narxlar qimmat');
        $this->assertEquals(MessageClassifier::TYPE_OPERATOR, $result['type']);
    }
}
