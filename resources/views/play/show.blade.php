@extends('layouts.app')

@php
    $words = preg_split('/\s+/', trim($campaign->name)) ?: [];
    $heroTitle = strtoupper(implode(' ', array_slice($words, 0, 2)) ?: $campaign->name);
    $heroSubtitle = strtoupper(implode(' ', array_slice($words, 2)) ?: 'ANNIVERSARY');
    $segments = $campaign->items->map(fn ($item) => ['id' => $item->id, 'label' => $item->label])->values();
@endphp

@section('title', $campaign->name.' | Spin Wheel')
@section('meta_description', 'Spin the wheel for '.$campaign->name.'.')

@section('content')
    <div class="page-shell">
        <main class="play-screen">
            <section class="play-hero">
                <img class="play-hero-firework play-hero-firework--left" src="{{ asset('layout-assets/fireworks-left.png') }}" alt="">
                <img class="play-logo" src="{{ asset('layout-assets/changg-logo.png') }}" alt="Changg Cafe Slowbar">
                <div class="play-title-wrap">
                    <div class="play-title-line">
                        <h1 class="play-title">{{ $heroTitle }}</h1>
                        <img class="play-hero-firework play-hero-firework--right" src="{{ asset('layout-assets/fireworks-right.png') }}" alt="">
                    </div>
                    <p class="play-subtitle">{{ $heroSubtitle }}</p>
                </div>
                <img class="play-mascot" src="{{ asset('layout-assets/mascot.png') }}" alt="Campaign mascot">
            </section>

            <section
                class="play-wheel-section"
                x-data="spinWheel(@js($segments), '{{ route('play.spin', $campaign->public_token) }}')"
            >
                <div class="play-spin-stack">
                    <div class="wheel-shell">
                        <img class="wheel-pointer" src="{{ asset('layout-assets/wheel-pointer.png') }}" alt="">
                        <div class="wheel-disc" :style="wheelStyle">
                            <div class="wheel-label-layer">
                                <template x-for="(segment, index) in segments" :key="segment.id">
                                    <div class="wheel-label" :style="labelStyle(index)">
                                        <span x-text="segment.label"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <button class="spin-button" type="button" :disabled="spinning" @click="spin">QUAY NGAY!</button>

                    <p x-show="errorMessage" x-text="errorMessage" style="text-align:center;color:#9b3f16;font-weight:700"></p>
                </div>

                <div class="result-ticket" :class="{ 'result-ticket--revealed': resultLabel, 'result-ticket--idle': !resultLabel }">
                    <div class="result-ticket__inner">
                        <p
                            class="result-ticket__caption"
                            x-html="resultLabel ? 'CHÚC MỪNG BẠN NHẬN ĐƯỢC' : 'Cảm ơn vì đã đến!<br>Cứ quay là có quà, chơi thôi!!'"></p>
                        <div class="result-ticket__body" x-show="resultLabel">
                            <p class="result-ticket__count" x-text="resultLabel ? '01' : ' '"></p>
                            <p
                                class="result-ticket__value"
                                :class="{ 'result-ticket__value--placeholder': !resultLabel }"
                                x-text="resultLabel || ' '"></p>
                        </div>
                    </div>
                </div>

                <div class="celebrate-overlay" x-show="showOverlay" @click="dismissOverlay()" style="display:none;">
                    <canvas class="celebrate-canvas" x-ref="celebCanvas"></canvas>
                    <div class="celebrate-content">
                        <p class="celebrate-congrats">CHÚC MỪNG!</p>
                        <p class="celebrate-prize" x-text="resultLabel"></p>
                        <p class="celebrate-tap">Nhấn để đóng</p>
                    </div>
                </div>
            </section>

            <img class="play-doodle-left" src="{{ asset('layout-assets/doodle-left.png') }}" alt="">
            <img class="play-doodle-right" src="{{ asset('layout-assets/doodle-right.png') }}" alt="">
            <img class="play-wave-lines" src="{{ asset('layout-assets/wave-lines.png') }}" alt="">
        </main>
    </div>
@endsection
