<?php

namespace Tests\Feature;

use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_shows_quick_launch_actions(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Choi thu ngay', false)
            ->assertSee('Nhap public token', false)
            ->assertSee('/admin/login', false)
            ->assertDontSee('Dung token demo', false);
    }

    public function test_the_homepage_shows_a_demo_link_when_an_active_campaign_exists(): void
    {
        $campaign = Campaign::factory()->create([
            'name' => 'Summer Blast',
            'public_token' => 'demo-campaign-token',
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Dung token demo', false)
            ->assertSee($campaign->public_token, false)
            ->assertSee(route('play.show', $campaign->public_token), false);
    }
}
