@extends('layouts.app')

@section('title', 'Campaigns | Spin Wheel')

@section('content')
    <main class="admin-page">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">Campaigns</h1>
                <p style="margin:0.35rem 0 0;color:#587364;">Manage public spin campaigns and their QR links.</p>
            </div>
            <a class="admin-link-button" href="{{ route('admin.campaigns.create') }}">Create campaign</a>
        </div>

        <section class="admin-grid" style="margin-top: 1.5rem;">
            @foreach ($campaigns as $campaign)
                <article class="admin-card">
                    <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;">
                        <div>
                            <h2 style="margin:0;">{{ $campaign->name }}</h2>
                            <p style="margin:0.35rem 0 0;color:#587364;">{{ $campaign->is_active ? 'Active' : 'Draft' }}</p>
                        </div>
                        <a class="admin-link-button admin-link-button--muted" href="{{ route('admin.campaigns.edit', $campaign) }}">Edit</a>
                    </div>
                </article>
            @endforeach
        </section>
    </main>
@endsection
