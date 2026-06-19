<?php

namespace Tests\Feature\Play;

use App\Models\Campaign;
use App\Models\CampaignItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpinResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_spin_does_not_repeat_the_immediately_previous_result_when_two_items_are_active(): void
    {
        $campaign = Campaign::factory()->create(['is_active' => true]);
        $first = CampaignItem::factory()->for($campaign)->create(['label' => 'A', 'sort_order' => 0]);
        CampaignItem::factory()->for($campaign)->create(['label' => 'B', 'sort_order' => 1]);

        $campaign->update(['last_result_item_id' => $first->id]);

        $this->postJson("/play/{$campaign->public_token}/spin")
            ->assertOk()
            ->assertJsonPath('result.label', 'B');
    }

    public function test_spin_returns_the_only_active_item_when_one_option_exists(): void
    {
        $campaign = Campaign::factory()->create(['is_active' => true]);
        CampaignItem::factory()->for($campaign)->create(['label' => 'Only prize']);

        $this->postJson("/play/{$campaign->public_token}/spin")
            ->assertOk()
            ->assertJsonPath('result.label', 'Only prize');
    }
}
