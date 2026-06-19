<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        return view('admin.campaigns.index', [
            'campaigns' => Campaign::query()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.campaigns.form', [
            'campaign' => new Campaign(['is_active' => false]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['array'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ]);

        $campaign = Campaign::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(6)),
            'public_token' => Str::random(32),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        collect($data['items'] ?? [])
            ->filter(fn (array $item) => filled($item['label'] ?? null))
            ->values()
            ->each(function (array $item, int $index) use ($campaign): void {
                $campaign->items()->create([
                    'label' => $item['label'],
                    'sort_order' => $index,
                    'is_active' => (bool) ($item['is_active'] ?? false),
                ]);
            });

        return redirect()->route('admin.campaigns.edit', $campaign)->with('status', 'Campaign saved.');
    }

    public function edit(Campaign $campaign): View
    {
        $campaign->load('items');

        return view('admin.campaigns.form', [
            'campaign' => $campaign,
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['array'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ]);

        $campaign->update([
            'name' => $data['name'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $campaign->items()->delete();

        collect($data['items'] ?? [])
            ->filter(fn (array $item) => filled($item['label'] ?? null))
            ->values()
            ->each(function (array $item, int $index) use ($campaign): void {
                $campaign->items()->create([
                    'label' => $item['label'],
                    'sort_order' => $index,
                    'is_active' => (bool) ($item['is_active'] ?? false),
                ]);
            });

        return redirect()->route('admin.campaigns.edit', $campaign)->with('status', 'Campaign saved.');
    }
}
