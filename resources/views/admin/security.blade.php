@extends('admin.layout')

@section('admin-content')
@include('admin.partials.ui')
@php
    $threatLevel = match (true) {
        $suspiciousIps->isNotEmpty() || $lockedAccounts->isNotEmpty() => ['Elevated', 'red', 'Repeated failed sign-ins were detected. Review the addresses and accounts below.'],
        $failedToday >= 3 => ['Watch', 'amber', 'A few failed sign-ins in the last 24 hours. Nothing is locked yet.'],
        default => ['Normal', 'green', 'No signs of attack in the last 24 hours.'],
    };
    $patientCoverage = $twoFactor['patientTotal'] ? (int) round($twoFactor['patients'] / $twoFactor['patientTotal'] * 100) : 0;
    $layers = [
        ['Strong sign-in', 'Rate limits, lockout after '.\App\Services\LoginGuard::MAX_ATTEMPTS.' failures for '.\App\Services\LoginGuard::LOCK_MINUTES.' min, strong passwords', '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'],
        ['Two-factor authentication', 'Authenticator codes: required for admins, optional for patients', '<rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/>'],
        ['Session protection', 'Idle sign-out (admin 15 min, patient 60 min), browser binding, expiring tokens', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        ['HTTP hardening', 'Content Security Policy, no framing, nosniff, strict referrer, no caching of private pages', '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/>'],
        ['Monitoring & audit', 'Every sign-in, failure, lock and change recorded and reviewed here', '<path d="M3 12h4l2-5 4 10 2-5h6"/>'],
    ];
@endphp
<style>
    .sx-threat { display:flex; flex-direction:column; gap:10px; min-width:min(340px,100%); padding:16px; border:1px solid #ffffff1f; border-radius:18px; background:#ffffff0f; backdrop-filter:blur(10px); }
    .sx-threat span { color:#a7f3d0; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .sx-threat strong { display:flex; align-items:center; gap:10px; color:#fff; font-size:26px; letter-spacing:-.02em; }
    .sx-threat strong i { width:12px; height:12px; border-radius:50%; background:var(--tone); box-shadow:0 0 0 5px color-mix(in srgb,var(--tone) 30%,transparent); }
    .sx-threat p { margin:0 !important; color:#b9d6cc !important; font-size:13px !important; }
    .sx-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .sx-kpi { display:flex; align-items:center; gap:12px; padding:14px; border:1px solid #fff; border-radius:18px; background:#ffffffd9; box-shadow:var(--shadow-soft); }
    .sx-kpi .lp-badge-icon { width:42px; height:42px; border-radius:13px; }
    .sx-kpi strong { display:block; font-size:24px; font-weight:800; line-height:1.1; letter-spacing:-.03em; }
    .sx-kpi div > span { display:block; color:#334155; font-size:13px; font-weight:700; }
    .sx-kpi small { display:block; color:#94a3b8; font-size:11.5px; }
    .sx-layers { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; }
    .sx-layer { position:relative; padding:16px; border:1px solid #d1fae5; border-radius:16px; background:linear-gradient(160deg,#f0fdf4,#fff); }
    .sx-layer b { display:inline-flex; align-items:center; gap:6px; color:var(--green); font-size:11px; letter-spacing:.08em; text-transform:uppercase; }
    .sx-layer b:after { content:"Active"; padding:1px 7px; border-radius:999px; color:#065f46; background:#d1fae5; letter-spacing:0; text-transform:none; }
    .sx-layer .lp-badge-icon { width:36px; height:36px; margin:10px 0 8px; border-radius:11px; }
    .sx-layer strong { display:block; font-size:14px; }
    .sx-layer p { margin:4px 0 0; color:var(--muted); font-size:12px; line-height:1.5; }
    .sx-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; align-items:start; }
    .sx-row { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:10px; padding:11px 12px; border:1px solid #f1f5f4; border-radius:13px; background:#fafcfb; }
    .sx-row + .sx-row { margin-top:8px; }
    .sx-row strong { display:block; overflow:hidden; font-size:13.5px; text-overflow:ellipsis; white-space:nowrap; }
    .sx-row small { display:block; color:var(--muted); font-size:12px; }
    .sx-row form { margin:0; }
    .sx-coverage { display:grid; gap:14px; }
    .sx-coverage-row span { display:flex; justify-content:space-between; color:#334155; font-size:13px; font-weight:600; }
    .sx-bar { height:8px; margin-top:6px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .sx-bar i { display:block; height:100%; border-radius:inherit; background:var(--tone); }
    .sx-event { display:grid; grid-template-columns:34px minmax(0,1fr) auto; align-items:center; gap:10px; padding:8px; border-radius:11px; }
    .sx-event:hover { background:#fafcfb; }
    .sx-event .lp-badge-icon { width:34px; height:34px; border-radius:10px; }
    .sx-event strong { font-size:13px; }
    .sx-event small { display:block; overflow:hidden; color:var(--muted); font-size:11.5px; text-overflow:ellipsis; white-space:nowrap; }
    .sx-event time { color:#94a3b8; font-size:11.5px; white-space:nowrap; }
    @media (max-width:1280px) { .sx-layers { grid-template-columns:repeat(3,minmax(0,1fr)); } .sx-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:900px) { .sx-grid, .sx-layers, .sx-kpis { grid-template-columns:minmax(0,1fr); } }
</style>

<div class="lp">
    @if (session('status'))
        <div class="au-flash" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>{{ session('status') }}</div>
    @endif

    <section class="lp-hero">
        <div>
            <span class="lp-crumb"><a href="{{ route('admin.dashboard') }}">Admin</a> / Security center</span>
            <h1>Security center</h1>
            <p>Five layers protect every admin and patient account. Watch for attacks, unlock accounts after a lockout, and track two-factor adoption.</p>
        </div>
        <div class="sx-threat lp-tone-{{ $threatLevel[1] }}">
            <span>Threat level · last 24 hours</span>
            <strong><i></i>{{ $threatLevel[0] }}</strong>
            <p>{{ $threatLevel[2] }}</p>
        </div>
    </section>

    <section class="sx-kpis">
        <div class="sx-kpi lp-tone-{{ $failedToday ? 'red' : 'green' }}"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg></span><div><strong>{{ $failedToday }}</strong><span>Failed sign-ins</span><small>{{ $failedWeek }} in the last 7 days</small></div></div>
        <div class="sx-kpi lp-tone-{{ $lockedAccounts->isNotEmpty() ? 'red' : 'green' }}"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span><div><strong>{{ $lockedAccounts->count() }}</strong><span>Locked right now</span><small>{{ $locksThisWeek }} lockouts this week</small></div></div>
        <div class="sx-kpi lp-tone-{{ $twoFactor['admins'] === $twoFactor['adminTotal'] ? 'green' : 'amber' }}"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg></span><div><strong>{{ $twoFactor['admins'] }}/{{ $twoFactor['adminTotal'] }}</strong><span>Admins with 2FA</span><small>required for every admin</small></div></div>
        <div class="sx-kpi lp-tone-sky"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg></span><div><strong>{{ $newDevicesWeek }}</strong><span>New devices</span><small>first-time sign-ins · 7 days</small></div></div>
    </section>

    <section class="lp-panel">
        <div class="lp-panel-head"><div><h2>Five layers of protection</h2><p>Applied to both administrator and patient accounts</p></div></div>
        <div class="sx-layers">
            @foreach ($layers as $index => [$name, $description, $icon])
                <div class="sx-layer"><b>Layer {{ $index + 1 }}</b><span class="lp-badge-icon lp-tone-green"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span><strong>{{ $name }}</strong><p>{{ $description }}</p></div>
            @endforeach
        </div>
    </section>

    <div class="sx-grid">
        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>Locked accounts</h2><p>Locked for {{ \App\Services\LoginGuard::LOCK_MINUTES }} minutes after {{ \App\Services\LoginGuard::MAX_ATTEMPTS }} failed attempts</p></div></div>
            @forelse ($lockedAccounts as $account)
                <div class="sx-row">
                    <div><strong>{{ $account->name }} · {{ $account->email }}</strong><small>{{ ucfirst($account->role) }} · unlocks {{ $account->locked_until->diffForHumans() }}</small></div>
                    <form method="POST" action="{{ route('admin.security.unlock', $account) }}">@csrf<button class="au-btn is-sm is-primary" type="submit">Unlock now</button></form>
                </div>
            @empty
                <div class="lp-empty"><strong>No locked accounts</strong>Accounts appear here after repeated failed sign-ins.</div>
            @endforelse

            <h3 style="margin:20px 0 10px;font-size:14px">Suspicious addresses · 24 hours</h3>
            @forelse ($suspiciousIps as $row)
                <div class="sx-row">
                    <div><strong>{{ $row['ip'] }}</strong><small>{{ $row['failures'] }} failed sign-ins against {{ $row['accounts'] }} account{{ $row['accounts'] === 1 ? '' : 's' }} · last {{ $row['last']->diffForHumans() }}</small></div>
                    <a class="au-btn is-sm" href="{{ route('admin.activity', ['q' => 'login_failed', 'group' => 'security']) }}">Review</a>
                </div>
            @empty
                <p class="lp-hint" style="margin:0">No address has {{ \App\Http\Controllers\AdminSecurityController::SUSPICIOUS_FAILURES }}+ failed sign-ins in the last 24 hours.</p>
            @endforelse
        </section>

        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>Two-factor coverage</h2><p>Who has the second layer of sign-in protection</p></div></div>
            <div class="sx-coverage">
                <div class="sx-coverage-row lp-tone-{{ $twoFactor['admins'] === $twoFactor['adminTotal'] ? 'green' : 'red' }}"><span>Administrators <b>{{ $twoFactor['admins'] }} of {{ $twoFactor['adminTotal'] }}</b></span><div class="sx-bar"><i @style(['width: '.($twoFactor['adminTotal'] ? $twoFactor['admins'] / $twoFactor['adminTotal'] * 100 : 0).'%'])></i></div></div>
                <div class="sx-coverage-row lp-tone-sky"><span>Patients <b>{{ $twoFactor['patients'] }} of {{ $twoFactor['patientTotal'] }} · {{ $patientCoverage }}%</b></span><div class="sx-bar"><i @style(['width: '.$patientCoverage.'%'])></i></div></div>
            </div>
            <h3 style="margin:20px 0 10px;font-size:14px">Most targeted sign-ins · 7 days</h3>
            @forelse ($targetedAccounts as $row)
                <div class="sx-row"><div><strong>{{ $row['email'] }}</strong><small>{{ $row['exists'] ? 'Existing account' : 'No account with this email' }}</small></div><span class="au-pill lp-tone-{{ $row['failures'] >= 5 ? 'red' : 'amber' }}"><i></i>{{ $row['failures'] }} failed</span></div>
            @empty
                <p class="lp-hint" style="margin:0">No failed sign-ins in the last 7 days.</p>
            @endforelse
        </section>
    </div>

    <section class="lp-panel">
        <div class="lp-panel-head"><div><h2>Recent security events</h2><p>Failures, lockouts, new devices, two-factor and password changes</p></div><a class="au-btn is-sm" href="{{ route('admin.activity', ['group' => 'security']) }}">Full security log →</a></div>
        @forelse ($events as $event)
            @php(['label' => $label, 'tone' => $tone, 'icon' => $icon] = \App\Support\ActivityPresenter::describe($event->action))
            <div class="sx-event lp-tone-{{ $tone }}">
                <span class="lp-badge-icon"><svg class="lp-svg" style="width:15px;height:15px" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span>
                <div><strong>{{ $label }}</strong><small>{{ collect([$event->actor?->name ?? ($event->metadata['email'] ?? null), $event->metadata['browser'] ?? null, $event->ip_address])->filter()->implode(' · ') ?: '—' }}</small></div>
                <time title="{{ $event->created_at->format('M j, Y g:i:s A') }}">{{ $event->created_at->diffForHumans() }}</time>
            </div>
        @empty
            <p class="lp-hint" style="margin:0">No security events yet.</p>
        @endforelse
    </section>
</div>
@endsection
