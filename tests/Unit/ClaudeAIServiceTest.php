<?php

namespace Tests\Unit;

use App\Services\ClaudeAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeAIServiceTest extends TestCase
{
    private ClaudeAIService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set a test API key (service reads from services.anthropic.api_key)
        config(['services.anthropic.api_key' => 'test-api-key-12345']);
        $this->service = new ClaudeAIService();
    }

    /**
     * Test service can be instantiated.
     */
    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ClaudeAIService::class, $this->service);
    }

    /**
     * Test complete method with mocked HTTP response.
     */
    public function test_complete_method(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Test response'],
                ],
            ], 200),
        ]);

        $result = $this->service->complete('Test prompt', null, 100, false);

        $this->assertEquals('Test response', $result);
    }

    /**
     * Test complete method with caching.
     */
    public function test_complete_method_uses_cache(): void
    {
        Cache::flush();

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Cached response'],
                ],
            ], 200),
        ]);

        // First call - should hit API
        $result1 = $this->service->complete('Test prompt', null, 100, true);
        $this->assertEquals('Cached response', $result1);

        // Second call - should use cache
        $result2 = $this->service->complete('Test prompt', null, 100, true);
        $this->assertEquals('Cached response', $result2);

        // Only one HTTP request should have been made
        Http::assertSentCount(1);
    }

    /**
     * Test chat method.
     */
    public function test_chat_method(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Chat response'],
                ],
            ], 200),
        ]);

        $messages = [
            ['role' => 'user', 'content' => 'Hello'],
        ];

        $result = $this->service->chat($messages);

        $this->assertEquals('Chat response', $result);
    }

    /**
     * Test generate dream buyer avatar.
     */
    public function test_generate_dream_buyer_avatar(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Dream buyer avatar description'],
                ],
            ], 200),
        ]);

        $dreamBuyerData = [
            'name' => 'Test Buyer',
            'frustrations' => 'Time management',
            'dreams' => 'Business growth',
        ];

        $result = $this->service->generateDreamBuyerAvatar($dreamBuyerData);

        $this->assertNotEmpty($result);
    }

    /**
     * Test generate competitor insights.
     */
    public function test_generate_competitor_insights(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Competitor analysis insights'],
                ],
            ], 200),
        ]);

        $competitorData = [
            'name' => 'Competitor A',
            'products' => ['Product 1', 'Product 2'],
        ];

        $businessData = [
            'name' => 'My Business',
            'category' => 'retail',
        ];

        $result = $this->service->generateCompetitorInsights($competitorData, $businessData);

        $this->assertNotEmpty($result);
    }

    /**
     * Test generate marketing recommendations.
     */
    public function test_generate_marketing_recommendations(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Marketing recommendations'],
                ],
            ], 200),
        ]);

        $analyticsData = [
            'leads_count' => 100,
            'conversion_rate' => 5.5,
            'top_sources' => ['telegram', 'instagram'],
        ];

        $result = $this->service->generateMarketingRecommendations($analyticsData);

        $this->assertNotEmpty($result);
    }

    /**
     * Test generate chatbot response.
     */
    public function test_generate_chatbot_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Chatbot response'],
                ],
            ], 200),
        ]);

        $userMessage = 'Salom';
        $conversationHistory = [];
        $businessContext = [
            'business_name' => 'Test Business',
            'description' => 'Test business providing services',
        ];

        $result = $this->service->generateChatbotResponse($userMessage, $conversationHistory, $businessContext);

        $this->assertNotEmpty($result);
    }

    /**
     * Test analyze call transcription.
     */
    public function test_analyze_call(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => json_encode([
                        'summary' => 'Call summary',
                        'sentiment' => 'positive',
                        'key_points' => ['Point 1', 'Point 2'],
                        'recommended_actions' => ['Action 1'],
                        'stage_recommendation' => 'qualified',
                    ])],
                ],
            ], 200),
        ]);

        $transcription = 'Hello, I am interested in your product...';
        $stages = ['new', 'contacted', 'qualified'];

        $result = $this->service->analyzeCall($transcription, $stages);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('sentiment', $result);
    }

    /**
     * Test API error handling.
     */
    public function test_api_error_handling(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'error' => ['message' => 'API Error'],
            ], 500),
        ]);

        $result = $this->service->complete('Test prompt', null, 100, false);

        $this->assertStringContainsString('AI xizmati hozircha mavjud emas', $result);
    }

    /**
     * Test missing API key handling.
     */
    public function test_missing_api_key(): void
    {
        // Clear both possible config keys and env (use empty string as service has strict string type)
        config(['services.anthropic.api_key' => '']);
        putenv('ANTHROPIC_API_KEY=');
        $service = new ClaudeAIService();

        $result = $service->complete('Test prompt');

        $this->assertStringContainsString('API kalit sozlanmagan', $result);
    }

    /**
     * Test premium model usage.
     */
    public function test_premium_model_usage(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Premium response'],
                ],
            ], 200),
        ]);

        $result = $this->service->complete('Test prompt', null, 100, false, true);

        $this->assertEquals('Premium response', $result);

        // Verify the correct model was used
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return str_contains($body['model'] ?? '', 'sonnet');
        });
    }
}
