@extends('admin.layout')

@section('admin-content')
@include('admin.partials.ui')
@php
    $hasFilters = $filters['q'] !== '' || $filters['group'] !== 'all' || $filters['from'] || $filters['to'];
    $today = now()->toDateString();
    $presets = [
        'today' => ['Today', $today, $today],
        '7d' => ['Last 7 days', now()->subDays(6)->toDateString(), $today],
        '30d' => ['Last 30 days', now()->subDays(29)->toDateString(), $today],
    ];
    $activePreset = collect($presets)->search(fn (array $preset): bool => $filters['from'] === $preset[1] && $filters['to'] === $preset[2]);
    $hourMax = max(1, collect($hourly)->max('count'));
    $groupTotal = max(1, array_sum($groupCounts));
    $groupMeta = [
        'sign-ins' => ['tone' => 'green', 'icon' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>'],
        'entries' => ['tone' => 'orange', 'icon' => '<path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/>'],
        'accounts' => ['tone' => 'amber', 'icon' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'],
        'guidance' => ['tone' => 'violet', 'icon' => '<path d="M12 3c1.6 2.2 5 4.5 5 8.4A5 5 0 0 1 7 11.4C7 8.1 9.6 6 12 3Z"/>'],
    ];
    $days = $activities->getCollection()->groupBy(fn ($activity): string => $activity->created_at->isToday() ? 'Today' : ($activity->created_at->isYesterday() ? 'Yesterday' : $activity->created_at->format('l, F j')));
    $topActorMax = max(1, (int) $topActors->max('total'));
@endphp
<style>
    .ac-hero-chart { display:flex; flex-direction:column; gap:10px; min-width:min(420px,100%); padding:14px 16px; border:1px solid #ffffff1f; border-radius:18px; background:#ffffff0f; backdrop-filter:blur(10px); }
    .ac-hero-chart-top { display:flex; align-items:baseline; justify-content:space-between; gap:12px; color:#a7f3d0; font-size:11.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
    .ac-hero-chart-top strong { color:#fff; font-size:22px; letter-spacing:-.03em; text-transform:none; }
    .ac-bars { display:grid; grid-template-columns:repeat(24,1fr); gap:3px; align-items:end; height:60px; }
    .ac-bars i { position:relative; display:block; min-height:3px; border-radius:3px 3px 1px 1px; background:#ffffff26; transform-origin:bottom; animation:lpGrow .7s cubic-bezier(.2,.8,.2,1) both; }
    .ac-bars i.is-on { background:linear-gradient(to top,#10b981,#6ee7b7); }
    .ac-bars i:hover { background:#fff; }
    .ac-hero-chart-foot { display:flex; justify-content:space-between; color:#86b3a5; font-size:10.5px; }
    .ac-hero-stats { display:flex; gap:8px; }
    .ac-hero-stats span { flex:1; padding:8px 10px; border-radius:11px; color:#a7f3d0; background:#ffffff12; font-size:11px; }
    .ac-hero-stats b { display:block; color:#fff; font-size:16px; }
    .ac-groups { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; }
    .ac-group { position:relative; display:flex; flex-direction:column; gap:8px; padding:14px; border:1px solid #fff; border-radius:18px; background:#ffffffd9; box-shadow:var(--shadow-soft); transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease; }
    .ac-group:hover { transform:translateY(-2px); box-shadow:0 1px 2px #0f172a0a,0 18px 32px -18px #0a5a4059; }
    .ac-group.is-active { border-color:var(--tone,#10b981); box-shadow:0 0 0 3px var(--tone-mid,#a7f3d0); }
    .ac-group-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .ac-group .lp-badge-icon { width:34px; height:34px; border-radius:10px; }
    .ac-group strong { font-size:22px; font-weight:800; letter-spacing:-.03em; }
    .ac-group span { color:#475569; font-size:12.5px; font-weight:600; }
    .ac-group small { color:#94a3b8; font-size:11px; }
    .ac-group-bar { height:5px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .ac-group-bar i { display:block; height:100%; border-radius:inherit; background:var(--tone,#10b981); }
    .ac-filters { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:8px; }
    .ac-filters .au-search { flex:1 1 260px; }
    .ac-presets { display:flex; flex-wrap:wrap; gap:6px; }
    .ac-presets a, .ac-presets button { display:inline-flex; align-items:center; height:34px; padding:0 12px; border:1px solid var(--line); border-radius:999px; color:#475569; background:#fff; font:inherit; font-size:12.5px; font-weight:600; cursor:pointer; }
    .ac-presets a:hover, .ac-presets button:hover { border-color:#a7f3d0; background:#f0fdf4; }
    .ac-presets .is-active { border-color:var(--green); color:#fff; background:var(--green); }
    .ac-custom { display:none; flex-wrap:wrap; align-items:center; gap:8px; margin:8px 0 4px; padding:10px; border:1px dashed #a7f3d0; border-radius:14px; background:#f0fdf4; }
    .ac-custom.is-open { display:flex; }
    .ac-custom label { display:flex; align-items:center; gap:6px; color:#475569; font-size:12px; font-weight:700; }
    .ac-custom .au-input { width:160px; height:38px; }
    .ac-meta { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin:12px 0 4px; color:var(--muted); font-size:12.5px; }
    .ac-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 6px 4px 10px; border-radius:999px; color:var(--green-deep); background:#ecfdf5; font-size:12px; font-weight:600; }
    .ac-chip a { display:grid; place-items:center; width:18px; height:18px; border-radius:50%; color:var(--green-deep); background:#d1fae5; font-size:12px; }
    .ac-live { display:inline-flex; align-items:center; gap:8px; color:#475569; font-size:12.5px; font-weight:600; cursor:pointer; user-select:none; }
    .ac-live input { position:absolute; opacity:0; }
    .ac-switch { position:relative; width:34px; height:20px; border-radius:999px; background:#cbd5e1; transition:background .2s ease; }
    .ac-switch:after { content:""; position:absolute; top:2px; left:2px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px #0003; transition:transform .2s ease; }
    .ac-live input:checked + .ac-switch { background:var(--green); }
    .ac-live input:checked + .ac-switch:after { transform:translateX(14px); }
    .ac-live input:focus-visible + .ac-switch { outline:2px solid #10b981; outline-offset:2px; }
    .ac-layout { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:20px; align-items:start; }
    .ac-day { margin-top:16px; }
    .ac-day h3 { position:sticky; top:72px; z-index:2; display:flex; align-items:center; justify-content:space-between; margin:0 0 6px; padding:8px 4px; color:#64748b; background:linear-gradient(#fffffff5,#ffffffe0); font-size:11.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .ac-day h3 span { color:#94a3b8; font-weight:600; letter-spacing:0; text-transform:none; }
    .ac-event { position:relative; display:grid; grid-template-columns:38px minmax(0,1fr) auto; gap:12px; align-items:center; width:100%; padding:10px; border:1px solid transparent; border-radius:14px; color:inherit; background:none; font:inherit; text-align:left; cursor:pointer; transition:background .2s ease,border-color .2s ease; }
    .ac-event:hover { border-color:#eef2f1; background:#fafcfb; }
    .ac-event:focus-visible { outline:2px solid #10b981; }
    .ac-event.is-selected { border-color:#a7f3d0; background:#f0fdf4; }
    .ac-event:not(:last-child):after { content:""; position:absolute; left:28px; top:50px; bottom:-8px; width:2px; background:#f1f5f9; }
    .ac-event .lp-badge-icon { position:relative; z-index:1; width:38px; height:38px; border-radius:12px; box-shadow:0 0 0 3px #fff; }
    .ac-event-title { display:flex; flex-wrap:wrap; align-items:center; gap:6px; }
    .ac-event-title strong { color:var(--ink); font-size:13.5px; }
    .ac-event-sub { display:block; margin-top:2px; overflow:hidden; color:var(--muted); font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
    .ac-tag { padding:1px 7px; border-radius:999px; color:#475569; background:#f1f5f9; font-size:10.5px; font-weight:700; }
    .ac-tag.is-admin { color:#6d28d9; background:#f5f3ff; }
    .ac-tag.is-google { color:#1d4ed8; background:#eff6ff; }
    .ac-time { display:flex; flex-direction:column; align-items:flex-end; gap:2px; white-space:nowrap; }
    .ac-time b { color:#334155; font-size:12.5px; }
    .ac-time small { color:#94a3b8; font-size:11px; }
    .ac-side { display:grid; gap:16px; position:sticky; top:92px; }
    .ac-actor { display:grid; grid-template-columns:32px minmax(0,1fr) auto; align-items:center; gap:10px; }
    .ac-actor + .ac-actor { margin-top:12px; }
    .ac-actor .au-avatar { width:32px; height:32px; border-radius:10px; font-size:12px; }
    .ac-actor strong { display:block; overflow:hidden; font-size:12.5px; text-overflow:ellipsis; white-space:nowrap; }
    .ac-actor-bar { height:5px; margin-top:5px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .ac-actor-bar i { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#34d399,#0f7a56); }
    .ac-actor b { color:#334155; font-size:12px; }
    .ac-watch { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .ac-watch div { padding:12px; border:1px solid #f1f5f4; border-radius:13px; background:#fafcfb; }
    .ac-watch b { display:block; color:var(--ink); font-size:20px; font-weight:800; letter-spacing:-.03em; }
    .ac-watch span { color:var(--muted); font-size:11.5px; }
    .ac-drawer { position:fixed; inset:0; z-index:60; pointer-events:none; }
    .ac-drawer-backdrop { position:absolute; inset:0; background:#0b1f2a66; opacity:0; transition:opacity .25s ease; }
    .ac-drawer-panel { position:absolute; top:0; right:0; bottom:0; display:flex; flex-direction:column; width:min(440px,100%); background:#fff; box-shadow:-30px 0 60px -20px #0b1f2a66; transform:translateX(100%); transition:transform .3s cubic-bezier(.2,.8,.2,1); }
    .ac-drawer.is-open { pointer-events:auto; }
    .ac-drawer.is-open .ac-drawer-backdrop { opacity:1; }
    .ac-drawer.is-open .ac-drawer-panel { transform:none; }
    .ac-drawer-head { display:flex; align-items:center; gap:12px; padding:20px; border-bottom:1px solid #eef2f1; }
    .ac-drawer-head .lp-badge-icon { width:44px; height:44px; border-radius:13px; }
    .ac-drawer-head h2 { margin:0; font-size:17px; }
    .ac-drawer-head p { margin:2px 0 0; color:var(--muted); font-size:12.5px; }
    .ac-drawer-close { margin-left:auto; width:34px; height:34px; display:grid; place-items:center; padding:0; border:1px solid var(--line); border-radius:10px; color:#64748b; background:#fff; font-size:18px; cursor:pointer; }
    .ac-drawer-body { flex:1; overflow-y:auto; padding:20px; }
    .ac-detail { display:grid; grid-template-columns:110px minmax(0,1fr); gap:10px; padding:11px 0; border-bottom:1px solid #f1f5f4; font-size:13px; }
    .ac-detail dt { color:#94a3b8; font-size:11.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
    .ac-detail dd { margin:0; color:var(--ink); overflow-wrap:anywhere; }
    .ac-detail code { padding:2px 6px; border-radius:6px; color:#0f766e; background:#f0fdfa; font-size:12px; }
    .ac-json { margin:14px 0 0; padding:12px; overflow:auto; border-radius:12px; color:#d1fae5; background:#0b1f2a; font:12px/1.6 ui-monospace,monospace; }
    .ac-drawer-foot { display:flex; gap:8px; padding:16px 20px; border-top:1px solid #eef2f1; }
    @media (max-width:1280px) { .ac-groups { grid-template-columns:repeat(3,minmax(0,1fr)); } .ac-layout { grid-template-columns:minmax(0,1fr); } .ac-side { position:static; grid-template-columns:1fr 1fr; } }
    @media (max-width:760px) { .ac-groups { grid-template-columns:1fr 1fr; } .ac-side { grid-template-columns:1fr; } .ac-hero-chart { min-width:0; } .ac-event { grid-template-columns:38px minmax(0,1fr); } .ac-time { grid-column:2; align-items:flex-start; flex-direction:row; gap:6px; } }
</style>

<div class="lp">
    <section class="lp-hero">
        <div>
            <span class="lp-crumb"><a href="{{ route('admin.dashboard') }}">Admin</a> / System activity</span>
            <h1>System activity</h1>
            <p>A live audit trail of sign-ins, new accounts, patient entries and admin changes. Click any event to see everything that was recorded about it.</p>
        </div>
        <div class="ac-hero-chart" role="img" aria-label="Events per hour over the last 24 hours">
            <div class="ac-hero-chart-top"><span>Last 24 hours</span><strong>{{ collect($hourly)->sum('count') }} events</strong></div>
            <div class="ac-bars">
                @foreach ($hourly as $index => $bucket)
                    <i class="{{ $bucket['count'] ? 'is-on' : '' }}" title="{{ $bucket['hour'] }}: {{ $bucket['count'] }} event{{ $bucket['count'] === 1 ? '' : 's' }}" @style(['height: '.max(5, $bucket['count'] / $hourMax * 100).'%', 'animation-delay: '.($index * 0.02).'s'])></i>
                @endforeach
            </div>
            <div class="ac-hero-chart-foot"><span>{{ $hourly[0]['hour'] }}</span><span>Now</span></div>
            <div class="ac-hero-stats">
                <span><b>{{ $todayCount }}</b>today</span>
                <span><b>{{ $weekCount }}</b>last 7 days</span>
                <span><b>{{ $activities->total() }}</b>{{ $hasFilters ? 'matching' : 'all time' }}</span>
            </div>
        </div>
    </section>

    <nav class="ac-groups" aria-label="Filter by category">
        <a class="ac-group lp-tone-slate {{ $filters['group'] === 'all' ? 'is-active' : '' }}" href="{{ route('admin.activity', [...$filters, 'group' => 'all', 'page' => null]) }}">
            <div class="ac-group-top"><span>All events</span><span class="lp-badge-icon"><svg class="lp-svg" style="width:16px;height:16px" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg></span></div>
            <strong>{{ $weekCount }}</strong>
            <div class="ac-group-bar"><i style="width:100%"></i></div>
            <small>Last 7 days</small>
        </a>
        @foreach ($groups as $key => $group)
            <a class="ac-group lp-tone-{{ $groupMeta[$key]['tone'] }} {{ $filters['group'] === $key ? 'is-active' : '' }}" href="{{ route('admin.activity', [...$filters, 'group' => $key, 'page' => null]) }}">
                <div class="ac-group-top"><span>{{ $group['label'] }}</span><span class="lp-badge-icon"><svg class="lp-svg" style="width:16px;height:16px" viewBox="0 0 24 24" aria-hidden="true">{!! $groupMeta[$key]['icon'] !!}</svg></span></div>
                <strong>{{ $groupCounts[$key] }}</strong>
                <div class="ac-group-bar"><i @style(['width: '.($groupCounts[$key] / $groupTotal * 100).'%'])></i></div>
                <small>{{ (int) round($groupCounts[$key] / $groupTotal * 100) }}% of the last 7 days</small>
            </a>
        @endforeach
    </nav>

    <div class="ac-layout">
        <section class="lp-panel">
            <form class="ac-filters" method="GET" action="{{ route('admin.activity') }}" id="activity-filters">
                <input type="hidden" name="group" value="{{ $filters['group'] }}">
                <label class="au-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input class="au-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search a person, email or action…" aria-label="Search activity"></label>
                <div class="ac-presets" role="group" aria-label="Date range">
                    @foreach ($presets as $key => [$label, $from, $to])
                        <a href="{{ route('admin.activity', [...$filters, 'from' => $from, 'to' => $to, 'page' => null]) }}" class="{{ $activePreset === $key ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                    <button type="button" id="custom-range" class="{{ ($filters['from'] || $filters['to']) && $activePreset === false ? 'is-active' : '' }}" aria-expanded="false" aria-controls="custom-dates">Custom…</button>
                </div>
                <button class="au-btn is-primary" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg> Search</button>
                <a class="au-btn" href="{{ route('admin.activity.export', $filters) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg> Export</a>
                <div class="ac-custom {{ ($filters['from'] || $filters['to']) && $activePreset === false ? 'is-open' : '' }}" id="custom-dates">
                    <label>From <input class="au-input" type="date" name="from" value="{{ $filters['from'] }}" max="{{ $today }}"></label>
                    <label>To <input class="au-input" type="date" name="to" value="{{ $filters['to'] }}" max="{{ $today }}"></label>
                    <button class="au-btn is-sm is-primary" type="submit">Apply range</button>
                </div>
            </form>
            @if ($errors->any())<p class="au-error">{{ $errors->first() }}</p>@endif

            <div class="ac-meta">
                <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                    <span>{{ $activities->total() }} event{{ $activities->total() === 1 ? '' : 's' }}</span>
                    @if ($filters['group'] !== 'all')<span class="ac-chip">{{ $groups[$filters['group']]['label'] }} <a href="{{ route('admin.activity', [...$filters, 'group' => 'all', 'page' => null]) }}" aria-label="Remove category filter">×</a></span>@endif
                    @if ($filters['q'] !== '')<span class="ac-chip">"{{ $filters['q'] }}" <a href="{{ route('admin.activity', [...$filters, 'q' => null, 'page' => null]) }}" aria-label="Remove search">×</a></span>@endif
                    @if ($filters['from'] || $filters['to'])<span class="ac-chip">{{ $filters['from'] ?? '…' }} → {{ $filters['to'] ?? 'now' }} <a href="{{ route('admin.activity', [...$filters, 'from' => null, 'to' => null, 'page' => null]) }}" aria-label="Remove date range">×</a></span>@endif
                    @if ($hasFilters)<a class="lg-link" href="{{ route('admin.activity') }}" style="color:var(--green);font-weight:700;font-size:12.5px">Clear all</a>@endif
                </div>
                <label class="ac-live" title="Reload this page every 30 seconds"><input type="checkbox" id="auto-refresh"><span class="ac-switch" aria-hidden="true"></span> Auto-refresh</label>
            </div>

            @if ($activities->isEmpty())
                <div class="lp-empty" style="margin-top:12px"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg><strong>No matching activity</strong>{{ $hasFilters ? 'Try a different category, search or date range.' : 'Events will appear here as they happen.' }}</div>
            @else
                @foreach ($days as $dayLabel => $dayEvents)
                    <div class="ac-day">
                        <h3>{{ $dayLabel }} <span>{{ $dayEvents->count() }} event{{ $dayEvents->count() === 1 ? '' : 's' }}</span></h3>
                        @foreach ($dayEvents as $activity)
                            @php(['label' => $label, 'tone' => $tone, 'icon' => $icon] = \App\Support\ActivityPresenter::describe($activity->action))
                            @php($channel = $activity->metadata['channel'] ?? null)
                            @php($changes = $activity->metadata['changes'] ?? [])
                            <button class="ac-event lp-tone-{{ $tone }}" type="button" data-event="{{ json_encode([
                                'label' => $label,
                                'action' => $activity->action,
                                'when' => $activity->created_at->format('l, F j, Y · g:i:s A'),
                                'ago' => $activity->created_at->diffForHumans(),
                                'person' => $activity->actor?->name,
                                'email' => $activity->actor?->email,
                                'role' => $activity->actor?->role,
                                'target' => $activity->target_type ? class_basename($activity->target_type).' #'.$activity->target_id : null,
                                'ip' => $activity->ip_address,
                                'metadata' => $activity->metadata,
                                'filterUrl' => $activity->actor ? route('admin.activity', ['q' => $activity->actor->email]) : null,
                            ]) }}">
                                <span class="lp-badge-icon"><svg class="lp-svg" style="width:16px;height:16px" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span>
                                <span style="min-width:0">
                                    <span class="ac-event-title">
                                        <strong>{{ $label }}</strong>
                                        @if ($activity->actor?->role === 'admin')<span class="ac-tag is-admin">Admin</span>@endif
                                        @if ($channel === 'google')<span class="ac-tag is-google">Google</span>@elseif ($channel)<span class="ac-tag">{{ ucfirst($channel) }}</span>@endif
                                        @foreach (array_slice($changes, 0, 3) as $field)<span class="ac-tag">{{ str_replace('_', ' ', $field) }} changed</span>@endforeach
                                    </span>
                                    <span class="ac-event-sub">{{ $activity->actor?->name ?? 'System or deleted account' }}{{ $activity->actor ? ' · '.$activity->actor->email : '' }}{{ $activity->ip_address ? ' · '.$activity->ip_address : '' }}</span>
                                </span>
                                <span class="ac-time"><b>{{ $activity->created_at->format('g:i A') }}</b><small>{{ $activity->created_at->diffForHumans(null, true) }} ago</small></span>
                            </button>
                        @endforeach
                    </div>
                @endforeach

                <div class="au-pagination">
                    <span>Showing {{ $activities->firstItem() }}–{{ $activities->lastItem() }} of {{ $activities->total() }}</span>
                    <nav aria-label="Pages">
                        @if ($activities->previousPageUrl())<a class="au-btn is-sm" href="{{ $activities->previousPageUrl() }}">← Newer</a>@endif
                        @if ($activities->nextPageUrl())<a class="au-btn is-sm" href="{{ $activities->nextPageUrl() }}">Older →</a>@endif
                    </nav>
                </div>
            @endif
        </section>

        <aside class="ac-side">
            <section class="lp-panel">
                <div class="lp-panel-head" style="margin-bottom:14px"><div><h2>Security watch</h2><p>Signals worth keeping an eye on</p></div></div>
                <div class="ac-watch">
                    <div><b>{{ $security['signInsToday'] }}</b><span>sign-ins today</span></div>
                    <div><b>{{ $security['newAccounts'] }}</b><span>new accounts · 7d</span></div>
                    <div><b>{{ $security['accountChanges'] }}</b><span>account changes · 7d</span></div>
                    <div><b>{{ $security['uniqueIps'] }}</b><span>IP addresses · 7d</span></div>
                </div>
            </section>
            <section class="lp-panel">
                <div class="lp-panel-head" style="margin-bottom:14px"><div><h2>Most active</h2><p>People with the most events · 7 days</p></div></div>
                @forelse ($topActors as $row)
                    <div class="ac-actor">
                        <span class="au-avatar">{{ strtoupper(substr($row->actor?->name ?? '?', 0, 1)) }}</span>
                        <div style="min-width:0"><a href="{{ route('admin.activity', ['q' => $row->actor?->email]) }}"><strong>{{ $row->actor?->name ?? 'Deleted account' }}</strong></a><div class="ac-actor-bar"><i @style(['width: '.($row->total / $topActorMax * 100).'%'])></i></div></div>
                        <b>{{ $row->total }}</b>
                    </div>
                @empty
                    <p class="lp-hint" style="margin:0">No activity in the last 7 days.</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>

<div class="ac-drawer" id="event-drawer" aria-hidden="true">
    <div class="ac-drawer-backdrop" data-close-drawer></div>
    <div class="ac-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
        <div class="ac-drawer-head">
            <span class="lp-badge-icon" id="drawer-icon"></span>
            <div><h2 id="drawer-title">Event</h2><p id="drawer-when"></p></div>
            <button class="ac-drawer-close" type="button" data-close-drawer aria-label="Close details">×</button>
        </div>
        <div class="ac-drawer-body"><dl id="drawer-details" style="margin:0"></dl><pre class="ac-json" id="drawer-json" hidden></pre></div>
        <div class="ac-drawer-foot">
            <a class="au-btn is-primary" id="drawer-filter" href="#">Show this person's activity</a>
            <button class="au-btn" type="button" id="drawer-copy">Copy details</button>
        </div>
    </div>
</div>

<script>
    (() => {
        const drawer = document.getElementById('event-drawer');
        document.body.appendChild(drawer);
        const details = document.getElementById('drawer-details');
        const json = document.getElementById('drawer-json');
        const filterLink = document.getElementById('drawer-filter');
        const copyButton = document.getElementById('drawer-copy');
        let current = null;
        let lastTrigger = null;

        const row = (label, value, isCode = false) => {
            if (value === null || value === undefined || value === '') { return ''; }
            const dt = document.createElement('dt'); dt.textContent = label;
            const dd = document.createElement('dd');
            if (isCode) { const code = document.createElement('code'); code.textContent = value; dd.appendChild(code); } else { dd.textContent = value; }
            const wrap = document.createElement('div'); wrap.className = 'ac-detail'; wrap.append(dt, dd);
            return wrap;
        };

        const open = (button) => {
            current = JSON.parse(button.dataset.event);
            lastTrigger = button;
            document.querySelectorAll('.ac-event.is-selected').forEach((item) => item.classList.remove('is-selected'));
            button.classList.add('is-selected');
            const icon = document.getElementById('drawer-icon');
            icon.className = `lp-badge-icon ${[...button.classList].find((name) => name.startsWith('lp-tone-'))}`;
            icon.innerHTML = button.querySelector('.lp-badge-icon').innerHTML;
            document.getElementById('drawer-title').textContent = current.label;
            document.getElementById('drawer-when').textContent = current.ago;
            details.replaceChildren(...[
                row('When', current.when),
                row('Person', current.person ? `${current.person}${current.role === 'admin' ? ' (admin)' : ''}` : 'System or deleted account'),
                row('Email', current.email),
                row('Target', current.target),
                row('IP address', current.ip),
                row('Channel', current.metadata?.channel),
                row('Changed', current.metadata?.changes?.join(', ')),
                row('Action code', current.action, true),
            ].filter(Boolean));
            json.hidden = ! current.metadata;
            json.textContent = current.metadata ? JSON.stringify(current.metadata, null, 2) : '';
            filterLink.hidden = ! current.filterUrl;
            if (current.filterUrl) { filterLink.href = current.filterUrl; }
            copyButton.textContent = 'Copy details';
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            drawer.querySelector('.ac-drawer-close').focus();
        };
        const close = () => {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            lastTrigger?.focus();
        };

        document.querySelectorAll('[data-event]').forEach((button) => button.addEventListener('click', () => open(button)));
        drawer.querySelectorAll('[data-close-drawer]').forEach((element) => element.addEventListener('click', close));
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && drawer.classList.contains('is-open')) { close(); } });
        copyButton.addEventListener('click', async () => {
            const text = [...details.querySelectorAll('.ac-detail')].map((item) => `${item.querySelector('dt').textContent}: ${item.querySelector('dd').textContent}`).join('\n');
            try { await navigator.clipboard.writeText(text); copyButton.textContent = 'Copied ✓'; } catch (error) { copyButton.textContent = 'Copy failed'; }
        });

        const customButton = document.getElementById('custom-range');
        const customDates = document.getElementById('custom-dates');
        customButton.addEventListener('click', () => {
            const isOpen = customDates.classList.toggle('is-open');
            customButton.setAttribute('aria-expanded', String(isOpen));
            if (isOpen) { customDates.querySelector('input').focus(); }
        });

        const autoRefresh = document.getElementById('auto-refresh');
        let timer = null;
        const schedule = () => {
            clearTimeout(timer);
            if (autoRefresh.checked) {
                timer = setTimeout(() => { if (! drawer.classList.contains('is-open')) { window.location.reload(); } else { schedule(); } }, 30000);
            }
        };
        try { autoRefresh.checked = localStorage.getItem('activity-auto-refresh') === 'on'; } catch (error) {}
        autoRefresh.addEventListener('change', () => {
            try { localStorage.setItem('activity-auto-refresh', autoRefresh.checked ? 'on' : 'off'); } catch (error) {}
            schedule();
        });
        schedule();
    })();
</script>
@endsection
