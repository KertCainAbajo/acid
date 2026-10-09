@extends('layouts.app')

@section('content')
@php
    $qualityMeta = [
        1 => ['label' => 'Poor', 'hint' => 'Restless, woke often', 'tone' => 'red'],
        2 => ['label' => 'Fair', 'hint' => 'Light or broken sleep', 'tone' => 'orange'],
        3 => ['label' => 'Average', 'hint' => 'Okay, not great', 'tone' => 'amber'],
        4 => ['label' => 'Good', 'hint' => 'Mostly restful', 'tone' => 'green'],
        5 => ['label' => 'Excellent', 'hint' => 'Woke up refreshed', 'tone' => 'green'],
    ];
    $hoursTone = fn (?float $hours): string => $hours === null ? 'sky' : ($hours < 6 ? 'red' : ($hours < 7 ? 'amber' : 'green'));
    $stressTone = fn (?int $stress): string => $stress === null ? 'sky' : ($stress >= 8 ? 'red' : ($stress >= 6 ? 'orange' : ($stress >= 4 ? 'amber' : 'green')));
    $latest = $recentLogs->first();
    $dayLabel = fn ($date): string => $date->isToday() ? 'Today' : ($date->isYesterday() ? 'Yesterday' : $date->format('D, M j'));
    $contextTags = ['Late meal', 'Caffeine', 'Woke up with reflux', 'Screen time in bed', 'Exercised', 'Work stress'];
    $sleepAdvice = match (true) {
        $averageHours === null => 'Log a few nights to see how your sleep and stress relate to your reflux.',
        $averageHours < 6 => 'You are averaging under 6 hours. Short sleep is one of the factors that raises your reflux risk estimate.',
        $averageHours < 7 => 'You are averaging just under 7 hours. A little more rest could lower your risk estimate.',
        $averageStress !== null && $averageStress >= 6 => 'Your sleep looks healthy, but stress is running high. High stress also raises your risk estimate.',
        default => 'Your sleep and stress are in a healthy range this week. Keep it up.',
    };
