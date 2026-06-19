<?php

namespace Tests\Feature\Admin;

use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['admin_authenticated' => true]);
    }

    public function test_admin_can_create_a_campaign_with_ordered_items(): void
    {
        $response = $this->post('/admin/campaigns', [
            'name' => 'Changg Anniversary',
            'is_active' => '1',
            'items' => [
                ['label' => 'Voucher 10%', 'is_active' => '1'],
                ['label' => 'Free Americano', 'is_active' => '1'],
            ],
        ]);

        $response->assertRedirect();

        $campaign = Campaign::query()->where('name', 'Changg Anniversary')->firstOrFail();

        $this->assertSame(['Voucher 10%', 'Free Americano'], $campaign->items()->pluck('label')->all());
    }
}
