<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(2),
            'slug' => Str::slug(fake()->unique()->sentence(2)),
            'public_token' => Str::random(32),
            'is_active' => false,
            'last_result_item_id' => null,
        ];
    }
}
