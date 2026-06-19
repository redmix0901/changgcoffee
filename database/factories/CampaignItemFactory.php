<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignItem>
 */
class CampaignItemFactory extends Factory
{
    protected $model = CampaignItem::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'label' => fake()->words(2, true),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