@endphp
@include('logs.partials.styles')
@include('logs.partials.entry-actions')
<style>
    .st-tag.is-active { border-color:var(--green); color:var(--green-deep); background:#ecfdf5; }
    .lp-tone-violet { --tone:#6d28d9; --tone-soft:#f5f3ff; --tone-mid:#ddd6fe; }
    .sl-hours { display:grid; grid-template-columns:auto minmax(0,1fr); align-items:center; gap:22px; padding:18px; border:1.5px solid var(--line); border-radius:16px; background:#fafcfb; transition:border-color .2s ease,box-shadow .2s ease; }
    .sl-hours:focus-within { border-color:#10b981; box-shadow:0 0 0 4px #d1fae5b3; }
    .sl-stepper { display:flex; align-items:center; gap:10px; }
    .sl-stepper button { width:42px; height:42px; display:grid; place-items:center; padding:0; border:1px solid var(--line); border-radius:12px; color:#334155; background:#fff; font-size:20px; font-weight:700; cursor:pointer; transition:border-color .15s ease,background .15s ease,transform .15s ease; }
    .sl-stepper button:hover { border-color:#a7f3d0; background:#f0fdf4; transform:translateY(-1px); }
    .sl-stepper button:focus-visible { outline:2px solid #10b981; outline-offset:2px; }
    .sl-hours-value { display:flex; align-items:baseline; gap:4px; min-width:112px; justify-content:center; }
    .sl-hours-value input { width:82px; padding:0; border:0; color:var(--tone); background:transparent; outline:none; font-size:40px; font-weight:800; letter-spacing:-.04em; text-align:right; -moz-appearance:textfield; }
    .sl-hours-value input::-webkit-outer-spin-button, .sl-hours-value input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
    .sl-hours-value span { color:#94a3b8; font-size:15px; font-weight:700; }
    .sl-hours-meta { display:grid; gap:10px; }
    .sl-hours-meta .lp-range { background:linear-gradient(90deg,#f87171 0%,#fbbf24 42%,#34d399 58%,#34d399 75%,#60a5fa 100%); }
    .sl-hours-row { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .sl-goal-pill { padding:5px 11px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:12.5px; font-weight:700; }
    .sl-calc-toggle { display:inline-flex; align-items:center; gap:6px; padding:0; border:0; color:var(--green); background:none; font-size:12.5px; font-weight:700; cursor:pointer; }
    .sl-calc-toggle svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .sl-calc { display:none; grid-template-columns:1fr 1fr auto; gap:10px; align-items:end; margin-top:12px; padding:14px; border:1px dashed #a7f3d0; border-radius:14px; background:#f0fdf4; }
    .sl-calc.is-open { display:grid; animation:lpRise .25s ease both; }
    .sl-calc label { display:grid; gap:5px; color:#334155; font-size:12px; font-weight:700; }
    .sl-calc input { height:42px; padding:0 10px; border:1px solid var(--line); border-radius:10px; background:#fff; font:inherit; font-size:14px; }
    .sl-calc output { padding-bottom:10px; color:var(--green-deep); font-size:13px; font-weight:700; white-space:nowrap; }
    .sl-quality { grid-template-columns:repeat(5,minmax(0,1fr)); }
    .sl-quality .lp-category { min-height:0; align-items:center; gap:6px; padding:14px 8px; text-align:center; }
    .sl-quality .lp-category-check { top:8px; right:8px; width:18px; height:18px; }
    .sl-stars { display:flex; gap:2px; color:var(--tone); }
    .sl-stars svg { width:13px; height:13px; fill:currentColor; }
    .sl-stars svg.is-off { fill:#e2e8f0; }
    .sl-date-note { display:none; margin-top:8px; padding:9px 12px; border-radius:11px; color:#92400e; background:#fffbeb; box-shadow:inset 0 0 0 1px #fde68a; font-size:12.5px; font-weight:600; }
    .sl-date-note.is-on { display:block; }
    .sl-goal-line { position:absolute; left:0; right:0; z-index:1; border-top:1.5px dashed #10b98199; pointer-events:none; }
    .sl-goal-line span { position:absolute; right:0; top:-18px; padding:1px 6px; border-radius:6px; color:#047857; background:#ecfdf5; font-size:10.5px; font-weight:700; }
    .sl-stress-row { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; margin-top:10px; }
    .sl-stress-row span { display:grid; place-items:center; height:24px; border-radius:7px; color:var(--tone); background:var(--tone-soft); font-size:11px; font-weight:700; }
    .sl-stress-row span.is-empty { color:#cbd5e1; background:#f8fafc; }
    .sl-averages { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .sl-average { padding:12px; border:1px solid #eef2f1; border-radius:14px; background:#fafcfb; }
    .sl-average span { display:block; color:var(--muted); font-size:11.5px; }
    .sl-average strong { display:block; margin-top:2px; color:var(--tone); font-size:20px; font-weight:800; letter-spacing:-.03em; }
    .sl-average small { color:#94a3b8; font-size:12px; font-weight:600; }
    .sl-advice { display:flex; gap:10px; margin-top:14px; padding:12px 14px; border-radius:13px; color:#334155; background:linear-gradient(120deg,#f5f7ff,#f0fdf4); box-shadow:inset 0 0 0 1px #e0e7ff; font-size:13px; line-height:1.55; }
    .sl-advice svg { flex:none; width:18px; height:18px; margin-top:1px; color:var(--green); }
    .sl-entry { display:grid; grid-template-columns:40px minmax(0,1fr) auto; gap:12px; padding:12px 10px; border:1px solid transparent; border-radius:14px; }
    .sl-entry + .sl-entry { margin-top:4px; }
    .sl-entry:hover { border-color:#eef2f1; background:#fafcfb; }
    .sl-entry strong { color:var(--ink); font-size:14px; }
    .sl-entry-pills { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
    .sl-pill { padding:2px 8px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:11px; font-weight:700; }
    .sl-entry p { margin:6px 0 0; color:var(--muted); font-size:12.5px; line-height:1.45; overflow-wrap:anywhere; }
    .sl-entry time { color:#64748b; font-size:12px; font-weight:700; white-space:nowrap; }
    .sl-tips { display:grid; gap:6px; margin:6px 0 0; padding-left:18px; color:#475569; font-size:13px; line-height:1.5; }
    @media (max-width:1400px) { .sl-quality { grid-template-columns:repeat(5,minmax(0,1fr)); } }
    @media (max-width:700px) { .sl-hours { grid-template-columns:1fr; justify-items:center; } .sl-hours-meta { width:100%; } .sl-quality { grid-template-columns:repeat(5,minmax(0,1fr)); gap:6px; } .sl-quality small { display:none; } .sl-calc { grid-template-columns:1fr 1fr; } .sl-averages { grid-template-columns:repeat(3,minmax(0,1fr)); } }
</style>

<div class="shell">
    @include('partials.topbar')

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'sleep'])

        <main class="main lp">
            <section class="lp-hero">
                <div>
                    <span class="lp-crumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Sleep &amp; stress</span>
                    <h1>Sleep &amp; Stress</h1>
                    <p>Record how you slept and how stressed you felt. Short sleep and high stress both feed into your reflux risk estimate.</p>
                </div>
                <div class="lp-hero-stats">
                    <div class="lp-hero-stat"><span>Latest night</span><strong>{{ $latest ? number_format($latest->sleep_hours, 1).'h' : '—' }}</strong><small>{{ $latest ? $dayLabel($latest->logged_on) : 'not logged yet' }}</small></div>
                    <div class="lp-hero-stat"><span>7-day avg. sleep</span><strong>{{ $averageHours !== null ? number_format($averageHours, 1).'h' : '—' }}</strong><small>goal 7–9 hours</small></div>
                    <div class="lp-hero-stat"><span>7-day avg. stress</span><strong>{{ $averageStress !== null ? number_format($averageStress, 1) : '—' }}</strong><small>{{ $averageStress !== null ? 'out of 10' : 'no entries yet' }}</small></div>
                </div>
            </section>

            <div class="lp-grid">
                <section class="lp-panel">
                    <div class="lp-panel-head">
                        <div><h2>How did you sleep?</h2><p>One entry per night. Only the fields marked <span style="color:#dc2626">*</span> are required.</p></div>
                    </div>

                    <div class="lp-progress" aria-hidden="true">
                        <div class="lp-progress-step" data-step="hours"><b><span>1</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Hours</div>
                        <div class="lp-progress-step" data-step="quality"><b><span>2</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Quality</div>
                        <div class="lp-progress-step" data-step="stress"><b><span>3</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Stress</div>
                    </div>

                    <form id="log-form" novalidate>
                        <div class="lp-field">
                            <div class="lp-label"><label for="sleep_hours">How many hours did you sleep? <em>*</em></label><small>Recommended: 7–9 hours</small></div>
                            <div class="sl-hours lp-tone-green" id="hours-box">
                                <div class="sl-stepper">
                                    <button type="button" data-step-hours="-0.5" aria-label="Half an hour less">−</button>
                                    <div class="sl-hours-value"><input id="sleep_hours" name="sleep_hours" type="number" inputmode="decimal" min="0" max="24" step="0.25" value="7" required aria-describedby="sleep_hours-error"><span>h</span></div>
                                    <button type="button" data-step-hours="0.5" aria-label="Half an hour more">+</button>
                                </div>
                                <div class="sl-hours-meta">
                                    <input class="lp-range" id="hours-range" type="range" min="0" max="12" step="0.25" value="7" aria-label="Hours slept">
                                    <div class="sl-hours-row">
                                        <span class="sl-goal-pill" id="hours-label">On target</span>
                                        <button class="sl-calc-toggle" type="button" id="calc-toggle" aria-expanded="false" aria-controls="sleep-calc"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> Calculate from bedtime</button>
                                    </div>
                                </div>
                            </div>
                            <div class="sl-calc" id="sleep-calc">
                                <label>Went to bed<input type="time" id="bedtime" value="23:00"></label>
                                <label>Woke up<input type="time" id="waketime" value="06:30"></label>
                                <output id="calc-result" for="bedtime waketime">7.5 hours</output>
                            </div>
                            <p class="lp-error" id="sleep_hours-error" data-error-for="sleep_hours"></p>
                        </div>

                        <div class="lp-field">
                            <div class="lp-label" id="quality-label"><span>How well did you sleep? <em>*</em></span><small>Tap again to clear</small></div>
                            <fieldset class="lp-categories sl-quality" aria-labelledby="quality-label" aria-describedby="sleep_quality-error" id="sleep_quality-group">
                                @foreach ($qualityMeta as $value => $meta)
                                    <label class="lp-category lp-tone-{{ $meta['tone'] }}">
                                        <input type="radio" name="sleep_quality" value="{{ $value }}" required>
                                        <span class="sl-stars" aria-hidden="true">@for ($star = 1; $star <= 5; $star++)<svg viewBox="0 0 24 24" class="{{ $star <= $value ? '' : 'is-off' }}"><path d="m12 2 3 6.6 7 .7-5.3 4.8 1.6 7L12 17.5 5.7 21l1.6-7L2 9.3l7-.7Z"/></svg>@endfor</span>
                                        <span><strong>{{ $meta['label'] }}</strong><small>{{ $meta['hint'] }}</small></span>
                                        <span class="lp-category-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <p class="lp-error" id="sleep_quality-error" data-error-for="sleep_quality"></p>
                        </div>

                        <div class="lp-field">
                            <label class="lp-label" for="stress_level"><span>How stressed did you feel? <em>*</em></span><small>1 = very calm · 10 = very stressed</small></label>
                            <div class="lp-meter lp-tone-green" id="stress-box">
                                <div class="lp-meter-top">
                                    <div class="lp-meter-value"><strong id="stress-value">3</strong><span>/ 10</span></div>
                                    <span class="lp-meter-label" id="stress-label">Low</span>
                                </div>
                                <input class="lp-range" id="stress_level" type="range" name="stress_level" min="1" max="10" step="1" value="3" aria-describedby="stress_level-error" aria-valuetext="3 out of 10, Low">
                                <div class="lp-scale" role="group" aria-label="Pick a stress level">
                                    @for ($level = 1; $level <= 10; $level++)
                                        <button type="button" data-level="{{ $level }}" class="{{ $level === 3 ? 'is-active' : '' }}" aria-label="Stress {{ $level }}">{{ $level }}</button>
                                    @endfor
                                </div>
                                <div class="lp-scale-ends"><span>Very calm</span><span>Very stressed</span></div>
                            </div>
                            <p class="lp-error" id="stress_level-error" data-error-for="stress_level"></p>
                        </div>

                        <div class="lp-field">
                            <label class="lp-label" for="logged_on"><span>Which night is this for? <em>*</em></span><small>Use the date you woke up</small></label>
                            <div class="lp-time">
                                <div class="lp-input-wrap">
                                    <svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                                    <input class="lp-input" id="logged_on" type="date" name="logged_on" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required aria-describedby="logged_on-note logged_on-error">
                                </div>
                                <div class="lp-presets" role="group" aria-label="Quick date presets">
                                    <button class="lp-chip is-active" type="button" data-days-ago="0">Today</button>
                                    <button class="lp-chip" type="button" data-days-ago="1">Yesterday</button>
                                    <button class="lp-chip" type="button" data-days-ago="2">2 days ago</button>
                                </div>
                            </div>
                            <p class="sl-date-note" id="logged_on-note" role="status">You already logged this night. Saving will replace that entry.</p>
                            <p class="lp-error" id="logged_on-error" data-error-for="logged_on"></p>
                        </div>

                        <div class="lp-field">
                            <label class="lp-label" for="notes"><span>Anything that affected your night? <small>(optional)</small></span><small id="notes-count">0 / 2000</small></label>
                            <div class="lp-chips" style="margin:0 0 10px">
                                @foreach ($contextTags as $tag)
                                    <button class="lp-chip st-tag" type="button" data-tag="{{ $tag }}" aria-pressed="false">+ {{ $tag }}</button>
                                @endforeach
                            </div>
                            <textarea class="lp-textarea" id="notes" name="notes" maxlength="2000" placeholder="Example: ate dinner at 10 PM and woke up with heartburn."></textarea>
                            <p class="lp-error" data-error-for="notes"></p>
                        </div>

                        <div class="lp-actions">
                            <button class="lp-submit" id="log-submit" type="submit">
                                <span id="log-submit-label">Save sleep entry</span>
                                <svg class="lp-svg lp-submit-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                <span class="lp-spinner" aria-hidden="true"></span>
                            </button>
                            <span class="lp-kbd"><kbd>Ctrl</kbd> + <kbd>Enter</kbd></span>
                        </div>
                        <div id="log-status" class="lp-status" role="status" aria-live="polite"></div>
                    </form>
                </section>

                <div class="lp-side">
                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Last 7 nights</h2><p>Hours slept, with stress below</p></div></div>
                        <div class="lp-chart" style="position:relative" role="img" aria-label="Hours slept for each of the last 7 nights">
                            <div class="sl-goal-line" style="bottom:{{ 7 / 12 * 100 }}%"><span>7h goal</span></div>
                            @foreach ($weeklySleep as $index => $night)
                                <div class="lp-col {{ $night['isToday'] ? 'is-today' : '' }}">
                                    @if ($night['hours'] !== null)<b>{{ rtrim(rtrim(number_format($night['hours'], 2), '0'), '.') }}</b>@endif
                                    <i class="{{ $night['hours'] !== null ? 'lp-tone-'.$hoursTone($night['hours']) : 'is-zero' }}" style="height:{{ $night['hours'] !== null ? min(100, $night['hours'] / 12 * 100) : 3 }}%;animation-delay:{{ $index * 0.05 }}s"></i>
                                    <span class="lp-chart-tip"><strong style="display:block">{{ $night['label'] }}, {{ $night['date'] }}</strong>{{ $night['hours'] !== null ? number_format($night['hours'], 1).'h · quality '.$night['quality'].'/5 · stress '.$night['stress'].'/10' : 'Not logged' }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="lp-days">@foreach ($weeklySleep as $night)<span class="{{ $night['isToday'] ? 'is-today' : '' }}">{{ $night['isToday'] ? 'Today' : $night['label'] }}</span>@endforeach</div>
                        <div class="sl-stress-row" aria-label="Stress per night">
                            @foreach ($weeklySleep as $night)
                                <span class="lp-tone-{{ $stressTone($night['stress']) }} {{ $night['stress'] === null ? 'is-empty' : '' }}" title="Stress {{ $night['stress'] ?? '—' }}/10">{{ $night['stress'] ?? '–' }}</span>
                            @endforeach
                        </div>
                        <div class="lp-chart-legend">
                            <span class="lp-tone-green"><i></i> 7h or more</span><span class="lp-tone-amber"><i></i> 6–7h</span><span class="lp-tone-red"><i></i> Under 6h</span><span style="margin-left:auto">Row below = stress /10</span>
                        </div>
                    </section>

                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Your week at a glance</h2><p>Averages over the last 7 nights</p></div></div>
                        <div class="sl-averages">
                            <div class="sl-average lp-tone-{{ $hoursTone($averageHours) }}"><span>Sleep</span><strong>{{ $averageHours !== null ? number_format($averageHours, 1) : '—' }}<small> h</small></strong></div>
                            <div class="sl-average lp-tone-{{ $averageQuality === null ? 'sky' : ($averageQuality >= 4 ? 'green' : ($averageQuality >= 3 ? 'amber' : 'red')) }}"><span>Quality</span><strong>{{ $averageQuality !== null ? number_format($averageQuality, 1) : '—' }}<small> /5</small></strong></div>
                            <div class="sl-average lp-tone-{{ $stressTone($averageStress === null ? null : (int) round($averageStress)) }}"><span>Stress</span><strong>{{ $averageStress !== null ? number_format($averageStress, 1) : '—' }}<small> /10</small></strong></div>
                        </div>
                        <div class="sl-advice"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9z"/></svg><span>{{ $sleepAdvice }}</span></div>
                    </section>

                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Your sleep history</h2><p>Hover an entry to edit or delete it</p></div></div>
                        @if ($recentLogs->isEmpty())
                            <div class="lp-empty">
                                <svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                                <strong>No nights logged yet</strong>
                                Your saved sleep entries will appear here.
                            </div>
                        @else
                            <div class="lp-history">
                                @foreach ($recentLogs as $log)
                                    @php($quality = $qualityMeta[$log->sleep_quality] ?? $qualityMeta[3])
                                    <div class="sl-entry lp-entry lp-tone-{{ $hoursTone($log->sleep_hours) }}" data-entry="{{ json_encode(['id' => $log->id, 'label' => $dayLabel($log->logged_on).' · '.number_format($log->sleep_hours, 1).'h', 'sleep_hours' => $log->sleep_hours, 'sleep_quality' => $log->sleep_quality, 'stress_level' => $log->stress_level, 'logged_on' => $log->logged_on->toDateString(), 'notes' => $log->notes]) }}">
                                        <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg></span>
                                        <div>
                                            <strong>{{ number_format($log->sleep_hours, 1) }} hours of sleep</strong>
                                            <div class="sl-entry-pills">
                                                <span class="sl-pill lp-tone-{{ $quality['tone'] }}">Quality {{ $log->sleep_quality }}/5 · {{ $quality['label'] }}</span>
                                                <span class="sl-pill lp-tone-{{ $stressTone($log->stress_level) }}">Stress {{ $log->stress_level }}/10</span>
                                            </div>
                                            @if ($log->notes)<p>{{ $log->notes }}</p>@endif
                                        </div>
                                        <div class="lp-entry-side">
                                            <time datetime="{{ $log->logged_on->toDateString() }}" title="{{ $log->logged_on->format('l, F j, Y') }}">{{ $dayLabel($log->logged_on) }}</time>
                                            <div class="lp-entry-actions">
                                                <button class="lp-icon-btn" type="button" data-edit aria-label="Edit the entry for {{ $log->logged_on->format('M j') }}" title="Edit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                                                <button class="lp-icon-btn is-danger" type="button" data-delete aria-label="Delete the entry for {{ $log->logged_on->format('M j') }}" title="Delete"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg></button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <div class="lp-tip">
                        <span class="lp-badge-icon lp-tone-violet"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg></span>
                        <div><strong>Sleep habits that can ease night-time reflux</strong>
                            <ul class="sl-tips">
                                <li>Finish eating about 3 hours before lying down.</li>
                                <li>Raise the head of your bed slightly.</li>
                                <li>Many people find sleeping on their left side helps.</li>
                            </ul>
                            <p style="margin-top:6px;font-size:11.5px;color:#94a3b8">General guidance only. Ask your healthcare provider what is right for you.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    (() => {
        const form = document.getElementById('log-form');
        const status = document.getElementById('log-status');
        const submit = document.getElementById('log-submit');
        const submitLabel = document.getElementById('log-submit-label');
        const hoursInput = document.getElementById('sleep_hours');
        const hoursRange = document.getElementById('hours-range');
        const hoursBox = document.getElementById('hours-box');
        const hoursLabel = document.getElementById('hours-label');
        const stressRange = document.getElementById('stress_level');
        const stressBox = document.getElementById('stress-box');
        const stressValue = document.getElementById('stress-value');
        const stressLabel = document.getElementById('stress-label');
        const stressButtons = document.querySelectorAll('[data-level]');
        const dateInput = document.getElementById('logged_on');
        const dateNote = document.getElementById('logged_on-note');
        const datePresets = document.querySelectorAll('[data-days-ago]');
        const notesInput = document.getElementById('notes');
        const notesCount = document.getElementById('notes-count');
        const calcToggle = document.getElementById('calc-toggle');
        const calcBox = document.getElementById('sleep-calc');
        const bedtime = document.getElementById('bedtime');
        const waketime = document.getElementById('waketime');
        const calcResult = document.getElementById('calc-result');
        const token = @json(session('api_token'));
        const serverToday = @json(now()->toDateString());
        const loggedDates = new Set(@json($loggedDates));
        const submitText = 'Save sleep entry';
        let stressTouched = false;
        let selectedQuality = null;
        const icons = {
            success: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
            error: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>',
        };

        const setStatus = (type, message) => {
            status.className = `lp-status is-${type}`;
            status.innerHTML = `${icons[type]}<span></span>`;
            status.querySelector('span').textContent = message;
        };
        const clearErrors = () => {
            form.querySelectorAll('[data-error-for]').forEach((element) => { element.textContent = ''; element.classList.remove('is-on'); });
            form.querySelectorAll('[aria-invalid]').forEach((element) => element.removeAttribute('aria-invalid'));
        };
        const showError = (field, message) => {
            const target = form.querySelector(`[data-error-for="${field}"]`);
            if (target) { target.textContent = message; target.classList.add('is-on'); }
            const control = field === 'sleep_quality' ? document.getElementById('sleep_quality-group') : form.elements[field];
            control?.setAttribute?.('aria-invalid', 'true');
        };
        const updateProgress = () => {
            const done = {
                hours: hoursInput.value !== '' && Number(hoursInput.value) >= 0,
                quality: !! form.querySelector('input[name="sleep_quality"]:checked'),
                stress: stressTouched,
            };
            Object.entries(done).forEach(([step, isDone]) => document.querySelector(`[data-step="${step}"]`).classList.toggle('is-done', isDone));
        };

        const setHours = (value, fromRange = false) => {
            const hours = Math.min(24, Math.max(0, Math.round(Number(value) * 4) / 4));
            if (! fromRange) { hoursRange.value = Math.min(12, hours); }
            hoursInput.value = hours;
            const [label, tone] = hours < 6 ? ['Under 6 hours', 'red'] : hours < 7 ? ['A bit short', 'amber'] : hours <= 9 ? ['On target', 'green'] : ['Long night', 'sky'];
            hoursLabel.textContent = label;
            hoursBox.className = `sl-hours lp-tone-${tone}`;
            updateProgress();
        };
        hoursRange.addEventListener('input', () => setHours(hoursRange.value, true));
        hoursInput.addEventListener('change', () => setHours(hoursInput.value || 0));
        document.querySelectorAll('[data-step-hours]').forEach((button) => button.addEventListener('click', () => setHours(Number(hoursInput.value || 0) + Number(button.dataset.stepHours))));

        const calculate = () => {
            const [bedHours, bedMinutes] = bedtime.value.split(':').map(Number);
            const [wakeHours, wakeMinutes] = waketime.value.split(':').map(Number);
            if ([bedHours, bedMinutes, wakeHours, wakeMinutes].some(Number.isNaN)) { return; }
            let minutes = (wakeHours * 60 + wakeMinutes) - (bedHours * 60 + bedMinutes);
            if (minutes <= 0) { minutes += 24 * 60; }
            const hours = Math.round(minutes / 15) / 4;
            calcResult.textContent = `${hours} hours`;
            setHours(hours);
        };
        calcToggle.addEventListener('click', () => {
            const isOpen = calcBox.classList.toggle('is-open');
            calcToggle.setAttribute('aria-expanded', String(isOpen));
            if (isOpen) { calculate(); bedtime.focus(); }
        });
        bedtime.addEventListener('input', calculate);
        waketime.addEventListener('input', calculate);

        const stressLevel = (level) => level >= 9 ? ['Very high', 'red'] : level >= 7 ? ['High', 'orange'] : level >= 5 ? ['Moderate', 'amber'] : level >= 3 ? ['Low', 'green'] : ['Very low', 'green'];
        const setStress = (level, touched = true) => {
            const [label, tone] = stressLevel(level);
            stressRange.value = level;
            stressValue.textContent = level;
            stressLabel.textContent = label;
            stressBox.className = `lp-meter lp-tone-${tone}`;
            stressRange.setAttribute('aria-valuetext', `${level} out of 10, ${label}`);
            stressButtons.forEach((button) => button.classList.toggle('is-active', Number(button.dataset.level) === level));
            stressTouched = touched;
            updateProgress();
        };
        stressRange.addEventListener('input', () => setStress(Number(stressRange.value)));
        stressButtons.forEach((button) => button.addEventListener('click', () => setStress(Number(button.dataset.level))));

        form.querySelectorAll('input[name="sleep_quality"]').forEach((radio) => radio.addEventListener('click', () => {
            if (radio.value === selectedQuality) { radio.checked = false; selectedQuality = null; } else { selectedQuality = radio.value; }
            updateProgress();
        }));

        const shiftDate = (isoDate, days) => {
            const date = new Date(`${isoDate}T12:00:00`);
            date.setDate(date.getDate() - days);
            return date.toISOString().slice(0, 10);
        };
        const updateDateNote = () => {
            const editingDate = form.dataset.editingDate;
            dateNote.classList.toggle('is-on', loggedDates.has(dateInput.value) && dateInput.value !== editingDate);
        };
        datePresets.forEach((preset) => preset.addEventListener('click', () => {
            dateInput.value = shiftDate(serverToday, Number(preset.dataset.daysAgo));
            datePresets.forEach((item) => item.classList.toggle('is-active', item === preset));
            updateDateNote();
        }));
        dateInput.addEventListener('input', () => { datePresets.forEach((item) => item.classList.toggle('is-active', shiftDate(serverToday, Number(item.dataset.daysAgo)) === dateInput.value)); updateDateNote(); });

        const syncTags = () => {
            notesCount.textContent = `${notesInput.value.length} / 2000`;
            const parts = notesInput.value.split(/\s*;\s*/);
            document.querySelectorAll('[data-tag]').forEach((tagButton) => {
                const isActive = parts.includes(tagButton.dataset.tag);
                tagButton.classList.toggle('is-active', isActive);
                tagButton.setAttribute('aria-pressed', String(isActive));
            });
        };
        document.querySelectorAll('[data-tag]').forEach((tagButton) => tagButton.addEventListener('click', () => {
            const parts = notesInput.value.split(/\s*;\s*/).filter(Boolean);
            const index = parts.indexOf(tagButton.dataset.tag);
            if (index === -1) { parts.push(tagButton.dataset.tag); } else { parts.splice(index, 1); }
            notesInput.value = parts.join('; ');
            syncTags();
        }));
        notesInput.addEventListener('input', syncTags);

        form.addEventListener('input', updateProgress);
        form.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) { event.preventDefault(); form.requestSubmit(); }
        });
        setHours(hoursInput.value);
        updateDateNote();

        const entryActions = window.createEntryActions({
            form,
            endpoint: '/api/sleep-log',
            token,
            noun: 'sleep entry',
            submitLabel,
            createText: submitText,
            updateText: 'Update this night',
            onStatus: setStatus,
            fill: (entry) => {
                clearErrors();
                setHours(entry.sleep_hours);
                const quality = form.querySelector(`input[name="sleep_quality"][value="${entry.sleep_quality}"]`);
                if (quality) { quality.checked = true; }
                selectedQuality = quality ? quality.value : null;
                setStress(Number(entry.stress_level));
                dateInput.value = entry.logged_on;
                form.dataset.editingDate = entry.logged_on;
                datePresets.forEach((item) => item.classList.toggle('is-active', shiftDate(serverToday, Number(item.dataset.daysAgo)) === entry.logged_on));
                notesInput.value = entry.notes ?? '';
                syncTags();
                updateDateNote();
                updateProgress();
            },
            reset: () => {
                form.reset();
                clearErrors();
                delete form.dataset.editingDate;
                selectedQuality = null;
                setHours(7);
                setStress(3, false);
                dateInput.value = serverToday;
                datePresets.forEach((item) => item.classList.toggle('is-active', item.dataset.daysAgo === '0'));
                syncTags();
                updateDateNote();
                status.className = 'lp-status';
                updateProgress();
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors();

            const data = Object.fromEntries(new FormData(form).entries());
            let hasError = false;
            if (data.sleep_hours === '' || Number(data.sleep_hours) < 0 || Number(data.sleep_hours) > 24) { showError('sleep_hours', 'Enter between 0 and 24 hours.'); hasError = true; }
            if (! data.sleep_quality) { showError('sleep_quality', 'Choose how well you slept.'); hasError = true; }
            if (! data.logged_on) { showError('logged_on', 'Choose the night this entry is for.'); hasError = true; }
            else if (data.logged_on > serverToday) { showError('logged_on', 'The date cannot be in the future.'); hasError = true; }
            if (hasError) { setStatus('error', 'Please complete the highlighted fields.'); return; }

            data.sleep_hours = Number(data.sleep_hours);
            data.sleep_quality = Number(data.sleep_quality);
            data.stress_level = Number(data.stress_level);
            const target = entryActions.request();
            const isUpdate = entryActions.isEditing();
            submit.disabled = true;
            submit.classList.add('is-loading');
            submitLabel.textContent = 'Saving…';
            status.className = 'lp-status';

            try {
                const response = await fetch(target.url, {
                    method: target.method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify(data),
                });
                const payload = await response.json();
                if (! response.ok) {
                    Object.entries(payload.errors ?? {}).forEach(([field, messages]) => showError(field, messages[0]));
                    throw new Error(payload.message || 'Please check the form and try again.');
                }
                submit.classList.remove('is-loading');
                submit.classList.add('is-success');
                submitLabel.textContent = 'Saved';
                setStatus('success', `${payload.message} Your insight was refreshed. Updating your history…`);
                setTimeout(() => window.location.reload(), 900);
            } catch (error) {
                submit.disabled = false;
                submit.classList.remove('is-loading');
                submitLabel.textContent = isUpdate ? 'Update this night' : submitText;
                setStatus('error', error.message);
            }
        });
    })();
</script>
@endsection
