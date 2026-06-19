<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignItem;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SpinResultPicker
{
    public function pick(Campaign $campaign): CampaignItem
    {
        return DB::transaction(function () use ($campaign): CampaignItem {
            $campaign = Campaign::query()->with('items')->findOrFail($campaign->id);

            $activeItems = $campaign->items->where('is_active', true)->values();

            if ($activeItems->isEmpty()) {
                throw new HttpException(422, 'This campaign has no active items.');
            }

            if ($activeItems->count() === 1) {
                $selected = $activeItems->first();
            } else {
                $selected = $activeItems
                    ->reject(fn (CampaignItem $item) => $item->id === $campaign->last_result_item_id)
                    ->values()
                    ->random();
            }

            $campaign->forceFill(['last_result_item_id' => $selected->id])->save();

            return $selected;
        });
    }
}
