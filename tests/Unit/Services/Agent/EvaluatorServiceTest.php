<?php

namespace Tests\Unit\Services\Agent;

use App\Services\Agent\EvaluatorService;
use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use PHPUnit\Framework\TestCase;

/**
 * Tekshiruvchi tizimi testlari.
 */
class EvaluatorServiceTest extends TestCase
{
    private EvaluatorService $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $aiService = $this->createMock(AIService::class);
        // AI chaqirilganda tasdiqlangan javob qaytarish
        $aiService->method('ask')->willReturn(
            new AIResponse(
                content: '{"approved": true, "reason": null}',
                model: 'claude-haiku-4-5-20251001',
                tokensInput: 50,
                tokensOutput: 20,
                costUsd: 0.0001,
                source: 'api',
                success: true,
            )
        );
        $this->evaluator = new EvaluatorService($aiService);
    }

    /** @test */
    public function it_approves_low_risk_actions_without_checking(): void
    {
        $result = $this->evaluator->evaluate(
            'analytics', 'kpi_display', 'Bugungi sotuvlar: 15 ta', 'biz-1'
        );

        $this->assertTrue($result['approved']);
        $this->assertEquals('low', $result['risk_level']);
        $this->assertEquals('skip', $result['method']);
    }

    /** @test */
    public function it_approves_greeting_without_checking(): void
    {
        $result = $this->evaluator->evaluate(
            'orchestrator', 'greeting', 'Assalomu alaykum!', 'biz-1'
        );

        $this->assertTrue($result['approved']);
        $this->assertEquals('skip', $result['method']);
    }

    /** @test */
    public function it_rejects_dangerous_financial_advice(): void
    {
        $result = $this->evaluator->evaluate(
            'analytics', 'recommendation',
            'Barcha pulni reklama ga sarflang, albatta ishlaydi!', 'biz-1'
        );

        $this->assertFalse($result['approved']);
        $this->assertEquals('rule', $result['method']);
        $this->assertNotNull($result['reason']);
    }

    /** @test */
    public function it_rejects_absolute_guarantee_claims(): void
    {
        $result = $this->evaluator->evaluate(
            'marketing', 'campaign_advice',
            'Bu strategiya bilan kafolat beraman 3x o\'sish bo\'ladi', 'biz-1'
        );

        $this->assertFalse($result['approved']);
    }

    /** @test */
    public function it_detects_high_risk_for_price_changes(): void
    {
        $result = $this->evaluator->evaluate(
            'sales', 'price_change',
            'Narxni 20% ga oshirish tavsiya qilinadi', 'biz-1'
        );

        $this->assertEquals('high', $result['risk_level']);
    }
}
