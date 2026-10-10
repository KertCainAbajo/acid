@extends('admin.layout')

@section('admin-content')
@php
    $profileErrors = $errors->getBag('profile');
    $passwordErrors = $errors->getBag('password');
    $eyeIcons = '<svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18.8 18.8 0 0 1-3.1 3.6"/><path d="M6.1 6.1C3.5 7.8 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.1-.8"/></svg><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
    $passwordChanged = $passwordChangedAt ? \Illuminate\Support\Carbon::parse($passwordChangedAt) : null;
    $passwordIsStale = ! $passwordChanged || $passwordChanged->lt(now()->subDays(90));
    $heroScoreTone = $score >= 75 ? 'green' : ($score >= 50 ? 'amber' : 'red');
    $checksDone = collect($checks)->where('done', true)->count();
    $sections = [
        ['id' => 'profile', 'label' => 'Profile', 'hint' => 'Name and sign-in email', 'state' => 'ok', 'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
        ['id' => 'password', 'label' => 'Password', 'hint' => $passwordChanged ? 'Changed '.$passwordChanged->diffForHumans() : 'Never changed', 'state' => $passwordIsStale ? 'warn' : 'ok', 'icon' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'],
        ['id' => 'security-score', 'label' => 'Security score', 'hint' => $score.'% protected', 'state' => $score >= 75 ? 'ok' : 'warn', 'icon' => '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/>'],
        ['id' => 'two-factor', 'label' => 'Two-factor', 'hint' => $twoFactorEnabled ? 'On · '.$recoveryCodesLeft.' recovery codes' : ($twoFactorPending ? 'Setup in progress' : 'Required for admins'), 'state' => $twoFactorEnabled ? 'ok' : 'bad', 'icon' => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>'],
        ['id' => 'sessions', 'label' => 'Devices', 'hint' => ($otherSessions + 1).' active session'.($otherSessions ? 's' : ''), 'state' => $otherSessions ? 'warn' : 'ok', 'icon' => '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>'],
        ['id' => 'sign-in-history', 'label' => 'Sign-in history', 'hint' => $history->count().' recent events', 'state' => 'ok', 'icon' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>'],
    ];
@endphp
@include('logs.partials.styles')
<style>
    .lp section[id] { scroll-margin-top:92px; }
    .st-flash { display:flex; align-items:center; gap:10px; padding:12px 16px; border:1px solid #a7f3d0; border-radius:14px; color:#065f46; background:#ecfdf5; font-size:13.5px; font-weight:600; animation:lpRise .3s ease both; }
    .st-flash.is-warn { border-color:#fde68a; color:#92400e; background:#fffbeb; }
    .st-flash svg { width:18px; height:18px; flex:none; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }

    /* Hero */
    .st-hero { grid-template-columns:minmax(0,1fr) auto; }
    .st-identity { display:flex; align-items:center; gap:18px; }
    .st-identity h1 { margin:6px 0 4px !important; }
    .st-avatar { position:relative; width:76px; height:76px; flex:none; display:grid; place-items:center; border-radius:24px; color:#064e3b; background:linear-gradient(135deg,#d1fae5,#34d399 60%,#059669); box-shadow:0 0 0 4px #ffffff14,0 16px 30px -12px #000000a0; font-size:30px; font-weight:800; }
    .st-avatar-badge { position:absolute; right:-6px; bottom:-6px; width:26px; height:26px; display:grid; place-items:center; border:3px solid #0a3229; border-radius:50%; color:#fff; background:var(--badge,#10b981); }
    .st-avatar-badge svg { width:12px; height:12px; fill:none; stroke:currentColor; stroke-width:3; stroke-linecap:round; stroke-linejoin:round; }
    .st-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
    .st-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border:1px solid #ffffff24; border-radius:999px; color:#d1fae5; background:#ffffff10; font-size:12px; font-weight:650; }
    .st-chip i { width:7px; height:7px; border-radius:50%; background:#34d399; box-shadow:0 0 0 3px #34d39933; }
    .st-chip.is-warn i { background:#fbbf24; box-shadow:0 0 0 3px #fbbf2433; }
    .st-chip.is-bad i { background:#f87171; box-shadow:0 0 0 3px #f8717133; }
    .st-chip.is-role { border-color:transparent; color:#064e3b; background:#6ee7b7; font-weight:800; letter-spacing:.06em; text-transform:uppercase; font-size:11px; }
    .st-hero-side { display:flex; align-items:center; gap:18px; padding:16px 18px; border:1px solid #ffffff1f; border-radius:20px; background:#ffffff0d; backdrop-filter:blur(10px); }
    .st-hero-ring { position:relative; width:92px; height:92px; flex:none; }
    .st-hero-ring svg { width:92px; height:92px; transform:rotate(-90deg); }
    .st-hero-ring circle { fill:none; stroke-width:8; }
    .st-hero-ring .track { stroke:#ffffff1a; }
    .st-hero-ring .value { stroke:var(--ring); stroke-linecap:round; stroke-dasharray:100; animation:stRing 1s ease both; }
    .st-hero-ring b { position:absolute; inset:0; display:grid; place-content:center; color:#fff; font-size:22px; font-weight:800; line-height:1; text-align:center; }
    .st-hero-ring b small { display:block; margin-top:3px; color:#a7f3d0; font-size:9.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .st-hero-facts { display:grid; gap:10px; min-width:170px; }
    .st-hero-facts div span { display:block; color:#a7f3d0; font-size:11px; font-weight:600; }
    .st-hero-facts div strong { display:block; color:#fff; font-size:14px; font-weight:750; }
    .st-hero-facts div small { color:#a9cbbf; font-size:11.5px; }
    .st-hero-ring.is-green { --ring:#34d399; } .st-hero-ring.is-amber { --ring:#fbbf24; } .st-hero-ring.is-red { --ring:#f87171; }
    @keyframes stRing { from { stroke-dashoffset:100; } }

    /* Layout */
    .st-layout { display:grid; grid-template-columns:260px minmax(0,1fr); gap:20px; align-items:start; }
    .st-content { display:grid; gap:20px; min-width:0; }
    .st-aside { position:sticky; top:92px; display:grid; gap:14px; }
    .st-nav { padding:10px; border:1px solid #fff; border-radius:22px; background:#ffffffd9; box-shadow:var(--shadow-card); backdrop-filter:blur(10px); }
    .st-nav-title { margin:6px 10px 8px; color:#94a3b8; font-size:10.5px; font-weight:800; letter-spacing:.09em; text-transform:uppercase; }
    .st-nav a { position:relative; display:grid; grid-template-columns:34px minmax(0,1fr) 8px; align-items:center; gap:10px; padding:9px 10px; border-radius:14px; color:var(--ink); text-decoration:none; transition:background .2s ease; }
    .st-nav a:hover { background:#f1f7f4; }
    .st-nav a.is-current { background:#ecfdf5; box-shadow:inset 0 0 0 1px #a7f3d0; }
    .st-nav a.is-current:before { content:""; position:absolute; left:-10px; top:12px; bottom:12px; width:3px; border-radius:0 3px 3px 0; background:var(--green); }
    .st-nav-icon { width:34px; height:34px; display:grid; place-items:center; border-radius:11px; color:#475569; background:#f1f5f4; transition:color .2s ease,background .2s ease; }
    .st-nav a.is-current .st-nav-icon { color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); }
    .st-nav-icon svg { width:17px; height:17px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .st-nav strong { display:block; font-size:13.5px; font-weight:700; }
    .st-nav small { display:block; overflow:hidden; color:var(--muted); font-size:11.5px; text-overflow:ellipsis; white-space:nowrap; }
    .st-dot { width:8px; height:8px; border-radius:50%; background:#10b981; }
    .st-dot.is-warn { background:#f59e0b; } .st-dot.is-bad { background:#ef4444; box-shadow:0 0 0 4px #fee2e2; animation:stPulse 1.8s ease infinite; }
    @keyframes stPulse { 50% { box-shadow:0 0 0 7px #fee2e200; } }
    .st-progress { padding:16px; border-radius:20px; color:#ecfdf5; background:linear-gradient(150deg,#0d4637,#071f1a); box-shadow:0 20px 40px -24px #0a2e26cc; }
    .st-progress strong { display:block; font-size:14px; }
    .st-progress p { margin:4px 0 12px; color:#a9cbbf; font-size:12px; line-height:1.5; }
    .st-progress-bar { height:8px; overflow:hidden; border-radius:99px; background:#ffffff1a; }
    .st-progress-bar span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#34d399,#a7f3d0); animation:lpGrowX .9s ease both; transform-origin:left; }
    .st-progress a { display:inline-flex; margin-top:12px; color:#6ee7b7; font-size:12.5px; font-weight:700; text-decoration:none; }
    .st-progress a:hover { text-decoration:underline; }

    /* Forms */
    .st-profile { display:grid; grid-template-columns:minmax(0,1fr) 250px; gap:22px; align-items:start; }
    .st-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 16px; }
    .st-fields .is-wide { grid-column:1 / -1; }
    .st-field { position:relative; margin-bottom:16px; }
    .st-field label { display:flex; justify-content:space-between; gap:8px; margin-bottom:7px; color:var(--ink); font-size:13px; font-weight:700; }
    .st-field label em { color:#94a3b8; font-size:11.5px; font-style:normal; font-weight:600; }
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
    .st-preview { position:sticky; top:100px; overflow:hidden; border:1px solid #eef2f1; border-radius:20px; background:#fafcfb; }
    .st-preview-cover { height:64px; background:linear-gradient(120deg,#0d4637,#12916a 60%,#6ee7b7); }
    .st-preview-body { padding:0 18px 18px; text-align:center; }
    .st-preview-avatar { width:64px; height:64px; display:grid; place-items:center; margin:-32px auto 10px; border:4px solid #fff; border-radius:20px; color:#064e3b; background:linear-gradient(135deg,#d1fae5,#34d399); font-size:24px; font-weight:800; }
    .st-preview-body strong { display:block; overflow:hidden; color:var(--ink); font-size:15px; text-overflow:ellipsis; white-space:nowrap; }
    .st-preview-body span { display:block; overflow:hidden; margin-top:2px; color:var(--muted); font-size:12.5px; text-overflow:ellipsis; white-space:nowrap; }
    .st-preview-meta { display:grid; gap:7px; margin-top:14px; padding-top:14px; border-top:1px dashed #dbe5e1; text-align:left; }
    .st-preview-meta div { display:flex; justify-content:space-between; gap:8px; color:var(--muted); font-size:12px; }
    .st-preview-meta b { color:var(--ink); font-weight:700; }
    .st-dirty { display:none; align-items:center; gap:6px; margin-top:12px; padding:5px 10px; border-radius:999px; color:#92400e; background:#fef3c7; font-size:11.5px; font-weight:700; }
    .st-dirty.is-visible { display:inline-flex; }
    .st-dirty i { width:6px; height:6px; border-radius:50%; background:#f59e0b; }
    .st-password { display:grid; grid-template-columns:minmax(0,1fr) 250px; gap:22px; align-items:start; }
    .st-strength { margin-top:10px; }
    .st-strength-bars { display:grid; grid-template-columns:repeat(4,1fr); gap:5px; }
    .st-strength-bars span { height:6px; border-radius:99px; background:#e2e8f0; transition:background .25s ease; }
    .st-strength[data-level="1"] span:nth-child(-n+1) { background:#ef4444; }
    .st-strength[data-level="2"] span:nth-child(-n+2) { background:#f59e0b; }
    .st-strength[data-level="3"] span:nth-child(-n+3) { background:#10b981; }
    .st-strength[data-level="4"] span { background:#047857; }
    .st-strength-label { display:flex; justify-content:space-between; margin-top:6px; color:#94a3b8; font-size:11.5px; font-weight:700; }
    .st-strength-label b { color:var(--ink); }
    .st-checklist { padding:16px; border:1px solid #eef2f1; border-radius:18px; background:#fafcfb; }
    .st-checklist h3 { margin:0 0 10px; color:var(--ink); font-size:13px; font-weight:750; }
    .st-rules { display:grid; gap:9px; margin:0; padding:0; list-style:none; }
    .st-rules li { display:flex; align-items:center; gap:8px; color:#94a3b8; font-size:12.5px; }
    .st-rules li svg { width:16px; height:16px; flex:none; padding:2px; border-radius:50%; fill:none; stroke:#fff; stroke-width:3.4; stroke-linecap:round; stroke-linejoin:round; background:#e2e8f0; transition:background .2s ease; }
    .st-rules li.is-met { color:#065f46; } .st-rules li.is-met svg { background:#10b981; }
    .st-generate { display:inline-flex; align-items:center; gap:6px; margin-top:14px; padding:8px 12px; border:1px dashed #a7f3d0; border-radius:11px; color:var(--green-deep,#065f46); background:#f0fdf4; font:inherit; font-size:12.5px; font-weight:700; cursor:pointer; transition:background .2s ease; }
    .st-generate:hover { background:#dcfce7; }
    .st-generate svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .st-match { min-height:18px; margin:7px 0 0 2px; font-size:12.5px; font-weight:600; }
    .st-match.is-ok { color:#047857; } .st-match.is-bad { color:#b91c1c; }
    .st-submit { display:inline-flex; align-items:center; gap:8px; height:46px; padding:0 20px; border:0; border-radius:13px; color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); box-shadow:inset 0 1px 0 #ffffff33,0 12px 24px -10px #0a5a40cc; font:inherit; font-size:14px; font-weight:700; cursor:pointer; transition:transform .2s ease,opacity .2s ease; }
    .st-submit:hover { transform:translateY(-1px); }
    .st-submit svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .st-footer { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-top:6px; padding-top:18px; border-top:1px solid #eef2f1; }
    .st-footer small { display:inline-flex; align-items:center; gap:6px; color:#94a3b8; font-size:12px; }
    .st-footer small svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .st-tips { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .st-tip { display:flex; gap:12px; padding:14px; border:1px solid #eef2f1; border-radius:14px; background:#fafcfb; transition:transform .2s ease,box-shadow .2s ease; }
    .st-tip:hover { transform:translateY(-2px); box-shadow:0 12px 24px -18px #0a2e2666; }
    .st-tip strong { display:block; font-size:13.5px; }
    .st-tip p { margin:3px 0 0; color:var(--muted); font-size:12.5px; line-height:1.5; }
    .st-tip a { color:var(--green); font-weight:700; }

    @media (max-width:1280px) { .st-profile, .st-password { grid-template-columns:minmax(0,1fr); } .st-preview { position:static; } }
    @media (max-width:1100px) { .st-layout { grid-template-columns:minmax(0,1fr); } .st-aside { position:static; } .st-nav { display:flex; gap:6px; overflow-x:auto; scrollbar-width:none; } .st-nav-title { display:none; } .st-nav a { grid-template-columns:30px auto; flex:none; } .st-nav a small, .st-nav .st-dot { display:none; } .st-nav a.is-current:before { display:none; } .st-progress { display:none; } .st-tips { grid-template-columns:minmax(0,1fr); } .st-hero { grid-template-columns:1fr; } }
    @media (max-width:640px) { .st-fields { grid-template-columns:minmax(0,1fr); } .st-hero-side { flex-direction:column; align-items:flex-start; } .st-identity { flex-direction:column; align-items:flex-start; } }
</style>

<div class="lp">
    @if (session('status'))
        <div class="st-flash {{ $twoFactorEnabled ? '' : 'is-warn' }}" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>{{ session('status') }}</div>
    @endif

    <section class="lp-hero st-hero">
        <div>
            <div class="st-identity">
                <span class="st-avatar" aria-hidden="true">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                    <span class="st-avatar-badge" @style(['--badge: '.($twoFactorEnabled ? '#10b981' : '#f59e0b')])>
                        @if ($twoFactorEnabled)<svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg>@else<svg viewBox="0 0 24 24"><path d="M12 7v6M12 17h.01"/></svg>@endif
                    </span>
                </span>
                <div>
                    <span class="lp-crumb"><a href="{{ route('admin.dashboard') }}">Admin</a> / Account settings</span>
                    <h1>Account settings</h1>
                    <p>Signed in as <strong style="color:#fff">{{ $user->name }}</strong> · {{ $user->email }}</p>
                </div>
            </div>
            <div class="st-chips">
                <span class="st-chip is-role">Administrator</span>
                <span class="st-chip {{ $twoFactorEnabled ? '' : 'is-bad' }}"><i></i>Two-factor {{ $twoFactorEnabled ? 'on' : 'off' }}</span>
                <span class="st-chip {{ $passwordIsStale ? 'is-warn' : '' }}"><i></i>{{ $passwordIsStale ? 'Password due for a change' : 'Password up to date' }}</span>
                <span class="st-chip {{ $otherSessions ? 'is-warn' : '' }}"><i></i>{{ $otherSessions + 1 }} active session{{ $otherSessions ? 's' : '' }}</span>
            </div>
        </div>
        <div class="st-hero-side">
            <div class="st-hero-ring is-{{ $heroScoreTone }}" role="img" aria-label="Security score {{ $score }} percent">
                <svg viewBox="0 0 92 92"><circle class="track" cx="46" cy="46" r="38" pathLength="100"/><circle class="value" cx="46" cy="46" r="38" pathLength="100" @style(['stroke-dashoffset: '.(100 - max(2, $score))])/></svg>
                <b>{{ $score }}%<small>Secure</small></b>
            </div>
            <div class="st-hero-facts">
                <div><span>Account created</span><strong>{{ $user->created_at->format('M j, Y') }}</strong><small>{{ $user->created_at->diffForHumans() }}</small></div>
                <div><span>Password changed</span><strong>{{ $passwordChanged ? $passwordChanged->format('M j, Y') : 'Never' }}</strong><small>{{ $passwordChanged ? $passwordChanged->diffForHumans() : 'still the setup password' }}</small></div>
            </div>
        </div>
    </section>

    <div class="st-layout">
        <aside class="st-aside">
            <nav class="st-nav" aria-label="Account settings sections">
                <p class="st-nav-title">Settings</p>
                @foreach ($sections as $section)
                    <a href="#{{ $section['id'] }}" data-section-link="{{ $section['id'] }}" class="{{ $loop->first ? 'is-current' : '' }}">
                        <span class="st-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $section['icon'] !!}</svg></span>
                        <span><strong>{{ $section['label'] }}</strong><small>{{ $section['hint'] }}</small></span>
                        <i class="st-dot {{ $section['state'] === 'ok' ? '' : 'is-'.$section['state'] }}" aria-hidden="true"></i>
                    </a>
                @endforeach
            </nav>
            <div class="st-progress">
                <strong>{{ $checksDone }} of {{ count($checks) }} security steps done</strong>
                <p>{{ $checksDone === count($checks) ? 'Every protection is in place. Nice work.' : 'Finish the remaining steps to fully lock down the admin account.' }}</p>
                <div class="st-progress-bar"><span @style(['width: '.max(4, $score).'%'])></span></div>
                <a href="{{ route('admin.security') }}">Open security center →</a>
            </div>
        </aside>

        <div class="st-content">
            <section class="lp-panel" id="profile">
                <div class="lp-panel-head"><div><h2>Profile</h2><p>Your name and the email you sign in with</p></div><span class="lp-badge-icon lp-tone-green"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span></div>
                <div class="st-profile">
                    <form method="POST" action="{{ route('admin.settings.profile') }}" id="profile-form" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="st-fields">
                            <div class="st-field">
                                <label for="name">Full name</label>
                                <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><input class="st-input" id="name" name="name" value="{{ old('name', $user->name) }}" data-original="{{ $user->name }}" autocomplete="name" maxlength="120" required @if ($profileErrors->has('name')) aria-invalid="true" @endif></div>
                                @if ($profileErrors->has('name'))<p class="st-error">{{ $profileErrors->first('name') }}</p>@endif
                            </div>
                            <div class="st-field">
                                <label for="email">Email address</label>
                                <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><input class="st-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" data-original="{{ $user->email }}" autocomplete="email" maxlength="255" required @if ($profileErrors->has('email')) aria-invalid="true" @endif></div>
                                @if ($profileErrors->has('email'))<p class="st-error">{{ $profileErrors->first('email') }}</p>@else<p class="st-help">You will use this email to sign in.</p>@endif
                            </div>
                            <div class="st-divider is-wide">Confirm it's you</div>
                            <div class="st-field is-wide">
                                <label for="profile_current_password">Current password <em>Required to save changes</em></label>
                                <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><input class="st-input has-toggle" id="profile_current_password" name="current_password" type="password" autocomplete="current-password" required @if ($profileErrors->has('current_password')) aria-invalid="true" @endif><button class="st-toggle" type="button" data-toggle="profile_current_password" aria-label="Show password">{!! $eyeIcons !!}</button></div>
                                @if ($profileErrors->has('current_password'))<p class="st-error">{{ $profileErrors->first('current_password') }}</p>@endif
                            </div>
                        </div>
                        <div class="st-footer"><small><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>Changes are recorded in the activity log.</small><button class="st-submit" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Save profile</button></div>
                    </form>

                    <div class="st-preview" aria-live="polite">
                        <div class="st-preview-cover"></div>
                        <div class="st-preview-body">
                            <div class="st-preview-avatar" id="preview-initial">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            <strong id="preview-name">{{ $user->name }}</strong>
                            <span id="preview-email">{{ $user->email }}</span>
                            <span class="st-dirty" id="profile-dirty"><i></i>Unsaved changes</span>
                            <div class="st-preview-meta">
                                <div>Role <b>Administrator</b></div>
                                <div>Member since <b>{{ $user->created_at->format('M Y') }}</b></div>
                                <div>Idle sign-out <b>{{ $user->idleTimeoutMinutes() }} min</b></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="lp-panel" id="password">
                <div class="lp-panel-head"><div><h2>Password</h2><p>Changing it signs you out on every other device</p></div><span class="lp-badge-icon lp-tone-amber"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span></div>
                <div class="st-password">
                    <form method="POST" action="{{ route('admin.settings.password') }}" id="password-form" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="st-field">
                            <label for="current_password">Current password</label>
                            <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><input class="st-input has-toggle" id="current_password" name="current_password" type="password" autocomplete="current-password" required @if ($passwordErrors->has('current_password')) aria-invalid="true" @endif><button class="st-toggle" type="button" data-toggle="current_password" aria-label="Show password">{!! $eyeIcons !!}</button></div>
                            @if ($passwordErrors->has('current_password'))<p class="st-error">{{ $passwordErrors->first('current_password') }}</p>@endif
                        </div>
                        <div class="st-fields">
                            <div class="st-field">
                                <label for="password">New password</label>
                                <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 0 1 7.8-7.8Zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg><input class="st-input has-toggle" id="password" name="password" type="password" autocomplete="new-password" required @if ($passwordErrors->has('password')) aria-invalid="true" @endif><button class="st-toggle" type="button" data-toggle="password" aria-label="Show password">{!! $eyeIcons !!}</button></div>
                                <div class="st-strength" id="password-strength" data-level="0">
                                    <div class="st-strength-bars" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                                    <div class="st-strength-label"><span>Strength</span><b id="password-strength-label" role="status">Not set</b></div>
                                </div>
                                @if ($passwordErrors->has('password'))<p class="st-error">{{ $passwordErrors->first('password') }}</p>@endif
                            </div>
                            <div class="st-field">
                                <label for="password_confirmation">Confirm new password</label>
                                <div class="st-control"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg><input class="st-input has-toggle" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button class="st-toggle" type="button" data-toggle="password_confirmation" aria-label="Show password">{!! $eyeIcons !!}</button></div>
                                <p class="st-match" id="password-match" role="status"></p>
                            </div>
                        </div>
                        <div class="st-footer"><small><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>You stay signed in on this device.</small><button class="st-submit" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> Change password</button></div>
                    </form>

                    <div class="st-checklist">
                        <h3>Password requirements</h3>
                        <ul class="st-rules" id="password-rules">
                            <li data-rule="length"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> At least 10 characters</li>
                            <li data-rule="case"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> Upper &amp; lowercase letters</li>
                            <li data-rule="number"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> At least one number</li>
                            <li data-rule="different"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> Different from current</li>
                        </ul>
                        <button class="st-generate" type="button" data-generate-password><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/></svg> Suggest a strong password</button>
                    </div>
                </div>
            </section>

            @include('security.partials.panels')

            <section class="lp-panel">
                <div class="lp-panel-head"><div><h2>Keeping the admin account safe</h2><p>This account can see every patient account and all system activity</p></div></div>
                <div class="st-tips">
                    <div class="st-tip"><span class="lp-badge-icon lp-tone-green"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 0 1 7.8-7.8Zm0 0L15.5 7.5"/></svg></span><div><strong>Use a unique password</strong><p>Never reuse the setup password or one from another site.</p></div></div>
                    <div class="st-tip"><span class="lp-badge-icon lp-tone-sky"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><div><strong>Use a real email</strong><p>Replace placeholder addresses like admin@example.com with one you control.</p></div></div>
                    <div class="st-tip"><span class="lp-badge-icon lp-tone-amber"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17h4v3H4v-3Zm6-6h4v9h-4v-9Zm6-7h4v16h-4V4Z"/></svg></span><div><strong>Check the security center</strong><p>The <a href="{{ route('admin.security') }}">security center</a> shows failed sign-ins, locked accounts and suspicious addresses.</p></div></div>
                </div>
            </section>
        </div>
    </div>
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

        const name = document.getElementById('name');
        const email = document.getElementById('email');
        const previewProfile = () => {
            document.getElementById('preview-name').textContent = name.value.trim() || 'Your name';
            document.getElementById('preview-email').textContent = email.value.trim() || 'you@example.com';
            document.getElementById('preview-initial').textContent = (name.value.trim()[0] || '?').toUpperCase();
            const dirty = name.value !== name.dataset.original || email.value !== email.dataset.original;
            document.getElementById('profile-dirty').classList.toggle('is-visible', dirty);
        };
        [name, email].forEach((input) => input.addEventListener('input', previewProfile));
        previewProfile();

        const current = document.getElementById('current_password');
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        const match = document.getElementById('password-match');
        const strength = document.getElementById('password-strength');
        const strengthLabel = document.getElementById('password-strength-label');
        const rules = {
            length: (value) => value.length >= 10,
            case: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value),
            number: (value) => /\d/.test(value),
            different: (value) => value !== '' && value !== current.value,
        };
        const update = () => {
            const value = password.value;
            let met = 0;
            Object.entries(rules).forEach(([rule, test]) => {
                const passed = test(value);
                met += passed ? 1 : 0;
                document.querySelector(`[data-rule="${rule}"]`).classList.toggle('is-met', passed);
            });
            const level = value === '' ? 0 : Math.max(1, Math.min(4, met - 1 + (value.length >= 14 || /[^A-Za-z0-9]/.test(value) ? 1 : 0)));
            strength.dataset.level = level;
            strengthLabel.textContent = ['Not set', 'Weak', 'Fair', 'Good', 'Strong'][level];

            if (confirmation.value === '') { match.textContent = ''; match.className = 'st-match'; return; }
            const same = confirmation.value === value;
            match.textContent = same ? '✓ Passwords match' : 'Passwords do not match yet';
            match.className = `st-match ${same ? 'is-ok' : 'is-bad'}`;
        };
        [current, password, confirmation].forEach((input) => input.addEventListener('input', update));

        document.querySelector('[data-generate-password]').addEventListener('click', () => {
            const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%^&*-_'];
            const all = sets.join('');
            const pick = (chars) => chars[crypto.getRandomValues(new Uint32Array(1))[0] % chars.length];
            const characters = [...sets.map(pick), ...Array.from({ length: 12 }, () => pick(all))];
            for (let index = characters.length - 1; index > 0; index--) {
                const swap = crypto.getRandomValues(new Uint32Array(1))[0] % (index + 1);
                [characters[index], characters[swap]] = [characters[swap], characters[index]];
            }
            password.value = confirmation.value = characters.join('');
            [password, confirmation].forEach((input) => { input.type = 'text'; });
            document.querySelectorAll('[data-toggle="password"], [data-toggle="password_confirmation"]').forEach((button) => { button.classList.add('is-visible'); button.setAttribute('aria-label', 'Hide password'); });
            update();
        });

        const links = [...document.querySelectorAll('[data-section-link]')];
        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                links.forEach((link) => link.classList.toggle('is-current', link.dataset.sectionLink === entry.target.id));
            });
        }, { rootMargin: '-30% 0px -60% 0px' });
        links.forEach((link) => { const target = document.getElementById(link.dataset.sectionLink); if (target) { observer.observe(target); } });
    })();
</script>
@endsection
