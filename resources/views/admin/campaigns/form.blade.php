@extends('layouts.app')

@php
    $initialItems = old('items', $campaign->exists
        ? $campaign->items->map(fn ($item) => ['label' => $item->label, 'is_active' => $item->is_active])->values()->all()
        : [['label' => '', 'is_active' => true]]);
    $publicUrl = $campaign->exists ? route('play.show', $campaign->public_token) : null;
@endphp

@section('title', ($campaign->exists ? 'Edit Campaign' : 'Create Campaign').' | Spin Wheel')

@section('content')
    <main class="admin-page">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">{{ $campaign->exists ? 'Edit Campaign' : 'Create Campaign' }}</h1>
                <p style="margin:0.35rem 0 0;color:#587364;">Update campaign details, active items, and public QR access.</p>
            </div>
            <a class="admin-link-button admin-link-button--muted" href="{{ route('admin.campaigns.index') }}">Back to list</a>
        </div>

        @if (session('status'))
            <div class="admin-card" style="color:#345445;font-weight:700;">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="admin-card" style="color:#9b3f16;font-weight:700;">{{ $errors->first() }}</div>
        @endif

        <div class="admin-grid" style="margin-top:1.5rem; grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr)); align-items:start;">
            <section class="admin-card">
                <form
                    method="post"
                    action="{{ $campaign->exists ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}"
                    class="admin-form-grid"
                    x-data="{ items: {{ Js::from($initialItems) }} }"
                >
                    @csrf
                    @if ($campaign->exists)
                        @method('put')
                    @endif

                    <label>
                        <span>Campaign name</span>
                        <input class="admin-input" type="text" name="name" value="{{ old('name', $campaign->name) }}">
                    </label>

                    <label class="admin-toggle">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $campaign->is_active))>
                        <span>Campaign is active</span>
                    </label>

                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:0.75rem;">
                            <h2 style="margin:0; font-size:1.15rem;">Items</h2>
                            <button
                                class="admin-link-button admin-link-button--muted"
                                type="button"
                                @click="items.push({ label: '', is_active: true })"
                            >
                                Add item
                            </button>
                        </div>

                        <div class="admin-items">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="admin-item-row">
                                    <input class="admin-input" type="text" :name="`items[${index}][label]`" x-model="item.label" placeholder="Item label">
                                    <label class="admin-toggle">
                                        <input type="checkbox" :name="`items[${index}][is_active]`" value="1" x-model="item.is_active">
                                        <span>Active</span>
                                    </label>
                                    <button class="admin-link-button admin-link-button--muted" type="button" @click="items.splice(index, 1)">Remove</button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <button class="admin-button" type="submit">Save campaign</button>
                </form>
            </section>

            @if ($campaign->exists)
                <aside class="admin-card">
                    <h2 style="margin-top:0;">Public Access</h2>
                    <p style="color:#587364;">Use this QR for guests to open the spin screen.</p>
                    <input class="admin-input" type="text" readonly value="{{ $publicUrl }}" onclick="this.select()">
                    <div class="admin-qr" style="margin-top:1rem;">{!! QrCode::size(180)->generate($publicUrl) !!}</div>
                </aside>
            @endif
        </div>
    </main>
@endsection
