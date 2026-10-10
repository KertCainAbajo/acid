@extends('admin.layout')

@section('admin-content')
@php
    $admin = auth()->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $totalEntries = $foodLogCount + $symptomLogCount + $sleepLogCount;
    $chartMax = max(1, collect($activity)->max(fn (array $day): int => $day['foods'] + $day['symptoms'] + $day['sleep']));
    $delta = function (int $current, int $previous): array {
        if ($previous === 0) {
            return $current > 0 ? ['+'.$current, 'up'] : ['No change', 'flat'];
        }
        $percent = (int) round(($current - $previous) / $previous * 100);

        return [($percent > 0 ? '+' : '').$percent.'%', $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat')];
    };
    [$entriesDelta, $entriesTrend] = $delta($entriesThisWeek, $entriesLastWeek);
    [$patientsDelta, $patientsTrend] = $delta($newPatientsThisWeek, $newPatientsLastWeek);
    $riskTotal = max(1, array_sum($riskDistribution));
    $riskMeta = ['high' => ['High risk', '#dc2626'], 'medium' => ['Medium risk', '#f59e0b'], 'low' => ['Low risk', '#10b981'], 'none' => ['No analysis yet', '#cbd5e1']];
    $offset = 0;
    $segments = [];
    foreach ($riskMeta as $key => [$label, $color]) {
        $share = $riskDistribution[$key] / $riskTotal * 100;
        $segments[] = ['key' => $key, 'label' => $label, 'color' => $color, 'share' => $share, 'offset' => $offset, 'count' => $riskDistribution[$key]];
        $offset += $share;
    }
    $actionMeta = function (string $action): array {
        return match (true) {
            str_starts_with($action, 'user_logged_in') => ['Signed in', 'green', '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>'],
            str_starts_with($action, 'user_logged_out') => ['Signed out', 'slate', '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>'],
            str_starts_with($action, 'user_registered') => ['New account', 'sky', '<circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0M19 8v6M16 11h6"/>'],
            str_starts_with($action, 'food_log') => [str($action)->after('food_log_')->ucfirst().' food entry', 'orange', '<path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/>'],
            str_starts_with($action, 'symptom_log') => [str($action)->after('symptom_log_')->ucfirst().' symptom', 'red', '<path d="M3 12h4l2-5 4 10 2-5h6"/>'],
            str_starts_with($action, 'sleep_log') => [str($action)->after('sleep_log_')->ucfirst().' sleep entry', 'violet', '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>'],
            str_starts_with($action, 'account_') || str_starts_with($action, 'user_account') => [str($action)->replace('_', ' ')->ucfirst(), 'amber', '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'],
            default => [str($action)->replace('_', ' ')->ucfirst(), 'slate', '<circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/>'],
        };
    };
    $riskTone = ['low' => 'green', 'medium' => 'amber', 'high' => 'red'];
@endphp
@include('logs.partials.styles')
<style>
    .lp-tone-violet { --tone:#6d28d9; --tone-soft:#f5f3ff; --tone-mid:#ddd6fe; }
    .lp-tone-slate { --tone:#475569; --tone-soft:#f1f5f9; --tone-mid:#e2e8f0; }
    .ad-hero h1 span { color:transparent; background:linear-gradient(95deg,#6ee7b7,#34d399 60%,#a7f3d0); -webkit-background-clip:text; background-clip:text; }
    .ad-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:16px; }
    .ad-actions a { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; border:1px solid #ffffff26; border-radius:12px; color:#ecfdf5; background:#ffffff14; font-size:13px; font-weight:700; transition:background .2s ease,transform .2s ease; }
    .ad-actions a:hover { background:#ffffff26; transform:translateY(-1px); }
    .ad-actions svg { width:15px; height:15px; fill:none; stroke:#6ee7b7; stroke-width:2.1; stroke-linecap:round; stroke-linejoin:round; }
    .ad-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
    .ad-kpi { display:flex; flex-direction:column; gap:10px; }
    .ad-kpi-head { display:flex; align-items:center; justify-content:space-between; color:#475569; font-size:13px; font-weight:600; }
    .ad-kpi strong { font-size:30px; font-weight:800; line-height:1; letter-spacing:-.04em; }
    .ad-kpi p { margin:0; color:var(--muted); font-size:12.5px; }
    .ad-delta { display:inline-flex; align-items:center; gap:4px; width:max-content; padding:3px 9px; border-radius:999px; font-size:11.5px; font-weight:700; }
    .ad-delta.up { color:#047857; background:#d1fae5; } .ad-delta.down { color:#b91c1c; background:#fee2e2; } .ad-delta.flat { color:#475569; background:#f1f5f9; }
    .ad-meter { height:7px; overflow:hidden; border-radius:999px; background:#eef2f1; }
    .ad-meter i { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#34d399,#0f7a56); transform-origin:left; animation:lpGrowX .9s ease both; }
    .ad-split { display:grid; grid-template-columns:minmax(0,1.6fr) minmax(300px,1fr); gap:20px; align-items:start; }
    .ad-legend { display:flex; flex-wrap:wrap; gap:12px; color:var(--muted); font-size:12px; }
    .ad-legend span { display:inline-flex; align-items:center; gap:6px; }
    .ad-legend i { width:10px; height:10px; border-radius:3px; }
    .ad-chart { display:grid; grid-template-columns:repeat(14,1fr); gap:6px; align-items:end; height:190px; padding:0 2px; background:repeating-linear-gradient(to top,#eef2f1 0 1px,transparent 1px 38px); border-bottom:1px solid #e2e8f0; }
    .ad-col { position:relative; display:flex; flex-direction:column-reverse; justify-content:flex-start; height:100%; border-radius:8px; }
    .ad-col.is-today { background:linear-gradient(to top,#ecfdf5,transparent); }
    .ad-col i { display:block; width:min(26px,70%); margin:0 auto; transform-origin:bottom; animation:lpGrow .8s cubic-bezier(.2,.8,.2,1) both; }
    .ad-col i:first-child { border-radius:0 0 3px 3px; } .ad-col i:last-of-type { border-top-left-radius:6px; border-top-right-radius:6px; }
    .ad-col .is-food { background:#10b981; } .ad-col .is-symptom { background:#f87171; } .ad-col .is-sleep { background:#818cf8; }
    .ad-col .ad-tip { position:absolute; bottom:calc(100% + 6px); left:50%; z-index:5; min-width:150px; padding:9px 11px; border-radius:10px; color:#fff; background:#0b1f2a; font-size:11.5px; line-height:1.5; opacity:0; pointer-events:none; transform:translate(-50%,6px); transition:opacity .18s ease,transform .18s ease; }
    .ad-col:hover .ad-tip { opacity:1; transform:translate(-50%,0); }
    .ad-col:first-child .ad-tip, .ad-col:nth-child(2) .ad-tip { left:0; transform:translate(0,6px); } .ad-col:first-child:hover .ad-tip, .ad-col:nth-child(2):hover .ad-tip { transform:none; }
    .ad-col:last-child .ad-tip, .ad-col:nth-last-child(2) .ad-tip { left:auto; right:0; transform:translate(0,6px); } .ad-col:last-child:hover .ad-tip, .ad-col:nth-last-child(2):hover .ad-tip { transform:none; }
    .ad-days { display:grid; grid-template-columns:repeat(14,1fr); gap:6px; margin-top:8px; color:#94a3b8; font-size:10.5px; font-weight:600; text-align:center; }
    .ad-days .is-today { color:var(--green); }
    .ad-chart-empty { position:absolute; inset:0; display:grid; place-content:center; color:var(--muted); font-size:13px; text-align:center; }
    .ad-donut-wrap { display:flex; align-items:center; gap:20px; }
    .ad-donut { position:relative; width:150px; height:150px; flex:none; }
    .ad-donut svg { width:150px; height:150px; transform:rotate(-90deg); }
    .ad-donut circle { fill:none; stroke-width:16; }
    .ad-donut div { position:absolute; inset:0; display:grid; place-content:center; text-align:center; }
    .ad-donut strong { font-size:28px; font-weight:800; line-height:1; }
    .ad-donut span { color:var(--muted); font-size:11.5px; }
    .ad-risk-list { display:grid; gap:10px; flex:1; }
    .ad-risk-row { display:grid; grid-template-columns:10px minmax(0,1fr) auto; align-items:center; gap:10px; font-size:13px; }
    .ad-risk-row i { width:10px; height:10px; border-radius:3px; }
    .ad-risk-row b { font-size:13px; }
    .ad-risk-row small { color:#94a3b8; font-weight:600; }
    .ad-table { width:100%; border-collapse:collapse; font-size:13px; }
    .ad-table th { padding:0 10px 10px; color:#94a3b8; font-size:11px; font-weight:700; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
    .ad-table td { padding:11px 10px; border-top:1px solid #f1f5f4; vertical-align:middle; }
    .ad-table tr:hover td { background:#fafcfb; }
    .ad-person { display:flex; align-items:center; gap:10px; min-width:0; }
    .ad-avatar { width:36px; height:36px; flex:none; display:grid; place-items:center; border-radius:11px; color:#fff; background:linear-gradient(135deg,#34d399,#0f7a56); font-size:13px; font-weight:800; }
    .ad-person strong { display:block; overflow:hidden; color:var(--ink); font-size:13.5px; text-overflow:ellipsis; white-space:nowrap; }
    .ad-person small { display:block; overflow:hidden; color:var(--muted); font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
    .ad-pill { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:11px; font-weight:700; white-space:nowrap; }
    .ad-pill i { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .ad-counts { display:flex; gap:6px; color:#64748b; font-size:12px; white-space:nowrap; }
    .ad-counts span { display:inline-flex; align-items:center; gap:3px; }
    .ad-counts svg { width:12px; height:12px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .ad-muted { color:#94a3b8; font-size:12px; white-space:nowrap; }
    .ad-feed { display:grid; gap:4px; }
    .ad-feed-row { position:relative; display:grid; grid-template-columns:36px minmax(0,1fr) auto; align-items:center; gap:12px; padding:9px 8px; border-radius:12px; transition:background .2s ease; }
    .ad-feed-row:hover { background:#fafcfb; }
    .ad-feed-row:not(:last-child):after { content:""; position:absolute; left:25px; top:46px; bottom:-6px; width:2px; background:#f1f5f9; }
    .ad-feed-row .lp-badge-icon { position:relative; z-index:1; width:36px; height:36px; border-radius:11px; box-shadow:0 0 0 3px #fff; }
    .ad-feed-row strong { display:block; font-size:13px; }
    .ad-feed-row small { display:block; color:var(--muted); font-size:12px; }
    .ad-feed-row time { color:#94a3b8; font-size:11.5px; white-space:nowrap; }
    .ad-trigger { display:grid; grid-template-columns:28px minmax(0,1fr) auto; align-items:center; gap:12px; padding:10px 12px; border:1px solid #f1f5f4; border-radius:13px; background:#fafcfb; }
    .ad-trigger + .ad-trigger { margin-top:8px; }
    .ad-rank { width:28px; height:28px; display:grid; place-items:center; border-radius:9px; color:#b91c1c; background:#fee2e2; font-size:12px; font-weight:800; }
    .ad-trigger strong { display:block; font-size:13.5px; }
    .ad-trigger small { color:var(--muted); font-size:12px; }
    .ad-bar { height:6px; margin-top:6px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .ad-bar i { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#fb923c,#dc2626); transform-origin:left; animation:lpGrowX .9s ease both; }
    .ad-health { display:grid; gap:10px; }
    .ad-health div { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; border:1px solid #f1f5f4; border-radius:13px; background:#fafcfb; font-size:13px; }
    .ad-health strong { font-size:15px; }
    .ad-link { display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:10px; color:var(--green); background:#ecfdf5; font-size:12.5px; font-weight:700; white-space:nowrap; transition:background .2s ease; }
    .ad-link:hover { background:#d1fae5; }
    @media (max-width:1280px) { .ad-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:1100px) { .ad-split { grid-template-columns:minmax(0,1fr); } }
    @media (max-width:700px) { .ad-kpis { grid-template-columns:minmax(0,1fr); } .ad-donut-wrap { flex-direction:column; align-items:stretch; } .ad-donut { margin:0 auto; } .ad-chart, .ad-days { gap:3px; } .ad-days span:nth-child(odd) { visibility:hidden; } .ad-table .ad-hide-sm { display:none; } }
</style>

<div class="lp">
    <section class="lp-hero ad-hero">
        <div>
            <span class="lp-crumb">Admin console · {{ now()->format('l, F j') }}</span>
            <h1>{{ $greeting }}, <span>{{ explode(' ', trim($admin->name))[0] }}</span></h1>
            <p>Here is how RefluxCare is being used. {{ $activeThisWeek }} of {{ $patientCount }} patient{{ $patientCount === 1 ? '' : 's' }} logged something in the last 7 days.</p>
            <div class="ad-actions">
                <a href="{{ route('admin.patients') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0M17 11h4M19 9v4"/></svg> Patients</a>
                <a href="{{ route('admin.activity') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg> Activity log</a>
                <a href="{{ route('admin.settings') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> Account settings</a>
            </div>
        </div>
        <div class="lp-hero-stats">
            <div class="lp-hero-stat"><span>Active this week</span><strong>{{ $activeThisWeek }}</strong><small>of {{ $patientCount }} patients</small></div>
            <div class="lp-hero-stat"><span>Entries this week</span><strong>{{ number_format($entriesThisWeek) }}</strong><small>{{ $entriesDelta }} vs last week</small></div>
            <div class="lp-hero-stat"><span>Need attention</span><strong>{{ $riskDistribution['high'] }}</strong><small>high-risk patient{{ $riskDistribution['high'] === 1 ? '' : 's' }}</small></div>
        </div>
    </section>

    <section class="ad-kpis" aria-label="Key numbers">
        <article class="lp-panel ad-kpi">
            <div class="ad-kpi-head"><span>Patient accounts</span><span class="lp-badge-icon lp-tone-green"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20a6 6 0 0 1 12 0M14 20a4 4 0 0 1 7 0"/></svg></span></div>
            <strong>{{ number_format($patientCount) }}</strong>
            <div class="ad-meter" title="{{ $activePatientCount }} active accounts"><i @style(['width: '.($patientCount ? $activePatientCount / $patientCount * 100 : 0).'%'])></i></div>
            <p>{{ number_format($activePatientCount) }} active · {{ number_format($patientCount - $activePatientCount) }} deactivated</p>
        </article>
        <article class="lp-panel ad-kpi">
            <div class="ad-kpi-head"><span>New sign-ups</span><span class="lp-badge-icon lp-tone-sky"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0M19 8v6M16 11h6"/></svg></span></div>
            <strong>{{ $newPatientsThisWeek }}</strong>
            <span class="ad-delta {{ $patientsTrend }}">{{ $patientsDelta }} vs last week</span>
            <p>New patient accounts in the last 7 days</p>
        </article>
        <article class="lp-panel ad-kpi">
            <div class="ad-kpi-head"><span>Total entries</span><span class="lp-badge-icon lp-tone-orange"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span></div>
            <strong>{{ number_format($totalEntries) }}</strong>
            <span class="ad-delta {{ $entriesTrend }}">{{ $entriesDelta }} this week</span>
            <p>{{ number_format($foodLogCount) }} food · {{ number_format($symptomLogCount) }} symptom · {{ number_format($sleepLogCount) }} sleep</p>
        </article>
        <article class="lp-panel ad-kpi">
            <div class="ad-kpi-head"><span>High-risk patients</span><span class="lp-badge-icon lp-tone-red"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg></span></div>
            <strong>{{ $riskDistribution['high'] }}</strong>
            <span class="ad-delta {{ $riskDistribution['high'] ? 'down' : 'up' }}">{{ $riskDistribution['high'] ? 'Review recommended' : 'All clear' }}</span>
            <p>Based on each patient's latest analysis</p>
        </article>
    </section>

    <div class="ad-split">
        <section class="lp-panel">
            <div class="lp-panel-head">
                <div><h2>Logging activity</h2><p>Entries per day across all patients · last 14 days</p></div>
                <div class="ad-legend"><span><i style="background:#10b981"></i> Food</span><span><i style="background:#f87171"></i> Symptoms</span><span><i style="background:#818cf8"></i> Sleep</span></div>
            </div>
            <div class="ad-chart" style="position:relative" role="img" aria-label="Entries logged per day over the last 14 days">
                @foreach ($activity as $index => $day)
                    @php($total = $day['foods'] + $day['symptoms'] + $day['sleep'])
                    <div class="ad-col {{ $day['isToday'] ? 'is-today' : '' }}">
                        @if ($day['foods'])<i class="is-food" @style(['height: '.($day['foods'] / $chartMax * 100).'%', 'animation-delay: '.($index * 0.03).'s'])></i>@endif
                        @if ($day['symptoms'])<i class="is-symptom" @style(['height: '.($day['symptoms'] / $chartMax * 100).'%', 'animation-delay: '.($index * 0.03).'s'])></i>@endif
                        @if ($day['sleep'])<i class="is-sleep" @style(['height: '.($day['sleep'] / $chartMax * 100).'%', 'animation-delay: '.($index * 0.03).'s'])></i>@endif
                        <span class="ad-tip"><strong style="display:block">{{ $day['label'] }}, {{ $day['date'] }} · {{ $total }} total</strong>{{ $day['foods'] }} food · {{ $day['symptoms'] }} symptom · {{ $day['sleep'] }} sleep</span>
                    </div>
                @endforeach
                @if ($entriesThisWeek + $entriesLastWeek === 0)
                    <div class="ad-chart-empty"><strong style="color:var(--ink)">No entries in the last 14 days</strong>Activity appears here as patients log.</div>
                @endif
            </div>
            <div class="ad-days">@foreach ($activity as $day)<span class="{{ $day['isToday'] ? 'is-today' : '' }}">{{ $day['isToday'] ? 'Today' : $day['label'] }}</span>@endforeach</div>
        </section>

        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>Patient risk levels</h2><p>From each patient's latest analysis</p></div></div>
            <div class="ad-donut-wrap">
                <div class="ad-donut" role="img" aria-label="Risk level distribution">
                    <svg viewBox="0 0 150 150">
                        <circle cx="75" cy="75" r="58" stroke="#f1f5f9"/>
                        @foreach ($segments as $segment)
                            @if ($segment['count'])
                                <circle cx="75" cy="75" r="58" pathLength="100" stroke="{{ $segment['color'] }}" stroke-dasharray="{{ max(0, $segment['share'] - 0.8) }} {{ 100 - max(0, $segment['share'] - 0.8) }}" stroke-dashoffset="{{ -$segment['offset'] }}"/>
                            @endif
                        @endforeach
                    </svg>
                    <div><strong>{{ $patientCount }}</strong><span>patients</span></div>
                </div>
                <div class="ad-risk-list">
                    @foreach ($segments as $segment)
                        <div class="ad-risk-row"><i @style(['background: '.$segment['color']])></i><span>{{ $segment['label'] }}</span><b>{{ $segment['count'] }} <small>· {{ (int) round($segment['share']) }}%</small></b></div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <div class="ad-split">
        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>Newest patients</h2><p>The latest accounts and how much they have logged</p></div><a class="ad-link" href="{{ route('admin.patients') }}">View all →</a></div>
            @if ($recentPatients->isEmpty())
                <div class="lp-empty"><strong>No patients yet</strong>New patient accounts will appear here.</div>
            @else
                <div style="overflow-x:auto">
                    <table class="ad-table">
                        <thead><tr><th>Patient</th><th class="ad-hide-sm">Entries</th><th>Risk</th><th class="ad-hide-sm">Last active</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($recentPatients as $patient)
                                @php($lastActive = collect([$patient->food_logs_max_time, $patient->symptom_logs_max_time])->filter()->max())
                                @php($risk = $patientRisk->get($patient->id))
                                <tr>
                                    <td><div class="ad-person"><span class="ad-avatar">{{ strtoupper(substr($patient->name, 0, 1)) }}</span><div><strong>{{ $patient->name }}</strong><small>{{ $patient->email }}</small></div></div></td>
                                    <td class="ad-hide-sm"><div class="ad-counts" title="Food · Symptoms · Sleep">
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/></svg>{{ $patient->food_logs_count }}</span>
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg>{{ $patient->symptom_logs_count }}</span>
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>{{ $patient->sleep_logs_count }}</span>
                                    </div></td>
                                    <td>@if ($risk)<span class="ad-pill lp-tone-{{ $riskTone[$risk] ?? 'slate' }}"><i></i>{{ ucfirst($risk) }}</span>@else<span class="ad-muted">—</span>@endif</td>
                                    <td class="ad-hide-sm"><span class="ad-muted">{{ $lastActive ? \Illuminate\Support\Carbon::parse($lastActive)->diffForHumans() : 'Never' }}</span></td>
                                    <td><span class="ad-pill lp-tone-{{ $patient->is_active ? 'green' : 'red' }}"><i></i>{{ $patient->is_active ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>Recent activity</h2><p>Sign-ins, new accounts and changes</p></div><a class="ad-link" href="{{ route('admin.activity') }}">View all →</a></div>
            @if ($recentActivity->isEmpty())
                <div class="lp-empty"><strong>No activity yet</strong>Events will appear here as they happen.</div>
            @else
                <div class="ad-feed">
                    @foreach ($recentActivity as $event)
                        @php([$label, $tone, $icon] = $actionMeta($event->action))
                        <div class="ad-feed-row lp-tone-{{ $tone }}">
                            <span class="lp-badge-icon"><svg class="lp-svg" style="width:16px;height:16px" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span>
                            <div><strong>{{ $label }}</strong><small>{{ $event->actor?->name ?? 'System' }}{{ $event->actor?->role === 'admin' ? ' · admin' : '' }}</small></div>
                            <time datetime="{{ $event->created_at->toIso8601String() }}" title="{{ $event->created_at->format('M j, Y g:i A') }}">{{ $event->created_at->diffForHumans(null, true) }} ago</time>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="ad-split">
        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>Most common triggers</h2><p>Foods most often followed by symptoms across all patients · last 30 days · anonymised</p></div></div>
            @if ($commonTriggers->isEmpty())
                <div class="lp-empty"><strong>No triggers detected yet</strong>Common triggers appear once patients log meals and symptoms.</div>
            @else
                @php($topTrigger = max(1, $commonTriggers->max('associations')))
                @foreach ($commonTriggers as $trigger)
                    <div class="ad-trigger">
                        <span class="ad-rank">{{ $loop->iteration }}</span>
                        <div><strong>{{ $trigger['food'] }}</strong><div class="ad-bar"><i @style(['width: '.($trigger['associations'] / $topTrigger * 100).'%'])></i></div></div>
                        <small>{{ $trigger['associations'] }} symptom{{ $trigger['associations'] === 1 ? '' : 's' }} · {{ $trigger['patients'] }} patient{{ $trigger['patients'] === 1 ? '' : 's' }}</small>
                    </div>
                @endforeach
            @endif
        </section>

        <section class="lp-panel">
            <div class="lp-panel-head"><div><h2>System at a glance</h2><p>Configuration and data health</p></div></div>
            <div class="ad-health">
                <div><span>Food categories (guidance)</span><strong>{{ $categoryCount }}</strong></div>
                <div><span>Patients with an analysis</span><strong>{{ $patientCount - $riskDistribution['none'] }} / {{ $patientCount }}</strong></div>
                <div><span>Weekly engagement</span><strong>{{ $patientCount ? (int) round($activeThisWeek / $patientCount * 100) : 0 }}%</strong></div>
                <div><span>Deactivated accounts</span><strong>{{ $patientCount - $activePatientCount }}</strong></div>
            </div>
        </section>
    </div>
</div>
@endsection
