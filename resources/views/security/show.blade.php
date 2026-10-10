@extends('layouts.app')

@section('content')
@php($passwordErrors = $errors->getBag('password'))
@include('admin.partials.ui')
<style>
    .sp-password { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)) auto; gap:10px; align-items:end; }
    .sp-password label { display:grid; gap:6px; color:var(--ink); font-size:12.5px; font-weight:700; }
    @media (max-width:1100px) { .sp-password { grid-template-columns:1fr; } }
</style>

<div class="shell">
    @include('partials.topbar')

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'security'])

        <main class="main lp">
            @if (session('status'))
                <div class="au-flash" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>{{ session('status') }}</div>
            @endif

            <section class="lp-hero">
                <div>
                    <span class="lp-crumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Account security</span>
                    <h1>Account security</h1>
                    <p>Your health records are protected by five layers of security. Turn on two-factor authentication, check where you are signed in, and review your sign-in history.</p>
                </div>
                <div class="lp-hero-stats">
                    <div class="lp-hero-stat"><span>Security score</span><strong>{{ $score }}%</strong><small>{{ $score >= 75 ? 'well protected' : 'room to improve' }}</small></div>
                    <div class="lp-hero-stat"><span>Two-factor</span><strong class="is-text">{{ $twoFactorEnabled ? 'On' : 'Off' }}</strong><small>{{ $twoFactorEnabled ? 'code needed to sign in' : 'recommended' }}</small></div>
                    <div class="lp-hero-stat"><span>Other sessions</span><strong>{{ $otherSessions }}</strong><small>besides this browser</small></div>
                </div>
            </section>

            @include('security.partials.panels')

            <section class="lp-panel" id="password">
                <div class="lp-panel-head"><div><h2>Change password</h2><p>At least 8 characters with letters and numbers. Changing it signs you out on every other device.</p></div></div>
                <form class="sp-password" method="POST" action="{{ route('security.password') }}">
                    @csrf
                    @method('PUT')
                    <label>Current password<input class="au-input" type="password" name="current_password" autocomplete="current-password" required @if ($passwordErrors->has('current_password')) aria-invalid="true" @endif></label>
                    <label>New password<input class="au-input" type="password" name="password" autocomplete="new-password" minlength="8" required @if ($passwordErrors->has('password')) aria-invalid="true" @endif></label>
                    <label>Confirm new password<input class="au-input" type="password" name="password_confirmation" autocomplete="new-password" required></label>
                    <button class="au-btn is-primary" type="submit">Change password</button>
                </form>
                @if ($passwordErrors->any())<p class="au-error">{{ $passwordErrors->first() }}</p>@endif
            </section>
        </main>
    </div>
</div>
@endsection
