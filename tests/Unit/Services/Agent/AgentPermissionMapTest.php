<?php

namespace Tests\Unit\Services\Agent;

use App\Services\Agent\Access\AgentPermissionMap;
use PHPUnit\Framework\TestCase;

/**
 * AgentPermissionMap testlari — rol-agent ruxsat matritsasi.
 */
class AgentPermissionMapTest extends TestCase
{
    /** @test */
    public function owner_has_access_to_all_agents(): void
    {
        $agents = AgentPermissionMap::ROLE_AGENT_ACCESS['owner']['agents'];

        $this->assertContains('analytics', $agents);
        $this->assertContains('marketing', $agents);
        $this->assertContains('sales', $agents);
        $this->assertContains('call_center', $agents);
        $this->assertContains('orchestrator', $agents);
    }

    /** @test */
    public function viewer_has_limited_agents(): void
    {
        $agents = AgentPermissionMap::ROLE_AGENT_ACCESS['viewer']['agents'];

        $this->assertContains('analytics', $agents);
        $this->assertContains('orchestrator', $agents);
        $this->assertNotContains('marketing', $agents);
        $this->assertNotContains('sales', $agents);
        $this->assertNotContains('call_center', $agents);
    }

    /** @test */
    public function member_has_daily_question_limit(): void
    {
        $limit = AgentPermissionMap::ROLE_AGENT_ACCESS['member']['max_daily_questions'];

        $this->assertNotNull($limit);
        $this->assertEquals(20, $limit);
    }

    /** @test */
    public function owner_has_no_daily_limit(): void
    {
        $limit = AgentPermissionMap::ROLE_AGENT_ACCESS['owner']['max_daily_questions'];

        $this->assertNull($limit);
    }

    /** @test */
    public function viewer_has_strict_daily_limit(): void
    {
        $limit = AgentPermissionMap::ROLE_AGENT_ACCESS['viewer']['max_daily_questions'];

        $this->assertEquals(5, $limit);
    }

    /** @test */
    public function marketing_department_gets_marketing_agent_boost(): void
    {
        $boost = AgentPermissionMap::DEPARTMENT_AGENT_BOOST['marketing'];

        $this->assertContains('marketing', $boost['extra_agents']);
        $this->assertContains('analytics', $boost['extra_agents']);
    }

    /** @test */
    public function sales_operator_gets_sales_agent_boost(): void
    {
        $boost = AgentPermissionMap::DEPARTMENT_AGENT_BOOST['sales_operator'];

        $this->assertContains('sales', $boost['extra_agents']);
        $this->assertContains('handle_chat', $boost['extra_actions']);
    }

    /** @test */
    public function cash_flow_only_for_owner_and_admin(): void
    {
        $allowed = AgentPermissionMap::MODULE_ACCESS['cash_flow'];

        $this->assertContains('owner', $allowed);
        $this->assertContains('admin', $allowed);
        $this->assertNotContains('manager', $allowed);
        $this->assertNotContains('member', $allowed);
    }

    /** @test */
    public function owner_has_full_data_scope(): void
    {
        $this->assertEquals('all', AgentPermissionMap::DATA_SCOPE['owner']);
    }

    /** @test */
    public function member_has_own_data_scope(): void
    {
        $this->assertEquals('own', AgentPermissionMap::DATA_SCOPE['member']);
    }

    /** @test */
    public function viewer_has_summary_data_scope(): void
    {
        $this->assertEquals('summary', AgentPermissionMap::DATA_SCOPE['viewer']);
    }

    /** @test */
    public function all_roles_have_ask_action(): void
    {
        foreach (AgentPermissionMap::ROLE_AGENT_ACCESS as $role => $config) {
            $this->assertContains('ask', $config['actions'], "Role '{$role}' should have 'ask' action");
        }
    }

    /** @test */
    public function only_owner_and_admin_can_view_usage(): void
    {
        foreach (AgentPermissionMap::ROLE_AGENT_ACCESS as $role => $config) {
            if (in_array($role, ['owner', 'admin'])) {
                $this->assertContains('view_usage', $config['actions'], "Role '{$role}' should have 'view_usage'");
            } else {
                $this->assertNotContains('view_usage', $config['actions'], "Role '{$role}' should NOT have 'view_usage'");
            }
        }
    }
}
