@extends('layouts.app')

@section('content')
@php
    $kindMeta = [
        'Food' => ['tone' => 'green', 'icon' => '<path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/>'],
        'Symptom' => ['tone' => 'red', 'icon' => '<path d="M3 12h4l2-5 4 10 2-5h6"/>'],
        'Sleep' => ['tone' => 'violet', 'icon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>'],
    ];
    $groups = $timeline->groupBy(fn (array $event): string => $event['at']->isToday() ? 'Today' : ($event['at']->isYesterday() ? 'Yesterday' : $event['at']->format('l, M j, Y')));
    $kindCounts = $timeline->countBy('kind');
    $totalCount = $foodCount + $symptomCount + $sleepCount;
    $heatMax = max(1, collect($heatmap)->max('count'));
@endphp
@include('logs.partials.styles')
<style>
    .lp-tone-violet { --tone:#6d28d9; --tone-soft:#f5f3ff; --tone-mid:#ddd6fe; }
    .hs-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:18px; }
    .hs-filters { display:flex; flex-wrap:wrap; gap:6px; }
    .hs-filters .lp-chip.is-active { border-color:var(--green); color:#fff; background:var(--green); }
    .hs-filters .lp-chip b { padding:0 6px; border-radius:999px; background:#0000000f; font-size:11px; }
    .hs-filters .lp-chip.is-active b { background:#ffffff33; }
    .hs-search { flex:1; min-width:180px; position:relative; }
    .hs-search svg { position:absolute; top:50%; left:12px; width:16px; height:16px; color:#94a3b8; transform:translateY(-50%); }
    .hs-search input { width:100%; height:40px; padding:0 12px 0 36px; border:1.5px solid var(--line); border-radius:12px; background:#f8fafa; outline:none; font:inherit; font-size:13.5px; }
    .hs-search input:focus { border-color:#10b981; background:#fff; box-shadow:0 0 0 4px #d1fae5b3; }
    .hs-actions { display:flex; gap:8px; }
    .hs-btn { display:inline-flex; align-items:center; gap:7px; height:40px; padding:0 14px; border:1px solid var(--line); border-radius:12px; color:#334155; background:#fff; font-size:13px; font-weight:700; cursor:pointer; transition:border-color .2s ease,background .2s ease; }
    .hs-btn:hover { border-color:#a7f3d0; background:#f0fdf4; }
    .hs-btn svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.1; stroke-linecap:round; stroke-linejoin:round; }
    .hs-btn.is-primary { border-color:var(--green); color:#fff; background:var(--green); }
    .hs-btn.is-primary:hover { background:var(--green-deep); }
    .hs-event { display:grid; grid-template-columns:40px minmax(0,1fr) auto; align-items:center; gap:12px; padding:10px; border-radius:14px; transition:background .2s ease; }
    .hs-event:hover { background:#fafcfb; }
    .hs-event strong { display:block; overflow:hidden; color:var(--ink); font-size:14px; text-overflow:ellipsis; white-space:nowrap; }
    .hs-event span { display:block; overflow:hidden; color:var(--muted); font-size:12.5px; text-overflow:ellipsis; white-space:nowrap; }
    .hs-event time { color:#94a3b8; font-size:12px; font-weight:600; white-space:nowrap; }
    .hs-kind { margin-right:6px; padding:1px 7px; border-radius:999px; color:var(--tone); background:var(--tone-soft); font-size:10.5px; font-weight:700; }
    .hs-no-results { display:none; }
    .hs-profile { display:flex; align-items:center; gap:14px; }
    .hs-avatar { width:58px; height:58px; flex:none; display:grid; place-items:center; border-radius:18px; color:#fff; background:linear-gradient(135deg,#34d399,#0a5a40); box-shadow:0 12px 24px -10px #0a5a40aa; font-size:24px; font-weight:800; }
    .hs-profile strong { display:block; font-size:17px; }
    .hs-profile small { display:block; color:var(--muted); font-size:12.5px; }
    .hs-status { display:inline-flex; align-items:center; gap:6px; margin-top:6px; padding:3px 9px; border-radius:999px; color:#065f46; background:#d1fae5; font-size:11px; font-weight:700; }
    .hs-status i { width:6px; height:6px; border-radius:50%; background:#10b981; }
    .hs-facts { display:grid; gap:10px; margin-top:18px; padding-top:16px; border-top:1px solid #eef2f1; }
    .hs-fact { display:flex; justify-content:space-between; gap:10px; color:var(--muted); font-size:13px; }
    .hs-fact strong { color:var(--ink); }
    .hs-totals { display:grid; gap:10px; }
    .hs-total { display:grid; grid-template-columns:40px minmax(0,1fr) auto; align-items:center; gap:12px; padding:12px; border:1px solid #eef2f1; border-radius:14px; background:#fafcfb; }
    .hs-total span { color:var(--muted); font-size:12.5px; }
    .hs-total strong { color:var(--ink); font-size:20px; font-weight:800; }
    .hs-total-bar { grid-column:2 / -1; height:6px; overflow:hidden; border-radius:999px; background:#eef2f1; }
    .hs-total-bar i { display:block; height:100%; border-radius:inherit; background:var(--tone); transform-origin:left; animation:lpGrowX .9s ease both; }
    .hs-heat { display:grid; grid-template-columns:repeat(7,1fr); gap:5px; }
    .hs-heat span { aspect-ratio:1; border-radius:6px; background:#eef2f1; }
    .hs-heat span.l1 { background:#bbf7d0; } .hs-heat span.l2 { background:#6ee7b7; } .hs-heat span.l3 { background:#10b981; } .hs-heat span.l4 { background:#047857; }
    .hs-heat span.is-today { box-shadow:0 0 0 2px #fff,0 0 0 4px #10b981; }
    .hs-heat-legend { display:flex; align-items:center; justify-content:flex-end; gap:4px; margin-top:10px; color:#94a3b8; font-size:11px; }
    .hs-heat-legend span { width:11px; height:11px; border-radius:3px; }
    @media print {
        .topbar, .sidebar, .hs-toolbar, .lp-side > :not(.hs-print-keep), .lp-hero-stats { display:none !important; }
        .main { padding:0 !important; } .lp-hero { color:#000 !important; background:none !important; box-shadow:none !important; padding:0 0 12px !important; } .lp-hero h1 { color:#000 !important; } .lp-hero p { color:#333 !important; }
        .lp-grid { display:block !important; } .lp-panel { box-shadow:none !important; border:1px solid #ddd !important; break-inside:avoid; } .lp-history { max-height:none !important; overflow:visible !important; }
    }
</style>

<div class="shell">
    @include('partials.topbar')

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'history'])

        <main class="main lp">
            <section class="lp-hero">
                <div>
                    <span class="lp-crumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Health history</span>
                    <h1>Health History &amp; Profile</h1>
                    <p>Everything you have logged in one place. Filter it, search it, or export it to share with your healthcare provider.</p>
                </div>
                <div class="lp-hero-stats">
                    <div class="lp-hero-stat"><span>Total records</span><strong>{{ $totalCount }}</strong><small>all time</small></div>
                    <div class="lp-hero-stat"><span>Logging streak</span><strong>{{ $streak }}</strong><small>day{{ $streak === 1 ? '' : 's' }} in a row</small></div>
                    <div class="lp-hero-stat"><span>Member since</span><strong class="is-text">{{ $user->created_at->format('M Y') }}</strong><small>{{ $user->created_at->diffForHumans(null, true) }}</small></div>
                </div>
            </section>

            <div class="lp-grid">
                <section class="lp-panel">
                    <div class="lp-panel-head"><div><h2>Your health timeline</h2><p>Last 90 days · newest first</p></div></div>
                    <div class="hs-toolbar">
                        <div class="hs-filters" role="group" aria-label="Filter by type">
                            <button class="lp-chip is-active" type="button" data-filter="all" aria-pressed="true">All <b>{{ $timeline->count() }}</b></button>
                            <button class="lp-chip" type="button" data-filter="Food" aria-pressed="false">Food <b>{{ $kindCounts->get('Food', 0) }}</b></button>
                            <button class="lp-chip" type="button" data-filter="Symptom" aria-pressed="false">Symptoms <b>{{ $kindCounts->get('Symptom', 0) }}</b></button>
                            <button class="lp-chip" type="button" data-filter="Sleep" aria-pressed="false">Sleep <b>{{ $kindCounts->get('Sleep', 0) }}</b></button>
                        </div>
                        <label class="hs-search"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" id="history-search" placeholder="Search this timeline…" aria-label="Search this timeline"></label>
                        <div class="hs-actions">
                            <button class="hs-btn" type="button" onclick="window.print()"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg> Print</button>
                            <a class="hs-btn is-primary" href="{{ route('history.export') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg> Export CSV</a>
                        </div>
                    </div>

                    @if ($timeline->isEmpty())
                        <div class="lp-empty"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><strong>Nothing logged yet</strong>Your meals, symptoms and sleep will appear here.</div>
                    @else
                        <div class="lp-history" id="history-list">
                            @foreach ($groups as $day => $events)
                                <div class="lp-day" data-day>
                                    <h3>{{ $day }} <span data-day-count>{{ $events->count() }} record{{ $events->count() === 1 ? '' : 's' }}</span></h3>
                                    @foreach ($events as $event)
                                        @php($meta = $kindMeta[$event['kind']])
                                        <div class="hs-event lp-tone-{{ $meta['tone'] }}" data-kind="{{ $event['kind'] }}" data-search="{{ mb_strtolower($event['kind'].' '.$event['title'].' '.$event['detail'].' '.$event['notes']) }}">
                                            <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $meta['icon'] !!}</svg></span>
                                            <div>
                                                <strong><span class="hs-kind" style="display:inline">{{ $event['kind'] }}</span>{{ $event['title'] }}</strong>
                                                <span>{{ $event['detail'] }}{{ $event['notes'] ? ' · '.$event['notes'] : '' }}</span>
                                            </div>
                                            <time datetime="{{ $event['at']->toIso8601String() }}">{{ $event['kind'] === 'Sleep' ? 'Night' : $event['at']->format('g:i A') }}</time>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                            <div class="lp-empty hs-no-results" id="history-empty"><strong>No matching records</strong>Try another filter or search term.</div>
                        </div>
                    @endif
                </section>

                <div class="lp-side">
                    <section class="lp-panel">
                        <div class="hs-profile">
                            <span class="hs-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small><span class="hs-status"><i></i> Active patient account</span></div>
                        </div>
                        <div class="hs-facts">
                            <div class="hs-fact"><span>Member since</span><strong>{{ $user->created_at->format('F j, Y') }}</strong></div>
                            <div class="hs-fact"><span>Last activity</span><strong>{{ $timeline->first() ? $timeline->first()['at']->diffForHumans() : 'No records yet' }}</strong></div>
                            <div class="hs-fact"><span>Data privacy</span><strong>Only visible to you</strong></div>
                        </div>
                    </section>

                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>All-time totals</h2><p>Every record in your account</p></div></div>
                        <div class="hs-totals">
                            @foreach ([['Food', 'Meals logged', $foodCount], ['Symptom', 'Symptoms logged', $symptomCount], ['Sleep', 'Nights logged', $sleepCount]] as [$kind, $label, $count])
                                <div class="hs-total lp-tone-{{ $kindMeta[$kind]['tone'] }}">
                                    <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $kindMeta[$kind]['icon'] !!}</svg></span>
                                    <span>{{ $label }}</span>
                                    <strong>{{ $count }}</strong>
                                    <div class="hs-total-bar"><i style="width:{{ $totalCount ? $count / $totalCount * 100 : 0 }}%"></i></div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Logging activity</h2><p>Last 5 weeks</p></div><span class="lp-chip" style="cursor:default">{{ collect($heatmap)->where('count', '>', 0)->count() }} active days</span></div>
                        <div class="hs-heat" role="img" aria-label="Records logged per day over the last 5 weeks">
                            @foreach ($heatmap as $cell)
                                @php($level = $cell['count'] === 0 ? 0 : (int) ceil($cell['count'] / $heatMax * 4))
                                <span class="l{{ $level }} {{ $cell['date']->isToday() ? 'is-today' : '' }}" title="{{ $cell['date']->format('D, M j') }}: {{ $cell['count'] }} record{{ $cell['count'] === 1 ? '' : 's' }}"></span>
                            @endforeach
                        </div>
                        <div class="hs-heat-legend">Less <span style="background:#eef2f1"></span><span style="background:#bbf7d0"></span><span style="background:#6ee7b7"></span><span style="background:#10b981"></span><span style="background:#047857"></span> More</div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    (() => {
        const list = document.getElementById('history-list');
        if (! list) { return; }
        const filters = document.querySelectorAll('[data-filter]');
        const search = document.getElementById('history-search');
        const empty = document.getElementById('history-empty');
        let activeFilter = 'all';

        const apply = () => {
            const term = search.value.trim().toLowerCase();
            let visibleTotal = 0;
            list.querySelectorAll('[data-day]').forEach((day) => {
                let visible = 0;
                day.querySelectorAll('[data-kind]').forEach((event) => {
                    const show = (activeFilter === 'all' || event.dataset.kind === activeFilter) && (! term || event.dataset.search.includes(term));
                    event.hidden = ! show;
                    visible += show ? 1 : 0;
                });
                day.hidden = visible === 0;
                day.querySelector('[data-day-count]').textContent = `${visible} record${visible === 1 ? '' : 's'}`;
                visibleTotal += visible;
            });
            empty.style.display = visibleTotal === 0 ? 'flex' : 'none';
        };

        filters.forEach((button) => button.addEventListener('click', () => {
            activeFilter = button.dataset.filter;
            filters.forEach((item) => { item.classList.toggle('is-active', item === button); item.setAttribute('aria-pressed', String(item === button)); });
            apply();
        }));
        search.addEventListener('input', apply);
    })();
</script>
@endsection
