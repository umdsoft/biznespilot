<?php

namespace Tests\Unit\Services\Agent;

use App\Services\Agent\AgentRouter;
use App\Services\AI\AIService;
use PHPUnit\Framework\TestCase;

/**
 * AgentRouter qoidaga asoslangan yo'naltirish testlari.
 * AI chaqiriqsiz — faqat kalit so'zlar va naqshlar tekshiriladi.
 */
class AgentRouterTest extends TestCase
{
    private AgentRouter $router;

    protected function setUp(): void
    {
        parent::setUp();

        // AIService ni mock qilamiz (AI chaqirilmasligi kerak)
        $aiService = $this->createMock(AIService::class);
        $this->router = new AgentRouter($aiService);
    }

    /** @test */
    public function it_routes_greetings_to_orchestrator(): void
    {
        $result = $this->router->route('Salom', 1);

        $this->assertEquals([AgentRouter::AGENT_ORCHESTRATOR], $result['agents']);
        $this->assertEquals('rule', $result['method']);
    }

    /** @test */
    public function it_routes_assalomu_alaykum_to_orchestrator(): void
    {
        $result = $this->router->route('Assalomu alaykum!', 1);

        $this->assertEquals([AgentRouter::AGENT_ORCHESTRATOR], $result['agents']);
    }

    /** @test */
    public function it_routes_thanks_to_orchestrator(): void
    {
        $result = $this->router->route('Rahmat!', 1);

        $this->assertEquals([AgentRouter::AGENT_ORCHESTRATOR], $result['agents']);
    }

    /** @test */
    public function it_routes_kpi_questions_to_analytics(): void
    {
        $result = $this->router->route('Bugungi sotuvlar qanday?', 1);

        $this->assertEquals([AgentRouter::AGENT_ANALYTICS], $result['agents']);
        $this->assertEquals('rule', $result['method']);
    }

    /** @test */
    public function it_routes_report_to_analytics(): void
    {
        $result = $this->router->route('Bu oylik hisobot kerak', 1);

        $this->assertEquals([AgentRouter::AGENT_ANALYTICS], $result['agents']);
    }

    /** @test */
    public function it_routes_lead_questions_to_analytics(): void
    {
        $result = $this->router->route('Leadlar soni nechta?', 1);

        $this->assertEquals([AgentRouter::AGENT_ANALYTICS], $result['agents']);
    }

    /** @test */
    public function it_routes_content_to_marketing(): void
    {
        $result = $this->router->route('Kontent reja tuzing', 1);

        $this->assertEquals([AgentRouter::AGENT_MARKETING], $result['agents']);
    }

    /** @test */
    public function it_routes_instagram_to_marketing(): void
    {
        $result = $this->router->route('Instagram reach pasayib ketdi', 1);

        $this->assertEquals([AgentRouter::AGENT_MARKETING], $result['agents']);
    }

    /** @test */
    public function it_routes_order_to_sales(): void
    {
        $result = $this->router->route('Buyurtma bermoqchiman', 1);

        $this->assertEquals([AgentRouter::AGENT_SALES], $result['agents']);
    }

    /** @test */
    public function it_routes_call_to_call_center(): void
    {
        $result = $this->router->route("Qo'ng'iroqlar reytingi qanday?", 1);

        $this->assertEquals([AgentRouter::AGENT_CALL_CENTER], $result['agents']);
    }

    /** @test */
    public function it_routes_complex_questions_to_multiple_agents(): void
    {
        $result = $this->router->route('Nega sotuvlar tushdi?', 1);

        $this->assertCount(3, $result['agents']);
        $this->assertContains(AgentRouter::AGENT_ANALYTICS, $result['agents']);
        $this->assertContains(AgentRouter::AGENT_SALES, $result['agents']);
        $this->assertContains(AgentRouter::AGENT_MARKETING, $result['agents']);
    }

    /** @test */
    public function it_routes_business_status_to_all_agents(): void
    {
        $result = $this->router->route('Biznes holati qanday?', 1);

        $this->assertCount(4, $result['agents']);
        $this->assertEquals('rule', $result['method']);
    }

    /** @test */
    public function it_routes_short_messages_to_orchestrator(): void
    {
        $result = $this->router->route('Ha', 1);

        $this->assertEquals([AgentRouter::AGENT_ORCHESTRATOR], $result['agents']);
    }
}
