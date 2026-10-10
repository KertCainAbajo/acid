@extends('admin.layout')

@section('admin-content')
@php
    $profileErrors = $errors->getBag('profile');
    $passwordErrors = $errors->getBag('password');
    $eyeIcons = '<svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18.8 18.8 0 0 1-3.1 3.6"/><path d="M6.1 6.1C3.5 7.8 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.1-.8"/></svg><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
@endphp
@include('logs.partials.styles')
<style>
    .st-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; align-items:start; }
    .st-flash { display:flex; align-items:center; gap:10px; padding:12px 16px; border:1px solid #a7f3d0; border-radius:14px; color:#065f46; background:#ecfdf5; font-size:13.5px; font-weight:600; animation:lpRise .3s ease both; }
    .st-flash svg { width:18px; height:18px; flex:none; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .st-avatar { width:64px; height:64px; flex:none; display:grid; place-items:center; border-radius:20px; color:#064e3b; background:linear-gradient(135deg,#a7f3d0,#34d399); box-shadow:0 12px 26px -10px #00000080; font-size:26px; font-weight:800; }
    .st-identity { display:flex; align-items:center; gap:16px; }
    .st-identity h1 { margin:6px 0 4px !important; }
    .lp-hero-stat .st-role { display:inline-flex; width:max-content; margin-top:4px; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; color:#064e3b; background:#6ee7b7; font-size:11px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .st-field { position:relative; margin-bottom:16px; }
    .st-field label { display:block; margin-bottom:7px; color:var(--ink); font-size:13px; font-weight:700; }
    .st-control { position:relative; }
    .st-control > svg { position:absolute; top:50%; left:14px; width:18px; height:18px; color:#94a3b8; transform:translateY(-50%); pointer-events:none; }
    .st-input { width:100%; height:48px; padding:0 14px 0 44px; border:1.5px solid var(--line); border-radius:13px; color:var(--ink); background:#f8fafa; outline:none; font:inherit; font-size:14.5px; transition:border-color .2s ease,box-shadow .2s ease,background .2s ease; }
    .st-input.has-toggle { padding-right:50px; }
    .st-input:focus { border-color:#10b981; background:#fff; box-shadow:0 0 0 4px #d1fae5b3; }
    .st-input[aria-invalid="true"] { border-color:#f87171; background:#fffafa; }
    .st-control:focus-within > svg { color:var(--green); }
    .st-error { margin:7px 0 0 2px; color:#b91c1c; font-size:12.5px; font-weight:600; }
    .st-help { margin:7px 0 0 2px; color:#94a3b8; font-size:12px; }
    .st-toggle { position:absolute; top:50%; right:8px; width:34px; height:34px; display:grid; place-items:center; padding:0; border:0; border-radius:9px; color:#64748b; background:transparent; cursor:pointer; transform:translateY(-50%); }
    .st-toggle:hover { color:var(--green); background:#ecfdf5; }
    .st-toggle svg { position:absolute; width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; transition:opacity .15s ease; }
    .st-toggle .eye-open { opacity:0; } .st-toggle.is-visible .eye-open { opacity:1; } .st-toggle.is-visible .eye-closed { opacity:0; }
    .st-divider { display:flex; align-items:center; gap:10px; margin:6px 0 16px; color:#94a3b8; font-size:11.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .st-divider:after { content:""; flex:1; height:1px; background:#eef2f1; }
    .st-rules { display:grid; grid-template-columns:1fr 1fr; gap:6px 12px; margin:10px 0 0; padding:0; list-style:none; }
    .st-rules li { display:flex; align-items:center; gap:7px; color:#94a3b8; font-size:12px; }
    .st-rules li svg { width:14px; height:14px; flex:none; padding:2px; border-radius:50%; fill:none; stroke:#fff; stroke-width:3.4; stroke-linecap:round; stroke-linejoin:round; background:#e2e8f0; transition:background .2s ease; }
    .st-rules li.is-met { color:#065f46; } .st-rules li.is-met svg { background:#10b981; }
    .st-match { min-height:18px; margin:7px 0 0 2px; font-size:12.5px; font-weight:600; }
    .st-match.is-ok { color:#047857; } .st-match.is-bad { color:#b91c1c; }
    .st-submit { display:inline-flex; align-items:center; gap:8px; height:46px; padding:0 20px; border:0; border-radius:13px; color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); box-shadow:inset 0 1px 0 #ffffff33,0 12px 24px -10px #0a5a40cc; font:inherit; font-size:14px; font-weight:700; cursor:pointer; transition:transform .2s ease; }
    .st-submit:hover { transform:translateY(-1px); }
    .st-submit svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .st-footer { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-top:6px; padding-top:18px; border-top:1px solid #eef2f1; }
    .st-footer small { color:#94a3b8; font-size:12px; }
    .st-tips { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .st-tip { display:flex; gap:12px; padding:14px; border:1px solid #eef2f1; border-radius:14px; background:#fafcfb; }
    .st-tip strong { display:block; font-size:13.5px; }
    .st-tip p { margin:3px 0 0; color:var(--muted); font-size:12.5px; line-height:1.5; }
    @media (max-width:1100px) { .st-grid, .st-tips { grid-template-columns:minmax(0,1fr); } }
    @media (max-width:560px) { .st-rules { grid-template-columns:1fr; } }
</style>

<div class="lp">
    @if (session('status'))
        <div class="st-flash" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>{{ session('status') }}</div>
    @endif

    <section class="lp-hero">
        <div class="st-identity">
            <span class="st-avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <div>
                <span class="lp-crumb"><a href="{{ route('admin.dashboard') }}">Admin</a> / Account settings</span>
                <h1>Account settings</h1>
                <p>Update the name, email and password for <strong style="color:#fff">{{ $user->email }}</strong>.</p>
            </div>
        </div>
        <div class="lp-hero-stats">
            <div class="lp-hero-stat"><span>Role</span><strong class="is-text"><span class="st-role">Admin</span></strong><small>Full console access</small></div>
            <div class="lp-hero-stat"><span>Account created</span><strong class="is-text">{{ $user->created_at->format('M j, Y') }}</strong><small>{{ $user->created_at->diffForHumans() }}</small></div>
            <div class="lp-hero-stat"><span>Password changed</span><strong class="is-text">{{ $passwordChangedAt ? \Illuminate\Support\Carbon::parse($passwordChangedAt)->format('M j, Y') : 'Never' }}</strong><small>{{ $passwordChangedAt ? \Illuminate\Support\Carbon::parse($passwordChangedAt)->diffForHumans() : 'still the setup password' }}</small></div>
        </div>
    </section>

    <div class="st-grid">
        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>Profile</h2><p>Your name and the email you sign in with</p></div><span class="lp-badge-icon lp-tone-green"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span></div>
            <form method="POST" action="{{ route('admin.settings.profile') }}" novalidate>
                @csrf
                @method('PUT')
                <div class="st-field">
                    <label for="name">Full name</label>
                    <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><input class="st-input" id="name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" maxlength="120" required @if ($profileErrors->has('name')) aria-invalid="true" @endif></div>
                    @if ($profileErrors->has('name'))<p class="st-error">{{ $profileErrors->first('name') }}</p>@endif
                </div>
                <div class="st-field">
                    <label for="email">Email address</label>
                    <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><input class="st-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" maxlength="255" required @if ($profileErrors->has('email')) aria-invalid="true" @endif></div>
                    @if ($profileErrors->has('email'))<p class="st-error">{{ $profileErrors->first('email') }}</p>@else<p class="st-help">You will use this email to sign in.</p>@endif
                </div>
                <div class="st-divider">Confirm it's you</div>
                <div class="st-field">
                    <label for="profile_current_password">Current password</label>
                    <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><input class="st-input has-toggle" id="profile_current_password" name="current_password" type="password" autocomplete="current-password" required @if ($profileErrors->has('current_password')) aria-invalid="true" @endif><button class="st-toggle" type="button" data-toggle="profile_current_password" aria-label="Show password">{!! $eyeIcons !!}</button></div>
                    @if ($profileErrors->has('current_password'))<p class="st-error">{{ $profileErrors->first('current_password') }}</p>@endif
                </div>
                <div class="st-footer"><small>Changes are recorded in the activity log.</small><button class="st-submit" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Save profile</button></div>
            </form>
        </section>

        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>Password</h2><p>Changing it signs you out on every other device</p></div><span class="lp-badge-icon lp-tone-amber"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span></div>
            <form method="POST" action="{{ route('admin.settings.password') }}" id="password-form" novalidate>
                @csrf
                @method('PUT')
                <div class="st-field">
                    <label for="current_password">Current password</label>
                    <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><input class="st-input has-toggle" id="current_password" name="current_password" type="password" autocomplete="current-password" required @if ($passwordErrors->has('current_password')) aria-invalid="true" @endif><button class="st-toggle" type="button" data-toggle="current_password" aria-label="Show password">{!! $eyeIcons !!}</button></div>
                    @if ($passwordErrors->has('current_password'))<p class="st-error">{{ $passwordErrors->first('current_password') }}</p>@endif
                </div>
                <div class="st-field">
                    <label for="password">New password</label>
                    <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 0 1 7.8-7.8Zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg><input class="st-input has-toggle" id="password" name="password" type="password" autocomplete="new-password" required @if ($passwordErrors->has('password')) aria-invalid="true" @endif><button class="st-toggle" type="button" data-toggle="password" aria-label="Show password">{!! $eyeIcons !!}</button></div>
                    <ul class="st-rules" id="password-rules">
                        <li data-rule="length"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> At least 10 characters</li>
                        <li data-rule="case"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> Upper &amp; lowercase letters</li>
                        <li data-rule="number"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> At least one number</li>
                        <li data-rule="different"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> Different from current</li>
                    </ul>
                    @if ($passwordErrors->has('password'))<p class="st-error">{{ $passwordErrors->first('password') }}</p>@endif
                </div>
                <div class="st-field">
                    <label for="password_confirmation">Confirm new password</label>
                    <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg><input class="st-input has-toggle" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button class="st-toggle" type="button" data-toggle="password_confirmation" aria-label="Show password">{!! $eyeIcons !!}</button></div>
                    <p class="st-match" id="password-match" role="status"></p>
                </div>
                <div class="st-footer"><small>You stay signed in on this device.</small><button class="st-submit" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> Change password</button></div>
            </form>
        </section>
    </div>

    <section class="lp-panel">
        <div class="lp-panel-head"><div><h2>Keeping the admin account safe</h2><p>This account can see every patient account and all system activity</p></div></div>
        <div class="st-tips">
            <div class="st-tip"><span class="lp-badge-icon lp-tone-green"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 0 1 7.8-7.8Zm0 0L15.5 7.5"/></svg></span><div><strong>Use a unique password</strong><p>Never reuse the setup password or one from another site.</p></div></div>
            <div class="st-tip"><span class="lp-badge-icon lp-tone-sky"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><div><strong>Use a real email</strong><p>Replace placeholder addresses like admin@example.com with one you control.</p></div></div>
            <div class="st-tip"><span class="lp-badge-icon lp-tone-amber"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17h4v3H4v-3Zm6-6h4v9h-4v-9Zm6-7h4v16h-4V4Z"/></svg></span><div><strong>Check the activity log</strong><p><a href="{{ route('admin.activity') }}" style="color:var(--green);font-weight:700">System activity</a> shows every sign-in and account change.</p></div></div>
        </div>
    </section>
</div>

<script>
    (() => {
        document.querySelectorAll('[data-toggle]').forEach((button) => button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.toggle);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.classList.toggle('is-visible', ! showing);
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        }));

        const current = document.getElementById('current_password');
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        const match = document.getElementById('password-match');
        const rules = {
            length: (value) => value.length >= 10,
            case: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value),
            number: (value) => /\d/.test(value),
            different: (value) => value !== '' && value !== current.value,
        };
        const update = () => {
            Object.entries(rules).forEach(([rule, test]) => document.querySelector(`[data-rule="${rule}"]`).classList.toggle('is-met', test(password.value)));
            if (confirmation.value === '') { match.textContent = ''; match.className = 'st-match'; return; }
            const same = confirmation.value === password.value;
            match.textContent = same ? '✓ Passwords match' : 'Passwords do not match yet';
            match.className = `st-match ${same ? 'is-ok' : 'is-bad'}`;
        };
        [current, password, confirmation].forEach((input) => input.addEventListener('input', update));
    })();
</script>
@endsection
