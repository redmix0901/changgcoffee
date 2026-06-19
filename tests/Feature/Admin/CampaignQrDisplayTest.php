<?php

namespace Tests\Feature\Admin;

use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignQrDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['admin_authenticated' => true]);
    }

    public function test_it_shows_the_public_url_and_inline_qr_code_on_the_edit_page(): void
    {
        $campaign = Campaign::factory()->create(['public_token' => 'public-token-123']);

        $this->get(route('admin.campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee(route('play.show', $campaign->public_token), false)
            ->assertSee('<svg', false);
    }
}
