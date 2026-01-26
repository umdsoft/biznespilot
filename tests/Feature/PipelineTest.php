<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineTest extends TestCase
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
     * Test pipeline stage creation.
     */
    public function test_pipeline_stage_can_be_created(): void
    {
        session(['current_business_id' => $this->business->id]);

        $stage = PipelineStage::create([
            'business_id' => $this->business->id,
            'name' => 'Test Stage',
            'slug' => 'test-stage',
            'order' => 1,
        ]);

        $this->assertNotNull($stage->id);
        $this->assertEquals('Test Stage', $stage->name);
        $this->assertEquals($this->business->id, $stage->business_id);
    }

    /**
     * Test pipeline stage factory.
     */
    public function test_pipeline_stage_factory_creates_valid_stage(): void
    {
        $stage = PipelineStage::factory()->forBusiness($this->business)->create();

        $this->assertNotNull($stage->id);
        $this->assertNotNull($stage->name);
        $this->assertNotNull($stage->slug);
        $this->assertEquals($this->business->id, $stage->business_id);
    }

    /**
     * Test won stage status.
     * Note: Business auto-creates default stages including 'won', so we verify the existing one.
     */
    public function test_pipeline_stage_won_status(): void
    {
        session(['current_business_id' => $this->business->id]);

        // Get the auto-created 'won' stage
        $stage = PipelineStage::where('business_id', $this->business->id)
            ->where('slug', 'won')
            ->first();

        $this->assertNotNull($stage, 'Default won stage should exist');
        $this->assertTrue($stage->is_won);
        $this->assertFalse($stage->is_lost);
        $this->assertEquals('won', $stage->slug);
    }

    /**
     * Test lost stage status.
     * Note: Business auto-creates default stages including 'lost', so we verify the existing one.
     */
    public function test_pipeline_stage_lost_status(): void
    {
        session(['current_business_id' => $this->business->id]);

        // Get the auto-created 'lost' stage
        $stage = PipelineStage::where('business_id', $this->business->id)
            ->where('slug', 'lost')
            ->first();

        $this->assertNotNull($stage, 'Default lost stage should exist');
        $this->assertTrue($stage->is_lost);
        $this->assertFalse($stage->is_won);
        $this->assertEquals('lost', $stage->slug);
    }

    /**
     * Test pipeline stages are isolated by business.
     * Note: Business auto-creates 3 default stages (new, won, lost).
     */
    public function test_pipeline_stages_are_isolated_by_business(): void
    {
        $business2 = Business::factory()->create();

        PipelineStage::factory()->forBusiness($this->business)->create(['name' => 'Stage 1', 'slug' => 'stage-1']);
        PipelineStage::factory()->forBusiness($business2)->create(['name' => 'Stage 2', 'slug' => 'stage-2']);

        session(['current_business_id' => $this->business->id]);
        $stages = PipelineStage::all();

        // 3 default stages + 1 custom = 4 for this business
        $this->assertCount(4, $stages);
        $this->assertTrue($stages->contains('name', 'Stage 1'));
        $this->assertFalse($stages->contains('name', 'Stage 2'));
    }

    /**
     * Test pipeline stage ordering.
     * Note: Business auto-creates 3 default stages with order 1, 100, 101.
     * We test ordering with custom stages in the middle range.
     */
    public function test_pipeline_stage_ordering(): void
    {
        session(['current_business_id' => $this->business->id]);

        // Delete default stages to test pure ordering
        PipelineStage::where('business_id', $this->business->id)->delete();

        PipelineStage::create([
            'business_id' => $this->business->id,
            'name' => 'Third',
            'slug' => 'third',
            'order' => 3,
        ]);

        PipelineStage::create([
            'business_id' => $this->business->id,
            'name' => 'First',
            'slug' => 'first',
            'order' => 1,
        ]);

        PipelineStage::create([
            'business_id' => $this->business->id,
            'name' => 'Second',
            'slug' => 'second',
            'order' => 2,
        ]);

        $stages = PipelineStage::orderBy('order')->get();

        $this->assertEquals('First', $stages[0]->name);
        $this->assertEquals('Second', $stages[1]->name);
        $this->assertEquals('Third', $stages[2]->name);
    }

    /**
     * Test lead can be linked to pipeline stage.
     * Note: Business auto-creates a 'new' stage by default.
     */
    public function test_lead_linked_to_pipeline_stage(): void
    {
        session(['current_business_id' => $this->business->id]);

        // Get the auto-created 'new' stage
        $stage = PipelineStage::where('business_id', $this->business->id)
            ->where('slug', 'new')
            ->first();

        $this->assertNotNull($stage, 'Default new stage should exist');

        $lead = Lead::factory()->forBusiness($this->business)->create([
            'status' => 'new',
        ]);

        $this->assertEquals('new', $lead->status);
        $this->assertEquals($stage->slug, $lead->status);
    }

    /**
     * Test pipeline stage color.
     */
    public function test_pipeline_stage_has_color(): void
    {
        $stage = PipelineStage::factory()->forBusiness($this->business)->create([
            'color' => '#ff5733',
        ]);

        $this->assertEquals('#ff5733', $stage->color);
    }

    /**
     * Test pipeline stage update.
     */
    public function test_pipeline_stage_can_be_updated(): void
    {
        session(['current_business_id' => $this->business->id]);

        $stage = PipelineStage::factory()->forBusiness($this->business)->create();

        $stage->update([
            'name' => 'Updated Stage',
            'color' => '#00ff00',
        ]);

        $this->assertEquals('Updated Stage', $stage->fresh()->name);
        $this->assertEquals('#00ff00', $stage->fresh()->color);
    }

    /**
     * Test pipeline stage delete.
     */
    public function test_pipeline_stage_can_be_deleted(): void
    {
        session(['current_business_id' => $this->business->id]);

        $stage = PipelineStage::factory()->forBusiness($this->business)->create();
        $stageId = $stage->id;

        $stage->delete();

        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stageId]);
    }
}
