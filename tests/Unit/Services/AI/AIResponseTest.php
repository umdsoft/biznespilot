<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\AIResponse;
use PHPUnit\Framework\TestCase;

class AIResponseTest extends TestCase
{
    /** @test */
    public function it_creates_from_cache(): void
    {
        $response = AIResponse::fromCache('Cached javob');

        $this->assertEquals('Cached javob', $response->content);
        $this->assertEquals('none', $response->model);
        $this->assertEquals(0, $response->tokensInput);
        $this->assertEquals(0, $response->tokensOutput);
        $this->assertEquals(0, $response->costUsd);
        $this->assertEquals('cache', $response->source);
        $this->assertTrue($response->success);
    }

    /** @test */
    public function it_creates_from_rule(): void
    {
        $response = AIResponse::fromRule('Salom! Men yordamchiman.');

        $this->assertEquals('Salom! Men yordamchiman.', $response->content);
        $this->assertEquals('rule', $response->source);
        $this->assertEquals(0, $response->costUsd);
        $this->assertTrue($response->success);
    }

    /** @test */
    public function it_creates_from_database(): void
    {
        $response = AIResponse::fromDatabase('Bugun 5 ta sotuv');

        $this->assertEquals('database', $response->source);
        $this->assertEquals(0, $response->costUsd);
        $this->assertTrue($response->success);
    }

    /** @test */
    public function it_creates_from_api(): void
    {
        $apiData = [
            'content' => [['text' => 'AI javobi']],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
        ];

        $response = AIResponse::fromAPI($apiData, 'claude-haiku-4-5-20251001', 250);

        $this->assertEquals('AI javobi', $response->content);
        $this->assertEquals('claude-haiku-4-5-20251001', $response->model);
        $this->assertEquals(100, $response->tokensInput);
        $this->assertEquals(50, $response->tokensOutput);
        $this->assertEquals('api', $response->source);
        $this->assertEquals(250, $response->processingTimeMs);
        $this->assertTrue($response->success);
        $this->assertGreaterThan(0, $response->costUsd);
    }

    /** @test */
    public function it_creates_error_response(): void
    {
        $response = AIResponse::error('API ishlamadi');

        $this->assertFalse($response->success);
        $this->assertEquals('API ishlamadi', $response->error);
        $this->assertEquals('error', $response->source);
        $this->assertStringContains('texnik muammo', $response->content);
    }

    /** @test */
    public function it_calculates_haiku_cost_correctly(): void
    {
        // Haiku: $0.80/1M input, $4.00/1M output
        $cost = AIResponse::calculateCost('claude-haiku-4-5-20251001', 1000, 500);

        // 1000 * 0.80 / 1M + 500 * 4.00 / 1M = 0.0008 + 0.002 = 0.0028
        $this->assertEqualsWithDelta(0.0028, $cost, 0.0001);
    }

    /** @test */
    public function it_calculates_sonnet_cost_correctly(): void
    {
        // Sonnet: $3.00/1M input, $15.00/1M output
        $cost = AIResponse::calculateCost('claude-sonnet-4-6', 1000, 500);

        // 1000 * 3.00 / 1M + 500 * 15.00 / 1M = 0.003 + 0.0075 = 0.0105
        $this->assertEqualsWithDelta(0.0105, $cost, 0.0001);
    }

    /** @test */
    public function it_converts_to_array(): void
    {
        $response = AIResponse::fromRule('Test');
        $array = $response->toArray();

        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('model', $array);
        $this->assertArrayHasKey('source', $array);
        $this->assertArrayHasKey('success', $array);
        $this->assertEquals('Test', $array['content']);
    }

    /**
     * PHPUnit 10+ uchun assertStringContains
     */
    private static function assertStringContains(string $needle, string $haystack): void
    {
        self::assertStringContainsString($needle, $haystack);
    }
}
