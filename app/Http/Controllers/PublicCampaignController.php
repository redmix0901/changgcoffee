<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\SpinResultPicker;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicCampaignController extends Controller
{
    public function show(string $token): View
    {
        $campaign = Campaign::query()
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->where('public_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        return view('play.show', ['campaign' => $campaign]);
    }

    public function spin(string $token, SpinResultPicker $picker): JsonResponse
    {
        $campaign = Campaign::query()
            ->where('public_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $result = $picker->pick($campaign);

        return response()->json([
            'result' => [
                'id' => $result->id,
                'label' => $result->label,
            ],
        ]);
    }
}
