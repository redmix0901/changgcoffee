@extends('layouts.app')

@section('title', 'Admin Login | Spin Wheel')

@section('content')
    <main class="admin-page">
        <section class="admin-card" style="max-width: 28rem; margin: 4rem auto 0;">
            <h1 style="margin-top: 0;">Admin Login</h1>

            @if ($errors->any())
                <div style="color:#9b3f16;font-weight:700;margin-bottom:1rem;">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="/admin/login" class="admin-form-grid">
                @csrf
                <label>
                    <span>Username</span>
                    <input class="admin-input" type="text" name="username" value="{{ old('username') }}">
                </label>

                <label>
                    <span>Password</span>
                    <input class="admin-input" type="password" name="password">
                </label>

                <button class="admin-button" type="submit">Log in</button>
            </form>
        </section>
    </main>
@endsection
