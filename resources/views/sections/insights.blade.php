@extends('layouts.app')

@section('content')
@php
    $risk = $analysis['risk'];
    $levelTone = ['low' => 'green', 'medium' => 'amber', 'high' => 'red'][$risk['level']] ?? 'amber';
    $trend = $analysis['trend'];
    $trendMeta = [
        'worsening' => ['label' => 'Worsening', 'tone' => 'red', 'icon' => '<path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/>'],
        'improving' => ['label' => 'Improving', 'tone' => 'green', 'icon' => '<path d="m3 7 6 6 4-4 8 8"/><path d="M14 17h7v-7"/>'],
        'stable' => ['label' => 'Stable', 'tone' => 'sky', 'icon' => '<path d="M3 12h18"/><path d="m15 6 6 6-6 6"/>'],
        'quiet' => ['label' => 'Quiet', 'tone' => 'green', 'icon' => '<path d="M20 6 9 17l-5-5"/>'],
    ][$trend['direction']];
    $confidenceMeta = ['high' => ['High confidence', 'green'], 'medium' => ['Medium confidence', 'amber'], 'low' => ['Low confidence', 'red']][$analysis['dataQuality']['level']];
    $confidenceBadge = ['strong' => ['Strong link', 'red'], 'likely' => ['Likely', 'orange'], 'possible' => ['Possible', 'amber'], 'clear' => ['No link', 'green']];
    $bucketMeta = ['morning' => ['Morning', '5–11 AM'], 'afternoon' => ['Afternoon', '11 AM–5 PM'], 'evening' => ['Evening', '5–10 PM'], 'night' => ['Night', '10 PM–5 AM']];
    $bucketMax = max(1, max($analysis['timeOfDay']['buckets']));
    $maxFactor = max(1, collect($risk['factors'])->max('points') ?? 1);
    $percent = fn (?float $rate): string => $rate === null ? '—' : (int) round($rate * 100).'%';
    $headline = $latestInsight->details['headline'] ?? $analysis['headline'];
    $points = $scoreHistory->values()->map(fn ($insight, $index) => [round($index / max(1, $scoreHistory->count() - 1) * 600, 1), round(82 - $insight->risk_score * 0.74, 1)]);
    $line = $points->map(fn ($point, $index) => ($index ? 'L' : 'M').$point[0].' '.$point[1])->implode(' ');
