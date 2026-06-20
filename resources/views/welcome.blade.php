@extends('layouts.app')

@php($demoCampaign = $demoCampaign ?? null)

@section('title', 'Spin Wheel Hub')
@section('meta_description', 'Quick launch hub for public spin campaigns and admin access.')

@section('content')
    <div class="home-shell">
        <div class="home-orb home-orb--peach"></div>
        <div class="home-orb home-orb--green"></div>
        <div class="home-grid"></div>

        <main class="home-stage">
            <section class="home-hero-card">
                <p class="home-eyebrow">Spin Wheel Quick Launch</p>
                <h1 class="home-title">Choi thu ngay. Dieu huong nhanh. Len campaign gon.</h1>
                <p class="home-copy">
                    Nhap public token de mo trang quay so ngay lap tuc, hoac di vao admin de tao campaign moi.
                </p>

                <form class="home-launcher" data-play-template="{{ url('/play/__TOKEN__') }}">
                    <label class="home-label" for="public_token">Nhap public token</label>
                    <div class="home-launcher__row">
                        <input
                            class="home-input"
                            id="public_token"
                            name="public_token"
                            type="text"
                            inputmode="text"
                            autocomplete="off"
                            placeholder="vd: summer-campaign-2026"
                        >
                        <button class="home-primary-button" type="submit">Choi thu ngay</button>
                    </div>
                    <p class="home-error" data-token-error aria-live="polite"></p>

                    @if ($demoCampaign)
                        <div class="home-demo-row">
                            <a class="home-demo-link" href="{{ route('play.show', $demoCampaign->public_token) }}">
                                Dung token demo
                            </a>
                            <span class="home-demo-meta">
                                {{ $demoCampaign->name }} / {{ $demoCampaign->public_token }}
                            </span>
                        </div>
                    @endif
                </form>
            </section>

            <section class="home-side-stack">
                <article class="home-shortcut-card home-shortcut-card--admin">
                    <p class="home-card-kicker">Admin</p>
                    <h2>Quan ly campaign</h2>
                    <p>Tao campaign, sua item quay, lay public token va phat hanh link cho nguoi choi.</p>
                    <a class="home-secondary-button" href="{{ url('/admin/login') }}">Vao admin</a>
                </article>

                <article class="home-shortcut-card home-shortcut-card--steps" id="how-it-works">
                    <p class="home-card-kicker">Flow</p>
                    <h2>Cach hoat dong</h2>
                    <ol class="home-steps">
                        <li>Tao campaign trong admin.</li>
                        <li>Lay public token tu campaign dang active.</li>
                        <li>Gui link play cho nguoi choi va mo tu homepage nay khi can demo nhanh.</li>
                    </ol>
                </article>
            </section>

            <section class="home-visual-card" aria-hidden="true">
                <div class="home-visual-badge">Live Demo Gateway</div>
                <div class="home-visual-wheel">
                    <div class="home-visual-wheel__center"></div>
                    <div class="home-visual-pointer"></div>
                </div>
                <div class="home-visual-notes">
                    <span>Public token input</span>
                    <span>One-click demo launch</span>
                    <span>Fast admin handoff</span>
                </div>
                <img class="home-mascot" src="{{ asset('layout-assets/mascot.png') }}" alt="">
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-play-template]');

            if (! form) {
                return;
            }

            const input = form.querySelector('input[name="public_token"]');
            const error = form.querySelector('[data-token-error]');
            const template = form.dataset.playTemplate;

            form.addEventListener('submit', (event) => {
                event.preventDefault();

                const token = input.value.trim();

                if (! token) {
                    error.textContent = 'Nhap token truoc khi mo trang quay.';
                    input.focus();
                    return;
                }

                error.textContent = '';
                window.location.href = template.replace('__TOKEN__', encodeURIComponent(token));
            });
        });
    </script>
@endsection
