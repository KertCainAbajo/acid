@extends('layouts.app')

@section('content')
@php
    $recommendationIcons = [
        'trigger' => '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>',
        'category' => '<path d="M3 6h18M6 12h12M10 18h4"/>',
        'late-meals' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
        'timing' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'sleep' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
        'stress' => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
        'safe-foods' => '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'data' => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
        'keep-going' => '<path d="M20 6 9 17l-5-5"/>',
    ];
    $confidenceBadge = ['strong' => ['Strong link', 'red'], 'likely' => ['Likely', 'orange'], 'possible' => ['Possible', 'amber']];
    $percent = fn (?float $rate): string => $rate === null ? '—' : (int) round($rate * 100).'%';
@endphp
@include('logs.partials.styles')
<style>
    .lp-tone-violet { --tone:#6d28d9; --tone-soft:#f5f3ff; --tone-mid:#ddd6fe; }
    .rc-plan { display:grid; gap:12px; counter-reset:plan; }
    .rc-step { position:relative; display:grid; grid-template-columns:44px minmax(0,1fr); gap:14px; padding:16px; border:1px solid #eef2f1; border-radius:16px; background:#fff; transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease; animation:lpRise .45s cubic-bezier(.2,.8,.2,1) both; }
    .rc-step:nth-child(2) { animation-delay:.05s; } .rc-step:nth-child(3) { animation-delay:.1s; } .rc-step:nth-child(4) { animation-delay:.15s; } .rc-step:nth-child(5) { animation-delay:.2s; }
    .rc-step:hover { border-color:var(--tone-mid); box-shadow:0 14px 26px -18px #0f172a59; transform:translateY(-2px); }
    .rc-step:before { counter-increment:plan; content:counter(plan); position:absolute; top:-8px; left:-8px; width:22px; height:22px; display:grid; place-items:center; border-radius:50%; color:#fff; background:var(--tone); box-shadow:0 0 0 3px #fff; font-size:11px; font-weight:800; }
    .rc-step .lp-badge-icon { width:44px; height:44px; border-radius:13px; }
    .rc-step strong { display:block; color:var(--ink); font-size:15px; font-weight:750; }
    .rc-step p { margin:4px 0 0; color:#475569; font-size:13.5px; line-height:1.55; }
    .rc-watch { display:grid; gap:10px; }
    .rc-watch-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:6px 12px; align-items:center; padding:12px 14px; border:1px solid #f1f5f4; border-radius:14px; background:#fafcfb; }
    .rc-watch-row strong { font-size:14px; }
    .rc-watch-row small { grid-column:1 / -1; color:var(--muted); font-size:12px; }
    .rc-watch-bar { grid-column:1 / -1; height:7px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .rc-watch-bar i { display:block; height:100%; border-radius:inherit; background:var(--tone); transform-origin:left; animation:lpGrowX .9s cubic-bezier(.2,.8,.2,1) both; }
    .rc-badge { display:inline-block; padding:3px 9px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:11px; font-weight:700; white-space:nowrap; }
    .rc-safe { display:flex; flex-wrap:wrap; gap:8px; }
    .rc-safe span { display:inline-flex; align-items:center; gap:7px; padding:8px 12px; border:1px solid #bbf7d0; border-radius:12px; color:#065f46; background:#f0fdf4; font-size:13px; font-weight:600; }
    .rc-safe small { color:#059669; font-size:11.5px; font-weight:600; }
    .rc-guide { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .rc-guide-list { display:grid; gap:8px; }
    .rc-guide-row { display:grid; grid-template-columns:32px minmax(0,1fr) auto; align-items:center; gap:10px; padding:10px 12px; border:1px solid #f1f5f4; border-radius:13px; background:#fafcfb; }
    .rc-guide-row .lp-badge-icon { width:32px; height:32px; border-radius:10px; }
    .rc-guide-row strong { font-size:13.5px; }
    .rc-guide-row small { display:block; color:var(--muted); font-size:11.5px; }
    .rc-you { padding:3px 8px; border-radius:999px; color:var(--tone); background:var(--tone-soft); font-size:11px; font-weight:700; white-space:nowrap; }
    .rc-habits { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .rc-habit { padding:16px; border:1px solid #eef2f1; border-radius:16px; background:#fafcfb; }
    .rc-habit .lp-badge-icon { margin-bottom:10px; }
    .rc-habit strong { display:block; font-size:13.5px; }
    .rc-habit p { margin:4px 0 0; color:var(--muted); font-size:12.5px; line-height:1.5; }
    @media (max-width:1100px) { .rc-guide { grid-template-columns:1fr; } .rc-habits { grid-template-columns:1fr 1fr; } }
    @media (max-width:600px) { .rc-habits { grid-template-columns:1fr; } }
</style>

<div class="shell">
    @include('partials.topbar')

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'recommendations'])

        <main class="main lp">
            <section class="lp-hero">
                <div>
                    <span class="lp-crumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Food recommendations</span>
                    <h1>Food Recommendations</h1>
                    <p>A personal plan built from your own meals and symptoms, alongside general reflux-friendly guidance.</p>
                </div>
                <div class="lp-hero-stats">
                    <div class="lp-hero-stat"><span>Personal actions</span><strong>{{ count($analysis['recommendations']) }}</strong><small>in your plan</small></div>
                    <div class="lp-hero-stat"><span>Foods to watch</span><strong>{{ $suspectedTriggers->count() }}</strong><small>linked to symptoms</small></div>
                    <div class="lp-hero-stat"><span>Foods that suit you</span><strong>{{ $wellTolerated->count() }}</strong><small>no symptoms after</small></div>
                </div>
            </section>

            <div class="lp-grid">
                <section class="lp-panel">
                    <div class="lp-panel-head"><div><h2>Your personal plan</h2><p>Most important first · based on your last 30 days</p></div><a class="lp-chip" href="{{ route('insights') }}">See the evidence →</a></div>
                    <div class="rc-plan">
                        @foreach ($analysis['recommendations'] as $recommendation)
                            <article class="rc-step lp-tone-{{ $recommendation['tone'] }}">
                                <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $recommendationIcons[$recommendation['key']] ?? $recommendationIcons['keep-going'] !!}</svg></span>
                                <div><strong>{{ $recommendation['title'] }}</strong><p>{{ $recommendation['detail'] }}</p></div>
                            </article>
                        @endforeach
                    </div>
                    <p class="lp-hint" style="margin-top:16px">These suggestions come from patterns in your own logs. Talk to your healthcare provider before making big changes to your diet or medication.</p>
                </section>

                <div class="lp-side">
                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Your trigger watchlist</h2><p>Foods followed by symptoms within 6 hours</p></div></div>
                        @if ($suspectedTriggers->isEmpty())
                            <div class="lp-empty"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg><strong>No suspected triggers</strong>Nothing you ate has been followed by symptoms in the last 30 days.</div>
                        @else
                            <div class="rc-watch">
                                @foreach ($suspectedTriggers->take(6) as $food)
                                    @php([$badge, $tone] = $confidenceBadge[$food['confidence']])
                                    <div class="rc-watch-row lp-tone-{{ $tone }}">
                                        <strong>{{ $food['name'] }}</strong>
                                        <span class="rc-badge">{{ $badge }}</span>
                                        <div class="rc-watch-bar"><i style="width:{{ $food['rate'] * 100 }}%"></i></div>
                                        <small>Symptoms after {{ $food['followed'] }} of {{ $food['eaten'] }} times ({{ $percent($food['rate']) }}){{ $food['averageIntensity'] ? ' · avg '.$food['averageIntensity'].'/10' : '' }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Foods that suit you</h2><p>Eaten with no symptoms afterwards</p></div></div>
                        @if ($wellTolerated->isEmpty())
                            <div class="lp-empty"><strong>Not enough meals yet</strong>Foods you eat without symptoms will appear here.</div>
                        @else
                            <div class="rc-safe">
                                @foreach ($wellTolerated->take(10) as $food)
                                    <span>{{ $food['name'] }} <small>{{ $food['eaten'] }}×</small></span>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            </div>

            <section class="lp-panel">
                <div class="lp-panel-head"><div><h2>General guidance</h2><p>Common reflux-friendly foods and common triggers, with your own results where you have them</p></div></div>
                <div class="rc-guide">
                    @foreach ([['Usually gentle', $safeFoods, 'green', '<path d="M20 6 9 17l-5-5"/>'], ['Common triggers', $avoidFoods, 'orange', '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>']] as [$heading, $foods, $tone, $icon])
                        <div>
                            <h3 style="margin:0 0 10px;font-size:14px">{{ $heading }}</h3>
                            <div class="rc-guide-list">
                                @forelse ($foods as $food)
                                    <div class="rc-guide-row lp-tone-{{ $tone }}">
                                        <span class="lp-badge-icon"><svg class="lp-svg" style="width:15px;height:15px" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span>
                                        <div><strong>{{ $food['name'] }}</strong></div>
                                        @if ($food['personal'])
                                            @php($personalTone = $food['personal']['followed'] > 0 ? 'red' : 'green')
                                            <span class="rc-you lp-tone-{{ $personalTone }}">You: {{ $food['personal']['followed'] > 0 ? 'symptoms '.$food['personal']['followed'].'/'.$food['personal']['eaten'] : 'fine '.$food['personal']['eaten'].'×' }}</span>
                                        @else
                                            <span class="rc-you" style="color:#94a3b8;background:#f1f5f9">Not logged</span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="lp-empty">No foods listed yet.</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="lp-panel">
                <div class="lp-panel-head"><div><h2>Everyday habits that help</h2><p>General guidance for most people with reflux</p></div></div>
                <div class="rc-habits">
                    <div class="rc-habit lp-tone-green"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h18M12 3v18"/></svg></span><strong>Smaller meals</strong><p>Several smaller meals put less pressure on your stomach than a few large ones.</p></div>
                    <div class="rc-habit lp-tone-violet"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><strong>Eat earlier</strong><p>Finish eating about 3 hours before lying down or going to bed.</p></div>
                    <div class="rc-habit lp-tone-sky"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11Z"/></svg></span><strong>Mind your drinks</strong><p>Coffee, alcohol and fizzy drinks are common triggers. Water is the safest choice.</p></div>
                    <div class="rc-habit lp-tone-amber"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg></span><strong>Stay upright</strong><p>Stay upright after meals and avoid tight clothing around your waist.</p></div>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