@endphp
@include('logs.partials.styles')
<style>
    .lp-tone-violet { --tone:#6d28d9; --tone-soft:#f5f3ff; --tone-mid:#ddd6fe; }
    .ai-hero-right { display:flex; align-items:center; gap:22px; }
    .ai-ring { position:relative; width:132px; height:132px; flex:none; }
    .ai-ring svg { width:132px; height:132px; transform:rotate(-90deg); }
    .ai-ring circle { fill:none; stroke-width:11; }
    .ai-ring .track { stroke:#ffffff1f; }
    .ai-ring .value { stroke:var(--tone); stroke-linecap:round; stroke-dasharray:100; animation:aiDraw 1.4s .2s cubic-bezier(.2,.8,.2,1) both; }
    .ai-ring div { position:absolute; inset:0; display:grid; place-content:center; text-align:center; }
    .ai-ring strong { color:#fff; font-size:34px; font-weight:800; line-height:1; letter-spacing:-.04em; }
    .ai-ring span { margin-top:4px; color:#a7f3d0; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .ai-hero-chips { display:grid; gap:8px; }
    .ai-hero-chip { display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border:1px solid #ffffff1f; border-radius:12px; color:#e2f5ee; background:#ffffff12; font-size:12.5px; white-space:nowrap; }
    .ai-hero-chip i { width:8px; height:8px; border-radius:50%; background:var(--tone); }
    .ai-hero-chip svg { width:15px; height:15px; fill:none; stroke:var(--tone); stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .ai-hero-chip strong { color:#fff; }
    .ai-refresh { display:inline-flex; align-items:center; gap:7px; margin-top:16px; padding:9px 14px; border:1px solid #6ee7b74d; border-radius:11px; color:#d1fae5; background:#ffffff14; font-size:13px; font-weight:700; cursor:pointer; transition:background .2s ease,transform .2s ease; }
    .ai-refresh:hover { background:#ffffff26; transform:translateY(-1px); }
    .ai-refresh svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .ai-refresh small { color:#86efac; font-weight:600; }
    .ai-flash { display:flex; align-items:center; gap:10px; padding:12px 16px; border:1px solid #a7f3d0; border-radius:14px; color:#065f46; background:#ecfdf5; font-size:13.5px; font-weight:600; animation:lpRise .3s ease both; }
    .ai-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(0,1fr); gap:20px; align-items:start; }
    .ai-grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px; align-items:start; }
    .ai-summary { overflow:hidden; isolation:isolate; }
    .ai-summary:before { content:""; position:absolute; inset:0; z-index:-1; padding:1.5px; border-radius:inherit; background:linear-gradient(135deg,#34d399,#a7f3d0 40%,#e0f2fe 70%,#c7d2fe); -webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0); -webkit-mask-composite:xor; mask-composite:exclude; }
    .ai-tag { display:inline-flex; align-items:center; gap:7px; color:var(--green); font-size:11.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .ai-tag svg { width:16px; height:16px; fill:currentColor; }
    .ai-summary h2 { margin:12px 0 10px; font-size:22px; font-weight:800; letter-spacing:-.03em; }
    .ai-summary p { margin:0; color:#475569; font-size:14px; line-height:1.75; }
    .ai-disclaimer { display:flex; align-items:center; gap:7px; margin-top:16px !important; color:#94a3b8 !important; font-size:12px !important; }
    .ai-factor { display:grid; gap:6px; }
    .ai-factor + .ai-factor { margin-top:14px; }
    .ai-factor-top { display:flex; align-items:baseline; justify-content:space-between; gap:10px; }
    .ai-factor-top strong { font-size:13.5px; }
    .ai-factor-top b { color:var(--tone); font-size:13px; }
    .ai-factor small { color:var(--muted); font-size:12px; }
    .ai-bar { height:8px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .ai-bar i { display:block; height:100%; border-radius:inherit; background:var(--tone); transform-origin:left; animation:lpGrowX .9s cubic-bezier(.2,.8,.2,1) both; }
    .ai-score-total { display:flex; justify-content:space-between; margin-top:16px; padding-top:14px; border-top:1px solid #eef2f1; font-size:13.5px; }
    .ai-score-total strong { color:var(--tone); }
    .ai-table { width:100%; border-collapse:collapse; font-size:13px; }
    .ai-table th { padding:0 10px 10px; color:#94a3b8; font-size:11px; font-weight:700; letter-spacing:.06em; text-align:left; text-transform:uppercase; }
    .ai-table td { padding:11px 10px; border-top:1px solid #f1f5f4; vertical-align:middle; }
    .ai-table tr:hover td { background:#fafcfb; }
    .ai-table td:first-child strong { display:block; font-size:13.5px; }
    .ai-table td:first-child small { color:var(--muted); font-size:11.5px; }
    .ai-rate { display:flex; align-items:center; gap:8px; min-width:120px; }
    .ai-rate .ai-bar { flex:1; height:7px; }
    .ai-rate b { min-width:36px; color:var(--tone); font-size:12.5px; text-align:right; }
    .ai-badge { display:inline-block; padding:3px 9px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:11px; font-weight:700; white-space:nowrap; }
    .ai-compare { display:grid; gap:12px; }
    .ai-compare-row { display:grid; grid-template-columns:minmax(0,1fr) auto auto; align-items:center; gap:12px; padding:12px 14px; border:1px solid #eef2f1; border-radius:14px; background:#fafcfb; }
    .ai-compare-row span { color:var(--muted); font-size:12.5px; }
    .ai-compare-row strong { font-size:18px; font-weight:800; letter-spacing:-.02em; }
    .ai-compare-row small { color:#94a3b8; font-size:12px; }
    .ai-delta { padding:2px 8px; border-radius:999px; color:var(--tone); background:var(--tone-soft); font-size:11.5px; font-weight:700; }
    .ai-buckets { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; align-items:end; height:130px; }
    .ai-bucket { display:flex; flex-direction:column; justify-content:flex-end; align-items:center; gap:6px; height:100%; }
    .ai-bucket i { width:100%; max-width:46px; min-height:4px; border-radius:9px 9px 3px 3px; background:#cbd5e1; transform-origin:bottom; animation:lpGrow .8s cubic-bezier(.2,.8,.2,1) both; }
    .ai-bucket.is-peak i { background:linear-gradient(to top,#7c3aed,#a78bfa); }
    .ai-bucket b { color:#475569; font-size:12px; }
    .ai-bucket-labels { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-top:8px; text-align:center; }
    .ai-bucket-labels strong { display:block; font-size:12.5px; }
    .ai-bucket-labels small { color:#94a3b8; font-size:10.5px; }
    .ai-finding { display:flex; gap:10px; margin-top:14px; padding:12px 14px; border-radius:13px; color:#334155; background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:13px; line-height:1.5; }
    .ai-finding svg { flex:none; width:18px; height:18px; color:var(--tone); }
    .ai-link { padding:14px; border:1px solid #eef2f1; border-radius:14px; background:#fafcfb; }
    .ai-link + .ai-link { margin-top:10px; }
    .ai-link strong { display:block; font-size:13.5px; }
    .ai-link p { margin:4px 0 0; color:var(--muted); font-size:12.5px; line-height:1.5; }
    .ai-link-compare { display:flex; gap:8px; margin-top:10px; }
    .ai-link-compare span { flex:1; padding:8px; border-radius:10px; color:var(--tone); background:var(--tone-soft); font-size:11.5px; font-weight:600; text-align:center; }
    .ai-link-compare b { display:block; font-size:17px; }
    .ai-meter { display:flex; gap:5px; margin:10px 0 12px; }
    .ai-meter i { flex:1; height:10px; border-radius:4px; background:#eef2f1; }
    .ai-meter i.is-on { background:var(--tone); }
    .ai-tips { display:grid; gap:8px; margin:0; padding:0; list-style:none; }
    .ai-tips li { display:flex; gap:8px; color:#475569; font-size:12.5px; line-height:1.5; }
    .ai-tips li:before { content:""; flex:none; width:6px; height:6px; margin-top:7px; border-radius:50%; background:var(--green); }
    .ai-spark { width:100%; height:90px; }
    .ai-spark path.line { fill:none; stroke:#0f7a56; stroke-width:2.5; stroke-linecap:round; stroke-linejoin:round; }
    .ai-spark path.area { fill:url(#ai-spark-fill); }
    .ai-spark circle { fill:#fff; stroke:#0f7a56; stroke-width:2; }
    .ai-history { display:grid; gap:6px; margin-top:12px; max-height:260px; overflow-y:auto; }
    .ai-history-row { display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:10px; padding:9px 10px; border-radius:11px; }
    .ai-history-row:hover { background:#fafcfb; }
    .ai-history-row strong { overflow:hidden; font-size:12.5px; text-overflow:ellipsis; white-space:nowrap; }
    .ai-history-row small { color:#94a3b8; font-size:11.5px; white-space:nowrap; }
    @keyframes aiDraw { from { stroke-dashoffset:100; } }
    @media (max-width:1280px) { .ai-grid-3 { grid-template-columns:minmax(0,1fr) minmax(0,1fr); } }
    @media (max-width:1100px) { .ai-grid, .ai-grid-3 { grid-template-columns:minmax(0,1fr); } .ai-hero-right { justify-content:flex-start; } }
    @media (max-width:700px) { .ai-hero-right { flex-direction:column; align-items:flex-start; } .ai-table th:nth-child(4), .ai-table td:nth-child(4) { display:none; } .ai-table { font-size:12px; } }
</style>

<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs><linearGradient id="ai-spark-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#10b981" stop-opacity=".25"/><stop offset="1" stop-color="#10b981" stop-opacity="0"/></linearGradient></defs></svg>

<div class="shell">
    @include('partials.topbar')

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'insights'])

        <main class="main lp">
            @if (session('status'))
                <div class="ai-flash" role="status"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>{{ session('status') }}</div>
            @endif

            <section class="lp-hero">
                <div>
                    <span class="lp-crumb"><a href="{{ route('dashboard') }}">Dashboard</a> / AI pattern insights</span>
                    <h1>AI Pattern Insights</h1>
                    <p>{{ $headline }} RefluxCare compares your meals, symptoms, sleep and stress to find what affects you personally.</p>
                    <form method="POST" action="{{ route('insights.refresh') }}">
                        @csrf
                        <button class="ai-refresh" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg> Refresh analysis <small>· updated {{ $latestInsight->generated_at->diffForHumans() }}</small></button>
                    </form>
                </div>
                <div class="ai-hero-right">
                    <div class="ai-ring lp-tone-{{ $levelTone }}" role="img" aria-label="Risk score {{ $risk['score'] }} out of 100, {{ $risk['level'] }}">
                        <svg viewBox="0 0 132 132"><circle class="track" cx="66" cy="66" r="54" pathLength="100"/><circle class="value" cx="66" cy="66" r="54" pathLength="100" style="stroke-dashoffset:{{ 100 - max(2, $risk['score']) }}"/></svg>
                        <div><strong>{{ $risk['score'] }}</strong><span>{{ ucfirst($risk['level']) }} risk</span></div>
                    </div>
                    <div class="ai-hero-chips">
                        <span class="ai-hero-chip lp-tone-{{ $trendMeta['tone'] }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $trendMeta['icon'] !!}</svg> Trend: <strong>{{ $trendMeta['label'] }}</strong></span>
                        <span class="ai-hero-chip lp-tone-{{ $confidenceMeta[1] }}"><i></i> <strong>{{ $confidenceMeta[0] }}</strong></span>
                        <span class="ai-hero-chip lp-tone-green"><i></i> Logged <strong>{{ $analysis['dataQuality']['activeDays'] }}/7</strong> days</span>
                    </div>
                </div>
            </section>

            <div class="ai-grid">
                <section class="lp-panel ai-summary">
                    <span class="ai-tag"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9zM19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z"/></svg> AI pattern summary</span>
                    <h2>{{ $analysis['headline'] }}</h2>
                    <p>{{ $analysis['summary'] }}</p>
                    <p class="ai-disclaimer"><svg class="lp-svg" style="width:14px;height:14px" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg> A tracking aid to discuss with your healthcare provider, not a medical diagnosis.</p>
                </section>

                <section class="lp-panel lp-tone-{{ $levelTone }}">
                    <div class="lp-panel-head"><div><h2>Why this score</h2><p>What adds to your risk this week</p></div></div>
                    @forelse ($risk['factors'] as $factor)
                        <div class="ai-factor lp-tone-{{ $factor['points'] >= 15 ? 'red' : ($factor['points'] >= 8 ? 'orange' : 'amber') }}">
                            <div class="ai-factor-top"><strong>{{ $factor['label'] }}</strong><b>+{{ $factor['points'] }}</b></div>
                            <div class="ai-bar"><i style="width:{{ $factor['points'] / $maxFactor * 100 }}%"></i></div>
                            <small>{{ $factor['detail'] }}</small>
                        </div>
                    @empty
                        <div class="lp-empty"><strong>Nothing is adding to your risk</strong>No symptoms or risk factors in the last 7 days.</div>
                    @endforelse
                    <div class="ai-score-total"><span>Risk score (0–100)</span><strong>{{ $risk['score'] }} · {{ ucfirst($risk['level']) }}</strong></div>
                </section>
            </div>

            <div class="ai-grid">
                <section class="lp-panel">
                    <div class="lp-panel-head"><div><h2>Food trigger analysis</h2><p>How often each food was followed by symptoms within {{ \App\Services\PatternAnalysisService::MEAL_WINDOW_HOURS }} hours · last {{ \App\Services\PatternAnalysisService::ANALYSIS_DAYS }} days</p></div></div>
                    @if (empty($analysis['foods']))
                        <div class="lp-empty"><strong>No meals logged yet</strong>Log meals and symptoms to see which foods affect you.</div>
                    @else
                        <div style="overflow-x:auto">
                            <table class="ai-table">
                                <thead><tr><th>Food</th><th>Eaten</th><th>Followed by symptoms</th><th>Avg. intensity</th><th>Link</th></tr></thead>
                                <tbody>
                                    @foreach (array_slice($analysis['foods'], 0, 8) as $food)
                                        @php([$badge, $tone] = $confidenceBadge[$food['confidence']])
                                        <tr class="lp-tone-{{ $tone }}">
                                            <td><strong>{{ $food['name'] }}</strong><small>{{ ucfirst($food['category']) }}</small></td>
                                            <td>{{ $food['eaten'] }}×</td>
                                            <td><div class="ai-rate"><div class="ai-bar"><i style="width:{{ $food['rate'] * 100 }}%"></i></div><b>{{ $percent($food['rate']) }}</b></div></td>
                                            <td>{{ $food['averageIntensity'] !== null ? $food['averageIntensity'].'/10' : '—' }}</td>
                                            <td><span class="ai-badge">{{ $badge }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="lp-hint" style="margin-top:12px">A <strong>strong link</strong> means symptoms followed at least 3 times and in 60% or more of meals. One-off matches are shown as <strong>possible</strong> until there is more data.</p>
                    @endif
                </section>

                <div class="lp-side">
                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>This week vs last week</h2><p>Last 7 days compared with the 7 before</p></div><span class="ai-badge lp-tone-{{ $trendMeta['tone'] }}">{{ $trendMeta['label'] }}</span></div>
                        <div class="ai-compare">
                            <div class="ai-compare-row lp-tone-{{ $trend['symptomChange'] > 0 ? 'red' : ($trend['symptomChange'] < 0 ? 'green' : 'sky') }}"><span>Symptoms</span><strong>{{ $analysis['week']['symptoms'] }} <small>vs {{ $trend['previousSymptoms'] }}</small></strong><span class="ai-delta">{{ $trend['symptomChange'] > 0 ? '+' : '' }}{{ $trend['symptomChange'] }}</span></div>
                            <div class="ai-compare-row lp-tone-{{ $trend['intensityChange'] > 0 ? 'red' : ($trend['intensityChange'] < 0 ? 'green' : 'sky') }}"><span>Avg. intensity</span><strong>{{ number_format($analysis['week']['averageIntensity'], 1) }} <small>vs {{ number_format($trend['previousAverageIntensity'], 1) }}</small></strong><span class="ai-delta">{{ $trend['intensityChange'] > 0 ? '+' : '' }}{{ number_format($trend['intensityChange'], 1) }}</span></div>
                            <div class="ai-compare-row lp-tone-green"><span>Meals logged</span><strong>{{ $analysis['week']['foods'] }}</strong><span class="ai-delta">this week</span></div>
                        </div>
                    </section>

                    <section class="lp-panel lp-tone-{{ $confidenceMeta[1] }}">
                        <div class="lp-panel-head"><div><h2>How reliable is this?</h2><p>Based on how often you logged this week</p></div><span class="ai-badge">{{ $confidenceMeta[0] }}</span></div>
                        <div class="ai-meter" aria-label="{{ $analysis['dataQuality']['activeDays'] }} of 7 days logged">@for ($day = 1; $day <= 7; $day++)<i class="{{ $day <= $analysis['dataQuality']['activeDays'] ? 'is-on' : '' }}"></i>@endfor</div>
                        @if ($analysis['dataQuality']['tips'])
                            <ul class="ai-tips">@foreach ($analysis['dataQuality']['tips'] as $tip)<li>{{ $tip }}</li>@endforeach</ul>
                        @else
                            <p class="lp-hint" style="margin:0">Great logging. Your patterns are based on a full week of data.</p>
                        @endif
                    </section>
                </div>
            </div>

            <div class="ai-grid-3">
                <section class="lp-panel">
                    <div class="lp-panel-head"><div><h2>When symptoms happen</h2><p>Time of day · last 30 days</p></div></div>
                    <div class="ai-buckets" role="img" aria-label="Symptoms by time of day">
                        @foreach ($analysis['timeOfDay']['buckets'] as $bucket => $count)
                            <div class="ai-bucket {{ $analysis['timeOfDay']['peak'] === $bucket ? 'is-peak' : '' }}"><b>{{ $count }}</b><i style="height:{{ max(3, $count / $bucketMax * 100) }}%"></i></div>
                        @endforeach
                    </div>
                    <div class="ai-bucket-labels">@foreach ($bucketMeta as $bucket => [$label, $range])<span><strong>{{ $label }}</strong><small>{{ $range }}</small></span>@endforeach</div>
                    @if ($analysis['lateMeals']['isPattern'])
                        <div class="ai-finding lp-tone-violet"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg><span>Symptoms followed <strong>{{ $percent($analysis['lateMeals']['lateRate']) }}</strong> of late meals (after 8 PM) but only <strong>{{ $percent($analysis['lateMeals']['earlierRate']) }}</strong> of earlier ones.</span></div>
                    @elseif ($analysis['timeOfDay']['peak'])
                        <div class="ai-finding lp-tone-violet"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>Most of your symptoms happen in the <strong>{{ $analysis['timeOfDay']['peak'] }}</strong>.</span></div>
                    @endif
                </section>

                <section class="lp-panel">
                    <div class="lp-panel-head"><div><h2>Sleep &amp; stress links</h2><p>Symptoms per day · last 30 days</p></div></div>
                    @foreach ([['sleepLink', 'Short sleep (under 6h)', 'shortAverage', 'shortNights', 'After short nights', 'sky'], ['stressLink', 'High stress (7/10 or more)', 'highAverage', 'highDays', 'On stressful days', 'amber']] as [$key, $label, $averageKey, $countKey, $matchingLabel, $tone])
                        <div class="ai-link lp-tone-{{ $tone }}">
                            <strong>{{ $label }}</strong>
                            @if ($analysis[$key])
                                <p>Linked to more symptoms for you.</p>
                                <div class="ai-link-compare"><span><b>{{ $analysis[$key][$averageKey] }}</b>{{ $matchingLabel }}</span><span style="color:#475569;background:#f1f5f9"><b>{{ $analysis[$key]['otherAverage'] }}</b>Other days</span></div>
                            @else
                                <p>No clear link found yet. It needs at least 2 matching days and 2 other days with sleep entries, and a real difference between them.</p>
                            @endif
                        </div>
                    @endforeach
                </section>

                <section class="lp-panel">
                    <div class="lp-panel-head"><div><h2>Food groups</h2><p>Compared with your average ({{ $percent($analysis['baselineRate']) }} of meals)</p></div></div>
                    @forelse ($analysis['categories'] as $category)
                        <div class="ai-factor lp-tone-{{ $category['lift'] !== null && $category['lift'] >= 1.5 ? 'red' : ($category['rate'] > 0 ? 'amber' : 'green') }}">
                            <div class="ai-factor-top"><strong>{{ ucfirst($category['category']) }}</strong><b>{{ $percent($category['rate']) }}</b></div>
                            <div class="ai-bar"><i style="width:{{ $category['rate'] * 100 }}%"></i></div>
                            <small>{{ $category['followed'] }} of {{ $category['eaten'] }} meals followed by symptoms{{ $category['lift'] !== null && $category['lift'] >= 1.5 ? ' · '.$category['lift'].'× your average' : '' }}</small>
                        </div>
                    @empty
                        <div class="lp-empty"><strong>No meals yet</strong>Food group patterns appear once you log meals.</div>
                    @endforelse
                </section>
            </div>

            <section class="lp-panel">
                <div class="lp-panel-head"><div><h2>Risk score over time</h2><p>Your last {{ $scoreHistory->count() }} analyses</p></div><a class="lp-chip" href="{{ route('recommendations') }}">See your personal plan →</a></div>
                @if ($scoreHistory->count() >= 2)
                    <svg class="ai-spark" viewBox="-6 0 612 90" preserveAspectRatio="none" role="img" aria-label="Risk score trend">
                        <path class="area" d="{{ $line }} L600 90 L0 90 Z"/>
                        <path class="line" d="{{ $line }}"/>
                        @foreach ($points as $point)<circle cx="{{ $point[0] }}" cy="{{ $point[1] }}" r="3.5" vector-effect="non-scaling-stroke"/>@endforeach
                    </svg>
                @else
                    <p class="lp-hint">Your score trend appears after a couple of analyses. Each new log refreshes it.</p>
                @endif
                <div class="ai-history">
                    @foreach ($scoreHistory->reverse()->take(6) as $insight)
                        <div class="ai-history-row">
                            <span class="ai-badge lp-tone-{{ ['low' => 'green', 'medium' => 'amber', 'high' => 'red'][$insight->risk_level] ?? 'amber' }}">{{ $insight->risk_score }} · {{ ucfirst($insight->risk_level) }}</span>
                            <strong>{{ $insight->details['headline'] ?? 'Pattern summary' }}</strong>
                            <small>{{ $insight->generated_at->format('M j, g:i A') }}</small>
                        </div>
                    @endforeach
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
