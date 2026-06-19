<?php

namespace Tests\Feature\Play;

use App\Models\Campaign;
use App\Models\CampaignItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlayPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_branded_play_page_for_an_active_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'name' => '2 Years Anniversary',
            'is_active' => true,
        ]);

        CampaignItem::factory()->count(6)->for($campaign)->create();

        $this->get("/play/{$campaign->public_token}")
            ->assertOk()
            ->assertSee('QUAY NGAY!', false)
            ->assertSee('2 YEARS', false)
            ->assertDontSee('play-phone-notch', false)
            ->assertSee('result-ticket__count', false)
            ->assertSee('result-ticket__value--placeholder', false);
    }
}
