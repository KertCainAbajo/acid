@extends('admin.layout')

@section('admin-content')
@include('admin.partials.ui')
@php
    $deleteErrors = $errors->getBag('delete');
    $segments = [
        'all' => ['All patients', $totals['all'], 'slate', 'Every registered account', '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20a6 6 0 0 1 12 0M14 20a4 4 0 0 1 7 0"/>'],
        'active' => ['Active', $totals['active'], 'green', 'Can sign in', '<path d="M20 6 9 17l-5-5"/>'],
        'dormant' => ['Dormant', $totals['dormant'], 'amber', 'No activity for 14+ days', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        'inactive' => ['Deactivated', $totals['inactive'], 'red', 'Signed out & blocked', '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>'],
    ];
    $signupMax = max(1, collect($signupsByWeek)->max('count'));
    $entryMax = max(1, $patients->getCollection()->max(fn ($patient): int => $patient->food_logs_count + $patient->symptom_logs_count + $patient->sleep_logs_count));
    $recency = function (?string $lastActive): array {
        if (! $lastActive) {
            return ['Never active', 'slate'];
        }
        $days = \Illuminate\Support\Carbon::parse($lastActive)->diffInDays(now());

        return match (true) {
            $days < 1 => ['Active today', 'green'],
            $days < 7 => ['Active this week', 'sky'],
            $days < 14 => ['Quiet lately', 'amber'],
            default => ['Dormant', 'red'],
        };
    };
@endphp
<style>
    .pt-hero-chart { display:flex; flex-direction:column; gap:10px; min-width:min(400px,100%); padding:14px 16px; border:1px solid #ffffff1f; border-radius:18px; background:#ffffff0f; backdrop-filter:blur(10px); }
    .pt-hero-chart-top { display:flex; align-items:baseline; justify-content:space-between; gap:12px; color:#a7f3d0; font-size:11.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
    .pt-hero-chart-top strong { color:#fff; font-size:22px; letter-spacing:-.03em; text-transform:none; }
    .pt-bars { display:grid; grid-template-columns:repeat(12,1fr); gap:5px; align-items:end; height:64px; }
    .pt-bars i { display:block; min-height:4px; border-radius:4px 4px 1px 1px; background:#ffffff26; transform-origin:bottom; animation:lpGrow .7s cubic-bezier(.2,.8,.2,1) both; }
    .pt-bars i.is-on { background:linear-gradient(to top,#10b981,#6ee7b7); }
    .pt-hero-chart-foot { display:flex; justify-content:space-between; color:#86b3a5; font-size:10.5px; }
    .pt-segments { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .pt-segment { display:flex; align-items:center; gap:12px; padding:14px; border:1px solid #fff; border-radius:18px; background:#ffffffd9; box-shadow:var(--shadow-soft); transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease; }
    .pt-segment:hover { transform:translateY(-2px); box-shadow:0 1px 2px #0f172a0a,0 18px 32px -18px #0a5a4059; }
    .pt-segment.is-active { border-color:var(--tone); box-shadow:0 0 0 3px var(--tone-mid); }
    .pt-segment .lp-badge-icon { width:42px; height:42px; border-radius:13px; }
    .pt-segment strong { display:block; font-size:24px; font-weight:800; line-height:1.1; letter-spacing:-.03em; }
    .pt-segment div > span { display:block; color:#334155; font-size:13px; font-weight:700; }
    .pt-segment small { display:block; color:#94a3b8; font-size:11.5px; }
    .pt-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:6px; }
    .pt-toolbar .au-search { flex:1 1 280px; }
    .pt-meta { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin:10px 0 2px; color:var(--muted); font-size:12.5px; }
    .pt-table td { padding-top:13px; padding-bottom:13px; }
    .pt-person { display:flex; align-items:center; gap:12px; min-width:0; }
    .pt-avatar { position:relative; width:40px; height:40px; flex:none; display:grid; place-items:center; border-radius:13px; color:#fff; background:linear-gradient(135deg,#34d399,#0f7a56); font-size:14px; font-weight:800; }
    .pt-avatar.is-off { background:linear-gradient(135deg,#cbd5e1,#64748b); }
    .pt-avatar i { position:absolute; right:-3px; bottom:-3px; width:12px; height:12px; border-radius:50%; background:var(--tone); box-shadow:0 0 0 2px #fff; }
    .pt-person strong { display:flex; align-items:center; gap:6px; overflow:hidden; color:var(--ink); font-size:14px; white-space:nowrap; }
    .pt-person small { display:block; overflow:hidden; color:var(--muted); font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
    .pt-new { padding:1px 7px; border-radius:999px; color:#0369a1; background:#e0f2fe; font-size:10.5px; font-weight:800; }
    .pt-engagement { min-width:130px; }
    .pt-engagement b { display:block; color:#334155; font-size:12.5px; }
    .pt-stack { display:flex; height:6px; margin-top:5px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .pt-stack i { height:100%; }
    .pt-stack .is-food { background:#10b981; } .pt-stack .is-symptom { background:#f87171; } .pt-stack .is-sleep { background:#818cf8; }
    .pt-recency { display:flex; flex-direction:column; gap:2px; white-space:nowrap; }
    .pt-recency b { display:inline-flex; align-items:center; gap:6px; color:var(--tone); font-size:12px; }
    .pt-recency b:before { content:""; width:7px; height:7px; border-radius:50%; background:currentColor; }
    .pt-recency small { color:#94a3b8; font-size:11.5px; }
    .pt-actions { display:flex; justify-content:flex-end; align-items:center; gap:6px; }
    .pt-menu { position:relative; }
    .pt-menu summary { width:32px; height:32px; display:grid; place-items:center; border:1px solid var(--line); border-radius:9px; color:#64748b; background:#fff; list-style:none; cursor:pointer; }
    .pt-menu summary::-webkit-details-marker { display:none; }
    .pt-menu summary:hover, .pt-menu[open] summary { border-color:#a7f3d0; background:#f0fdf4; }
    .pt-menu svg { width:16px; height:16px; fill:currentColor; }
    .pt-menu-list { position:absolute; top:calc(100% + 6px); right:0; z-index:10; display:grid; min-width:200px; padding:6px; border:1px solid var(--line); border-radius:13px; background:#fff; box-shadow:0 20px 40px -16px #0f172a59; animation:lpRise .15s ease both; }
    .pt-menu-list form { margin:0; }
    .pt-menu-list button, .pt-menu-list a { width:100%; display:flex; align-items:center; gap:8px; padding:9px 10px; border:0; border-radius:9px; color:#334155; background:none; font:inherit; font-size:13px; font-weight:600; text-align:left; cursor:pointer; }
    .pt-menu-list button:hover, .pt-menu-list a:hover { background:#f8fafa; }
    .pt-menu-list .is-danger { color:#b91c1c; } .pt-menu-list .is-danger:hover { background:#fef2f2; }
    .pt-menu-list svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .pt-menu-list hr { margin:4px 0; border:0; border-top:1px solid #eef2f1; }
    .pt-legend { display:flex; gap:12px; color:var(--muted); font-size:11.5px; }
    .pt-legend span { display:inline-flex; align-items:center; gap:5px; }
    .pt-legend i { width:9px; height:9px; border-radius:3px; }
    .pt-drawer { position:fixed; inset:0; z-index:60; pointer-events:none; }
    .pt-drawer-backdrop { position:absolute; inset:0; background:#0b1f2a66; opacity:0; transition:opacity .25s ease; }
    .pt-drawer-panel { position:absolute; top:0; right:0; bottom:0; display:flex; flex-direction:column; width:min(460px,100%); background:#fff; box-shadow:-30px 0 60px -20px #0b1f2a66; transform:translateX(100%); transition:transform .3s cubic-bezier(.2,.8,.2,1); }
    .pt-drawer.is-open { pointer-events:auto; }
    .pt-drawer.is-open .pt-drawer-backdrop { opacity:1; }
    .pt-drawer.is-open .pt-drawer-panel { transform:none; }
    .pt-drawer-head { position:relative; padding:24px 20px 20px; color:#ecfdf5; background:linear-gradient(150deg,#0d4637,#071f1a); }
    .pt-drawer-head .pt-avatar { width:56px; height:56px; border-radius:17px; font-size:22px; box-shadow:0 0 0 3px #ffffff1f; }
    .pt-drawer-head h2 { margin:12px 0 2px; color:#fff; font-size:20px; }
    .pt-drawer-head p { display:flex; align-items:center; gap:8px; margin:0; color:#a9cbbf; font-size:13px; }
    .pt-drawer-close { position:absolute; top:16px; right:16px; width:34px; height:34px; display:grid; place-items:center; padding:0; border:1px solid #ffffff26; border-radius:10px; color:#d1fae5; background:#ffffff14; font-size:18px; cursor:pointer; }
    .pt-copy { padding:2px 8px; border:1px solid #ffffff26; border-radius:7px; color:#d1fae5; background:transparent; font:inherit; font-size:11px; cursor:pointer; }
    .pt-drawer-body { flex:1; overflow-y:auto; padding:20px; }
    .pt-drawer-body h3 { margin:18px 0 10px; color:#94a3b8; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .pt-drawer-body h3:first-child { margin-top:0; }
    .pt-facts { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .pt-facts div { padding:12px; border:1px solid #f1f5f4; border-radius:13px; background:#fafcfb; }
    .pt-facts span { display:block; color:var(--muted); font-size:11.5px; }
    .pt-facts strong { display:block; margin-top:2px; color:var(--ink); font-size:14px; }
    .pt-counts { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
    .pt-counts div { padding:12px; border-radius:13px; background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); text-align:center; }
    .pt-counts b { display:block; color:var(--tone); font-size:22px; font-weight:800; }
    .pt-counts span { color:#475569; font-size:11.5px; font-weight:600; }
    .pt-events { display:grid; gap:4px; }
    .pt-event { display:grid; grid-template-columns:32px minmax(0,1fr) auto; align-items:center; gap:10px; padding:8px; border-radius:11px; }
    .pt-event:hover { background:#fafcfb; }
    .pt-event .lp-badge-icon { width:32px; height:32px; border-radius:10px; }
    .pt-event strong { font-size:13px; }
    .pt-event small { display:block; color:var(--muted); font-size:11.5px; }
    .pt-event time { color:#94a3b8; font-size:11.5px; white-space:nowrap; }
    .pt-privacy { display:flex; gap:8px; margin-top:16px; padding:10px 12px; border-radius:12px; color:#475569; background:#f8fafa; font-size:12px; line-height:1.5; }
    .pt-privacy svg { width:15px; height:15px; flex:none; margin-top:1px; fill:none; stroke:#059669; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .pt-drawer-foot { display:flex; flex-wrap:wrap; gap:8px; padding:16px 20px; border-top:1px solid #eef2f1; }
    .pt-drawer-foot form { margin:0; }
    @media (max-width:1280px) { .pt-segments { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:760px) { .pt-segments { grid-template-columns:1fr; } .pt-hero-chart { min-width:0; } }
</style>

<div class="lp">
    @if (session('status'))
        <div class="au-flash" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>{{ session('status') }}</div>
    @endif

    <section class="lp-hero">
        <div>
            <span class="lp-crumb"><a href="{{ route('admin.dashboard') }}">Admin</a> / Patients</span>
            <h1>Patients</h1>
            <p>Find a patient, check how engaged they are, and handle deactivation or deletion requests. Click a patient to see their account details.</p>
        </div>
        <div class="pt-hero-chart" role="img" aria-label="New patient sign-ups per week over the last 12 weeks">
            <div class="pt-hero-chart-top"><span>Sign-ups · 12 weeks</span><strong>{{ collect($signupsByWeek)->sum('count') }} new</strong></div>
            <div class="pt-bars">
                @foreach ($signupsByWeek as $index => $week)
                    <i class="{{ $week['count'] ? 'is-on' : '' }}" title="Week of {{ $week['label'] }}: {{ $week['count'] }} sign-up{{ $week['count'] === 1 ? '' : 's' }}" @style(['height: '.max(6, $week['count'] / $signupMax * 100).'%', 'animation-delay: '.($index * 0.03).'s'])></i>
                @endforeach
            </div>
            <div class="pt-hero-chart-foot"><span>{{ $signupsByWeek[0]['label'] }}</span><span>{{ $totals['newThisWeek'] }} in the last 7 days</span><span>This week</span></div>
        </div>
    </section>

    <nav class="pt-segments" aria-label="Patient segments">
        @foreach ($segments as $key => [$label, $count, $tone, $hint, $icon])
            <a class="pt-segment lp-tone-{{ $tone }} {{ $filters['status'] === $key ? 'is-active' : '' }}" href="{{ route('admin.patients', [...$filters, 'status' => $key, 'page' => null]) }}" @if ($filters['status'] === $key) aria-current="true" @endif>
                <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span>
                <div><strong>{{ $count }}</strong><span>{{ $label }}</span><small>{{ $hint }}</small></div>
            </a>
        @endforeach
    </nav>

    <section class="lp-panel">
        <form class="pt-toolbar" method="GET" action="{{ route('admin.patients') }}">
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
            <label class="au-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input class="au-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search by name or email…" aria-label="Search patients"></label>
            <select class="au-select" name="sort" aria-label="Sort patients" onchange="this.form.submit()">
                @foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'name' => 'Name A–Z', 'most-active' => 'Most entries'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="au-btn is-primary" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg> Search</button>
            <a class="au-btn" href="{{ route('admin.patients.export', $filters) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg> Export CSV</a>
        </form>
        <div class="pt-meta">
            <span>{{ $patients->total() }} patient{{ $patients->total() === 1 ? '' : 's' }}{{ $filters['q'] !== '' ? ' matching "'.$filters['q'].'"' : '' }}{{ $filters['status'] !== 'all' ? ' · '.$segments[$filters['status']][0] : '' }}@if ($filters['q'] !== '' || $filters['status'] !== 'all') · <a href="{{ route('admin.patients') }}" style="color:var(--green);font-weight:700">Clear</a>@endif</span>
            <div class="pt-legend" aria-label="Entries legend"><span><i style="background:#10b981"></i> Food</span><span><i style="background:#f87171"></i> Symptoms</span><span><i style="background:#818cf8"></i> Sleep</span></div>
        </div>

        @if ($patients->isEmpty())
            <div class="lp-empty" style="margin-top:12px"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/></svg><strong>No patients found</strong>{{ $filters['q'] !== '' || $filters['status'] !== 'all' ? 'Try another segment or search.' : 'Patient accounts will appear here.' }}</div>
        @else
            <div style="overflow-x:auto">
                <table class="au-table pt-table">
                    <thead><tr><th>Patient</th><th class="au-hide-sm">Engagement</th><th class="au-hide-sm">Last activity</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($patients as $patient)
                            @php
                                $entries = $patient->food_logs_count + $patient->symptom_logs_count + $patient->sleep_logs_count;
                                [$recencyLabel, $recencyTone] = $recency($patient->last_active_at);
                                $isNew = $patient->created_at->greaterThan(now()->subDays(7));
                                $events = ($recentEvents->get($patient->id) ?? collect())->map(function ($event): array {
                                    $presented = \App\Support\ActivityPresenter::describe($event->action);

                                    return ['label' => $presented['label'], 'tone' => $presented['tone'], 'icon' => $presented['icon'], 'channel' => $event->metadata['channel'] ?? null, 'ip' => $event->ip_address, 'ago' => $event->created_at->diffForHumans()];
                                })->values();
                            @endphp
                            <tr>
                                <td>
                                    <div class="pt-person">
                                        <span class="pt-avatar lp-tone-{{ $recencyTone }} {{ $patient->is_active ? '' : 'is-off' }}">{{ strtoupper(substr($patient->name, 0, 1)) }}<i title="{{ $recencyLabel }}"></i></span>
                                        <div style="min-width:0"><strong>{{ $patient->name }}@if ($isNew)<span class="pt-new">New</span>@endif</strong><small>{{ $patient->email }} · joined {{ $patient->created_at->format('M j, Y') }}</small></div>
                                    </div>
                                </td>
                                <td class="au-hide-sm">
                                    <div class="pt-engagement" title="{{ $patient->food_logs_count }} food · {{ $patient->symptom_logs_count }} symptom · {{ $patient->sleep_logs_count }} sleep">
                                        <b>{{ $entries }} entr{{ $entries === 1 ? 'y' : 'ies' }}</b>
                                        <div class="pt-stack" @style(['width: '.max(8, $entries / $entryMax * 100).'%'])>
                                            @if ($entries)
                                                <i class="is-food" @style(['width: '.($patient->food_logs_count / $entries * 100).'%'])></i>
                                                <i class="is-symptom" @style(['width: '.($patient->symptom_logs_count / $entries * 100).'%'])></i>
                                                <i class="is-sleep" @style(['width: '.($patient->sleep_logs_count / $entries * 100).'%'])></i>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="au-hide-sm"><div class="pt-recency lp-tone-{{ $recencyTone }}"><b>{{ $recencyLabel }}</b><small>{{ $patient->last_active_at ? \Illuminate\Support\Carbon::parse($patient->last_active_at)->diffForHumans() : 'No recorded activity' }}</small></div></td>
                                <td><span class="au-pill lp-tone-{{ $patient->is_active ? 'green' : 'red' }}"><i></i>{{ $patient->is_active ? 'Active' : 'Deactivated' }}</span></td>
                                <td>
                                    <div class="pt-actions">
                                        <button class="au-btn is-sm" type="button" data-patient="{{ json_encode([
                                            'id' => $patient->id,
                                            'name' => $patient->name,
                                            'email' => $patient->email,
                                            'initial' => strtoupper(substr($patient->name, 0, 1)),
                                            'active' => $patient->is_active,
                                            'joined' => $patient->created_at->format('F j, Y'),
                                            'joinedAgo' => $patient->created_at->diffForHumans(),
                                            'lastActive' => $patient->last_active_at ? \Illuminate\Support\Carbon::parse($patient->last_active_at)->diffForHumans() : 'Never',
                                            'recency' => $recencyLabel,
                                            'recencyTone' => $recencyTone,
                                            'food' => $patient->food_logs_count,
                                            'symptoms' => $patient->symptom_logs_count,
                                            'sleep' => $patient->sleep_logs_count,
                                            'events' => $events,
                                            'activityUrl' => route('admin.activity', ['q' => $patient->email]),
                                            'statusUrl' => route('admin.patients.status', $patient),
                                            'deleteUrl' => route('admin.patients.destroy', $patient),
                                        ]) }}">View</button>
                                        <details class="pt-menu">
                                            <summary aria-label="More actions for {{ $patient->name }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg></summary>
                                            <div class="pt-menu-list">
                                                <a href="{{ route('admin.activity', ['q' => $patient->email]) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg> View activity</a>
                                                <form method="POST" action="{{ route('admin.patients.status', $patient) }}" data-confirm="{{ $patient->is_active ? "Deactivate {$patient->name}? They will be signed out of every device and blocked from signing in." : "Reactivate {$patient->name}? They will be able to sign in again." }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="{{ $patient->is_active ? 0 : 1 }}">
                                                    <button type="submit">@if ($patient->is_active)<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/></svg> Deactivate @else<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Reactivate @endif</button>
                                                </form>
                                                <hr>
                                                <button class="is-danger" type="button" data-delete-patient data-name="{{ $patient->name }}" data-email="{{ $patient->email }}" data-action="{{ route('admin.patients.destroy', $patient) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg> Delete account…</button>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="au-pagination">
                <span>Showing {{ $patients->firstItem() }}–{{ $patients->lastItem() }} of {{ $patients->total() }}</span>
                <nav aria-label="Pages">
                    @if ($patients->previousPageUrl())<a class="au-btn is-sm" href="{{ $patients->previousPageUrl() }}">← Previous</a>@endif
                    @if ($patients->nextPageUrl())<a class="au-btn is-sm" href="{{ $patients->nextPageUrl() }}">Next →</a>@endif
                </nav>
            </div>
        @endif
    </section>
</div>

<div class="pt-drawer" id="patient-drawer" aria-hidden="true">
    <div class="pt-drawer-backdrop" data-close-drawer></div>
    <div class="pt-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="pd-name">
        <div class="pt-drawer-head">
            <button class="pt-drawer-close" type="button" data-close-drawer aria-label="Close patient details">×</button>
            <span class="pt-avatar" id="pd-avatar"><span id="pd-initial"></span><i></i></span>
            <h2 id="pd-name"></h2>
            <p><span id="pd-email"></span><button class="pt-copy" type="button" id="pd-copy">Copy</button></p>
        </div>
        <div class="pt-drawer-body">
            <h3>Account</h3>
            <div class="pt-facts">
                <div><span>Status</span><strong id="pd-status"></strong></div>
                <div><span>Engagement</span><strong id="pd-recency"></strong></div>
                <div><span>Joined</span><strong id="pd-joined"></strong></div>
                <div><span>Last activity</span><strong id="pd-last"></strong></div>
            </div>
            <h3>Entries logged</h3>
            <div class="pt-counts">
                <div class="lp-tone-green"><b id="pd-food">0</b><span>Food</span></div>
                <div class="lp-tone-red"><b id="pd-symptoms">0</b><span>Symptoms</span></div>
                <div class="lp-tone-violet"><b id="pd-sleep">0</b><span>Sleep</span></div>
            </div>
            <h3>Recent account activity · 30 days</h3>
            <div class="pt-events" id="pd-events"></div>
            <div class="pt-privacy"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><span>Health entries stay private to the patient. Admins only see counts and account activity, as described in the Privacy Policy.</span></div>
        </div>
        <div class="pt-drawer-foot">
            <a class="au-btn" id="pd-activity" href="#"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg> Full activity</a>
            <form method="POST" id="pd-status-form" data-confirm="">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" id="pd-status-value">
                <button class="au-btn" type="submit" id="pd-status-button"></button>
            </form>
            <button class="au-btn is-danger" type="button" id="pd-delete">Delete…</button>
        </div>
    </div>
</div>

<div class="au-modal" id="delete-modal" role="dialog" aria-modal="true" aria-labelledby="delete-title" data-reopen="{{ $deleteErrors->any() ? '1' : '0' }}" data-expected="{{ old('patient_email', '') }}">
    <form class="au-modal-card" method="POST" id="delete-form" action="{{ old('patient_action') }}">
        @csrf
        @method('DELETE')
        <h3 id="delete-title"><span class="lp-badge-icon lp-tone-red"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg></span> Delete <span id="delete-name">{{ old('patient_name') }}</span>?</h3>
        <p>This permanently deletes the account and <strong>all of its food, symptom and sleep logs and insights</strong>. It cannot be undone. Only do this when the patient asks for their account to be removed.</p>
        <input type="hidden" name="patient_action" id="delete-action" value="{{ old('patient_action') }}">
        <input type="hidden" name="patient_name" id="delete-name-input" value="{{ old('patient_name') }}">
        <label for="confirm_email">Type <strong id="delete-email">{{ old('patient_email') }}</strong> to confirm</label>
        <input type="hidden" name="patient_email" id="delete-email-input" value="{{ old('patient_email') }}">
        <input class="au-input" id="confirm_email" name="confirm_email" autocomplete="off" required @if ($deleteErrors->any()) aria-invalid="true" @endif>
        @if ($deleteErrors->any())<p class="au-error">{{ $deleteErrors->first() }}</p>@endif
        <div class="au-modal-actions">
            <button class="au-btn" type="button" data-close-modal>Cancel</button>
            <button class="au-btn is-solid-danger" type="submit" id="delete-submit" disabled>Delete permanently</button>
        </div>
    </form>
</div>

<script>
    (() => {
        const drawer = document.getElementById('patient-drawer');
        const modal = document.getElementById('delete-modal');
        document.body.append(drawer, modal);
        const $ = (id) => document.getElementById(id);

        // Delete confirmation
        const deleteForm = $('delete-form');
        const confirmInput = $('confirm_email');
        const deleteSubmit = $('delete-submit');
        let expectedEmail = modal.dataset.expected;
        const syncDelete = () => { deleteSubmit.disabled = confirmInput.value.trim() !== expectedEmail; };
        const openDelete = ({ name, email, action }) => {
            expectedEmail = email;
            deleteForm.action = action;
            $('delete-action').value = action;
            $('delete-name').textContent = name;
            $('delete-name-input').value = name;
            $('delete-email').textContent = email;
            $('delete-email-input').value = email;
            confirmInput.value = '';
            syncDelete();
            modal.classList.add('is-open');
            confirmInput.focus();
        };
        const closeDelete = () => { modal.classList.remove('is-open'); confirmInput.value = ''; deleteSubmit.disabled = true; };
        confirmInput.addEventListener('input', syncDelete);
        modal.querySelector('[data-close-modal]').addEventListener('click', closeDelete);
        modal.addEventListener('click', (event) => { if (event.target === modal) { closeDelete(); } });
        document.querySelectorAll('[data-delete-patient]').forEach((button) => button.addEventListener('click', () => {
            button.closest('details')?.removeAttribute('open');
            openDelete({ name: button.dataset.name, email: button.dataset.email, action: button.dataset.action });
        }));
        if (modal.dataset.reopen === '1') { modal.classList.add('is-open'); }

        document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
            if (! window.confirm(form.dataset.confirm)) { event.preventDefault(); }
        }));

        // Patient details drawer
        let current = null;
        let trigger = null;
        const openDrawer = (button) => {
            current = JSON.parse(button.dataset.patient);
            trigger = button;
            $('pd-avatar').className = `pt-avatar lp-tone-${current.recencyTone}${current.active ? '' : ' is-off'}`;
            $('pd-initial').textContent = current.initial;
            $('pd-name').textContent = current.name;
            $('pd-email').textContent = current.email;
            $('pd-copy').textContent = 'Copy';
            $('pd-status').textContent = current.active ? 'Active' : 'Deactivated';
            $('pd-recency').textContent = current.recency;
            $('pd-joined').textContent = current.joined;
            $('pd-last').textContent = current.lastActive;
            $('pd-food').textContent = current.food;
            $('pd-symptoms').textContent = current.symptoms;
            $('pd-sleep').textContent = current.sleep;
            const events = $('pd-events');
            events.replaceChildren();
            if (current.events.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'lp-hint';
                empty.style.margin = '0';
                empty.textContent = 'No account activity in the last 30 days.';
                events.appendChild(empty);
            }
            current.events.forEach((event) => {
                const row = document.createElement('div');
                row.className = `pt-event lp-tone-${event.tone}`;
                row.innerHTML = `<span class="lp-badge-icon"><svg class="lp-svg" style="width:15px;height:15px" viewBox="0 0 24 24" aria-hidden="true">${event.icon}</svg></span><div><strong></strong><small></small></div><time></time>`;
                row.querySelector('strong').textContent = event.label;
                row.querySelector('small').textContent = [event.channel ? `via ${event.channel}` : null, event.ip].filter(Boolean).join(' · ');
                row.querySelector('time').textContent = event.ago;
                events.appendChild(row);
            });
            $('pd-activity').href = current.activityUrl;
            const statusForm = $('pd-status-form');
            statusForm.action = current.statusUrl;
            statusForm.dataset.confirm = current.active
                ? `Deactivate ${current.name}? They will be signed out of every device and blocked from signing in.`
                : `Reactivate ${current.name}? They will be able to sign in again.`;
            $('pd-status-value').value = current.active ? 0 : 1;
            $('pd-status-button').textContent = current.active ? 'Deactivate' : 'Reactivate';
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            drawer.querySelector('.pt-drawer-close').focus();
        };
        const closeDrawer = () => {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            trigger?.focus();
        };
        document.querySelectorAll('[data-patient]').forEach((button) => button.addEventListener('click', () => openDrawer(button)));
        drawer.querySelectorAll('[data-close-drawer]').forEach((element) => element.addEventListener('click', closeDrawer));
        $('pd-copy').addEventListener('click', async () => {
            try { await navigator.clipboard.writeText(current.email); $('pd-copy').textContent = 'Copied ✓'; } catch (error) { $('pd-copy').textContent = 'Copy failed'; }
        });
        $('pd-delete').addEventListener('click', () => { closeDrawer(); openDelete({ name: current.name, email: current.email, action: current.deleteUrl }); });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') { return; }
            if (modal.classList.contains('is-open')) { closeDelete(); } else if (drawer.classList.contains('is-open')) { closeDrawer(); }
            document.querySelectorAll('.pt-menu[open]').forEach((menu) => menu.removeAttribute('open'));
        });
        document.addEventListener('click', (event) => {
            document.querySelectorAll('.pt-menu[open]').forEach((menu) => { if (! menu.contains(event.target)) { menu.removeAttribute('open'); } });
        });
    })();
</script>
@endsection
