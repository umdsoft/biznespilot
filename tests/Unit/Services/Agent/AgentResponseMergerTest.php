<?php

namespace Tests\Unit\Services\Agent;

use App\Services\Agent\AgentResponseMerger;
use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use PHPUnit\Framework\TestCase;

class AgentResponseMergerTest extends TestCase
{
    private AgentResponseMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();
        $aiService = $this->createMock(AIService::class);
        $this->merger = new AgentResponseMerger($aiService);
    }

    /** @test */
    public function it_returns_single_response_unchanged(): void
    {
        $response = AIResponse::fromRule('Bitta javob');

        $result = $this->merger->merge(['analytics' => $response], 1);

        $this->assertEquals('Bitta javob', $result->content);
        $this->assertEquals('rule', $result->source);
    }

    /** @test */
    public function it_merges_two_responses(): void
    {
        $responses = [
            'analytics' => AIResponse::fromDatabase('KPI: 50 ta sotuv'),
            'marketing' => AIResponse::fromDatabase('Instagram reach: 5000'),
        ];

        $result = $this->merger->merge($responses, 1);

        $this->assertStringContainsString('Tahlil', $result->content);
        $this->assertStringContainsString('Marketing', $result->content);
        $this->assertEquals('merged', $result->source);
    }

    /** @test */
    public function it_filters_failed_responses(): void
    {
        $responses = [
            'analytics' => AIResponse::fromDatabase('Yaxshi natija'),
            'marketing' => AIResponse::error('Xato'),
        ];

        $result = $this->merger->merge($responses, 1);

        $this->assertEquals('Yaxshi natija', $result->content);
        $this->assertTrue($result->success);
    }

    /** @test */
    public function it_returns_error_if_all_fail(): void
    {
        $responses = [
            'analytics' => AIResponse::error('Xato 1'),
            'marketing' => AIResponse::error('Xato 2'),
        ];

        $result = $this->merger->merge($responses, 1);

        $this->assertFalse($result->success);
    }
}
