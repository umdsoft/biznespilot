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
        $this->user->businesses()->attach($this->business->id, ['role' => 'owner']);
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
     */
    public function test_pipeline_stage_won_status(): void
    {
        $stage = PipelineStage::factory()->won()->forBusiness($this->business)->create();

        $this->assertTrue($stage->is_won);
        $this->assertFalse($stage->is_lost);
        $this->assertEquals('won', $stage->slug);
    }

    /**
     * Test lost stage status.
     */
    public function test_pipeline_stage_lost_status(): void
    {
        $stage = PipelineStage::factory()->lost()->forBusiness($this->business)->create();

        $this->assertTrue($stage->is_lost);
        $this->assertFalse($stage->is_won);
        $this->assertEquals('lost', $stage->slug);
    }

    /**
     * Test pipeline stages are isolated by business.
     */
    public function test_pipeline_stages_are_isolated_by_business(): void
    {
        $business2 = Business::factory()->create();

        PipelineStage::factory()->forBusiness($this->business)->create(['name' => 'Stage 1']);
        PipelineStage::factory()->forBusiness($business2)->create(['name' => 'Stage 2']);

        session(['current_business_id' => $this->business->id]);
        $stages = PipelineStage::all();

        $this->assertCount(1, $stages);
        $this->assertEquals('Stage 1', $stages->first()->name);
    }

    /**
     * Test pipeline stage ordering.
     */
    public function test_pipeline_stage_ordering(): void
    {
        session(['current_business_id' => $this->business->id]);

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
     */
    public function test_lead_linked_to_pipeline_stage(): void
    {
        session(['current_business_id' => $this->business->id]);

        $stage = PipelineStage::create([
            'business_id' => $this->business->id,
            'name' => 'New',
            'slug' => 'new',
            'order' => 1,
        ]);

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
