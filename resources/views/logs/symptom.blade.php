@extends('layouts.app')

@section('content')
@php
    $typeMeta = [
        'heartburn' => ['label' => 'Heartburn', 'hint' => 'Burning in the chest or throat', 'tone' => 'red', 'icon' => '<path d="M12 3c1 3 5 5 5 10a5 5 0 0 1-10 0c0-2.2 1.2-3.6 2.4-4.6.1 2 1 3.1 2.1 3.1 0-3.2-.8-5.6.5-8.5Z"/>'],
        'bloating' => ['label' => 'Bloating', 'hint' => 'Fullness, pressure or gas', 'tone' => 'amber', 'icon' => '<circle cx="12" cy="11" r="7"/><path d="M12 18v3M9 21h6"/>'],
        'pain' => ['label' => 'Pain', 'hint' => 'Stomach or chest discomfort', 'tone' => 'violet', 'icon' => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>'],
    ];
    $severity = fn (int $intensity): array => match (true) {
        $intensity >= 9 => ['label' => 'Severe', 'tone' => 'red'],
        $intensity >= 7 => ['label' => 'Strong', 'tone' => 'orange'],
        $intensity >= 5 => ['label' => 'Moderate', 'tone' => 'amber'],
        $intensity >= 3 => ['label' => 'Mild', 'tone' => 'green'],
        default => ['label' => 'Very mild', 'tone' => 'green'],
    };
    $historyGroups = $recentLogs->groupBy(fn ($log) => $log->time->isToday() ? 'Today' : ($log->time->isYesterday() ? 'Yesterday' : $log->time->format('l, M j')));
    $contextTags = ['After a meal', 'Lying down', 'At night', 'After coffee', 'Feeling stressed', 'Large portion'];
@endphp
@include('logs.partials.styles')
@include('logs.partials.entry-actions')
<style>
    .lp-tone-violet { --tone:#6d28d9; --tone-soft:#f5f3ff; --tone-mid:#ddd6fe; }
    .lp-categories.is-three { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .lp-categories.is-three .lp-category { min-height:118px; }

    .st-tag.is-active { border-color:var(--green); color:var(--green-deep); background:#ecfdf5; }
    .st-tag svg { width:12px; height:12px; fill:none; stroke:currentColor; stroke-width:2.6; stroke-linecap:round; }


    .st-type-row { display:grid; grid-template-columns:36px minmax(0,1fr) auto; align-items:center; gap:12px; }
    .st-type-row + .st-type-row { margin-top:12px; }
    .st-type-row .lp-badge-icon { width:36px; height:36px; border-radius:11px; }
    .st-type-row strong { display:block; font-size:13px; }
    .st-type-bar { height:7px; margin-top:6px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .st-type-bar i { display:block; height:100%; border-radius:inherit; background:var(--tone); transform-origin:left; animation:lpGrowX .9s cubic-bezier(.2,.8,.2,1) both; }
    .st-type-row > span:last-child { color:#334155; font-size:13px; font-weight:700; }

    .st-entry { display:grid; grid-template-columns:40px minmax(0,1fr) auto; gap:12px; padding:12px 10px; border:1px solid transparent; border-radius:14px; transition:background .2s ease,border-color .2s ease; }
    .st-entry + .st-entry { margin-top:4px; }
    .st-entry:hover { border-color:#eef2f1; background:#fafcfb; }
    .st-entry-title { display:flex; align-items:center; flex-wrap:wrap; gap:8px; }
    .st-entry-title strong { color:var(--ink); font-size:14px; }
    .st-pill { padding:2px 8px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:11px; font-weight:700; }
    .st-entry p { margin:4px 0 0; color:var(--muted); font-size:12.5px; line-height:1.45; overflow-wrap:anywhere; }
    .st-meals { display:flex; align-items:center; flex-wrap:wrap; gap:5px; margin-top:7px; color:#64748b; font-size:11.5px; }
    .st-meals svg { width:13px; height:13px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .st-meals span { padding:1px 7px; border-radius:999px; color:#9a3412; background:#fff7ed; box-shadow:inset 0 0 0 1px #fed7aa; font-weight:600; }
    .st-entry time { color:#94a3b8; font-size:12px; font-weight:600; white-space:nowrap; }

    @media (max-width:700px) { .lp-categories.is-three { grid-template-columns:minmax(0,1fr); } .lp-categories.is-three .lp-category { min-height:0; flex-direction:row; align-items:center; } .lp-categories.is-three .lp-category-tag { display:none; } .lp-scale { grid-template-columns:repeat(5,1fr); } }
</style>

<div class="shell">
    @include('partials.topbar')

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'symptom'])

        <main class="main lp">
            <section class="lp-hero">
                <div>
                    <span class="lp-crumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Symptom tracker</span>
                    <h1>Symptom Tracker</h1>
                    <p>Record how you feel so RefluxCare can connect your symptoms with recent meals, sleep and stress.</p>
                </div>
                <div class="lp-hero-stats">
                    <div class="lp-hero-stat"><span>Today</span><strong>{{ $todayCount }}</strong><small>symptom{{ $todayCount === 1 ? '' : 's' }} logged</small></div>
                    <div class="lp-hero-stat"><span>Last 7 days</span><strong>{{ $weekCount }}</strong><small>symptom{{ $weekCount === 1 ? '' : 's' }} logged</small></div>
                    <div class="lp-hero-stat"><span>Avg. intensity</span><strong>{{ $weekAverageIntensity !== null ? number_format($weekAverageIntensity, 1) : '—' }}</strong><small>{{ $weekAverageIntensity !== null ? 'out of 10 this week' : 'no symptoms yet' }}</small></div>
                </div>
            </section>

            <div class="lp-grid">
                <section class="lp-panel">
                    <div class="lp-panel-head">
                        <div><h2>How are you feeling?</h2><p>Three quick steps. Only the fields marked <span style="color:#dc2626">*</span> are required.</p></div>
                    </div>

                    <div class="lp-progress" aria-hidden="true">
                        <div class="lp-progress-step" data-step="symptom"><b><span>1</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Symptom</div>
                        <div class="lp-progress-step" data-step="intensity"><b><span>2</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Intensity</div>
                        <div class="lp-progress-step" data-step="time"><b><span>3</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Time</div>
                    </div>

                    <form id="log-form" novalidate>
                        <div class="lp-field">
                            <div class="lp-label" id="symptom-label"><span>What are you feeling? <em>*</em></span><small>Tap again to clear</small></div>
                            <fieldset class="lp-categories is-three" aria-labelledby="symptom-label" aria-describedby="symptom_type-error" id="symptom_type-group">
                                @foreach ($typeMeta as $value => $meta)
                                    <label class="lp-category lp-tone-{{ $meta['tone'] }}">
                                        <input type="radio" name="symptom_type" value="{{ $value }}" required>
                                        <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $meta['icon'] !!}</svg></span>
                                        <span><strong>{{ $meta['label'] }}</strong><small>{{ $meta['hint'] }}</small></span>
                                        <span class="lp-category-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <p class="lp-error" id="symptom_type-error" data-error-for="symptom_type"></p>
                        </div>

                        <div class="lp-field">
                            <label class="lp-label" for="intensity"><span>How strong is it? <em>*</em></span><small>1 = barely noticeable · 10 = worst</small></label>
                            <div class="lp-meter lp-tone-amber" id="intensity-box">
                                <div class="lp-meter-top">
                                    <div class="lp-meter-value"><strong id="intensity-value">5</strong><span>/ 10</span></div>
                                    <span class="lp-meter-label" id="intensity-label">Moderate</span>
                                </div>
                                <input class="lp-range" id="intensity" type="range" name="intensity" min="1" max="10" step="1" value="5" aria-describedby="intensity-error" aria-valuetext="5 out of 10, Moderate">
                                <div class="lp-scale" role="group" aria-label="Pick an intensity">
                                    @for ($level = 1; $level <= 10; $level++)
                                        <button type="button" data-level="{{ $level }}" class="{{ $level === 5 ? 'is-active' : '' }}" aria-label="Intensity {{ $level }}">{{ $level }}</button>
                                    @endfor
                                </div>
                                <div class="lp-scale-ends"><span>Very mild</span><span>Severe</span></div>
                                <div class="lp-care" id="intensity-care" role="note">
                                    <svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                    <span>If you have chest pain, trouble swallowing, vomiting, or pain that spreads to your arm or jaw, contact a healthcare provider or emergency services right away.</span>
                                </div>
                            </div>
                            <p class="lp-error" id="intensity-error" data-error-for="intensity"></p>
                        </div>

                        <div class="lp-field">
                            <label class="lp-label" for="time"><span>When did it happen? <em>*</em></span></label>
                            <div class="lp-time">
                                <div class="lp-input-wrap">
                                    <svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    <input class="lp-input" id="time" type="datetime-local" name="time" value="{{ now()->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}" required aria-describedby="time-hint time-error">
                                </div>
                                <div class="lp-presets" role="group" aria-label="Quick time presets">
                                    <button class="lp-chip is-active" type="button" data-minutes-ago="0">Now</button>
                                    <button class="lp-chip" type="button" data-minutes-ago="30">30m ago</button>
                                    <button class="lp-chip" type="button" data-minutes-ago="60">1h ago</button>
                                    <button class="lp-chip" type="button" data-minutes-ago="120">2h ago</button>
                                </div>
                            </div>
                            <small class="lp-hint" id="time-hint">Logging the time helps compare symptoms with meals and sleep.</small>
                            <p class="lp-error" id="time-error" data-error-for="time"></p>
                        </div>

                        <div class="lp-field">
                            <label class="lp-label" for="notes"><span>What was happening? <small>(optional)</small></span><small id="notes-count">0 / 2000</small></label>
                            <div class="lp-chips" style="margin:0 0 10px">
                                @foreach ($contextTags as $tag)
                                    <button class="lp-chip st-tag" type="button" data-tag="{{ $tag }}" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>{{ $tag }}</button>
                                @endforeach
                            </div>
                            <textarea class="lp-textarea" id="notes" name="notes" maxlength="2000" placeholder="Describe the episode, e.g. burning after dinner that eased after an hour."></textarea>
                            <p class="lp-error" data-error-for="notes"></p>
                        </div>

                        <div class="lp-actions">
                            <button class="lp-submit" id="log-submit" type="submit">
                                <span id="log-submit-label">Save symptom entry</span>
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
                        <div class="lp-panel-head"><div><h2>Severity this week</h2><p>Highest intensity per day · last 7 days</p></div></div>
                        <div class="lp-chart" role="img" aria-label="Highest symptom intensity for each of the last 7 days">
                            @foreach ($weeklySeverity as $index => $day)
                                <div class="lp-col {{ $day['isToday'] ? 'is-today' : '' }}">
                                    @if ($day['count'])<b>{{ $day['maxIntensity'] }}</b>@endif
                                    <i class="{{ $day['count'] ? 'lp-tone-'.$severity($day['maxIntensity'])['tone'] : 'is-zero' }}" style="height:{{ $day['count'] ? $day['maxIntensity'] * 10 : 3 }}%;animation-delay:{{ $index * 0.05 }}s"></i>
                                    <span class="lp-chart-tip"><strong style="display:block">{{ $day['label'] }}, {{ $day['date'] }}</strong>{{ $day['count'] ? $day['count'].' symptom'.($day['count'] === 1 ? '' : 's').' · peak '.$day['maxIntensity'].'/10' : 'No symptoms' }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="lp-days">@foreach ($weeklySeverity as $day)<span class="{{ $day['isToday'] ? 'is-today' : '' }}">{{ $day['isToday'] ? 'Today' : $day['label'] }}</span>@endforeach</div>
                        <div class="lp-chart-legend">
                            <span class="lp-tone-green"><i></i> Mild</span><span class="lp-tone-amber"><i></i> Moderate</span><span class="lp-tone-orange"><i></i> Strong</span><span class="lp-tone-red"><i></i> Severe</span>
                        </div>
                    </section>

                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Symptom types</h2><p>Last 7 days</p></div></div>
                        @foreach ($typeBreakdown as $type => $count)
                            <div class="st-type-row lp-tone-{{ $typeMeta[$type]['tone'] }}">
                                <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $typeMeta[$type]['icon'] !!}</svg></span>
                                <div><strong>{{ $typeMeta[$type]['label'] }}</strong><div class="st-type-bar"><i style="width:{{ $weekCount ? $count / $weekCount * 100 : 0 }}%"></i></div></div>
                                <span>{{ $count }}</span>
                            </div>
                        @endforeach
                    </section>

                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Your symptom history</h2><p>With meals eaten in the 6 hours before</p></div></div>
                        @if ($recentLogs->isEmpty())
                            <div class="lp-empty">
                                <svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg>
                                <strong>No symptoms logged</strong>
                                Your saved symptom entries will appear here.
                            </div>
                        @else
                            <div class="lp-history">
                                @foreach ($historyGroups as $day => $logs)
                                    <div class="lp-day">
                                        <h3>{{ $day }} <span>{{ $logs->count() }} entr{{ $logs->count() === 1 ? 'y' : 'ies' }}</span></h3>
                                        @foreach ($logs as $log)
                                            @php($meta = $typeMeta[$log->symptom_type] ?? $typeMeta['pain'])
                                            @php($level = $severity($log->intensity))
                                            <div class="st-entry lp-entry lp-tone-{{ $meta['tone'] }}" data-entry="{{ json_encode(['id' => $log->id, 'label' => $meta['label'].' · '.$log->time->format('M j, g:i A'), 'symptom_type' => $log->symptom_type, 'intensity' => $log->intensity, 'time' => $log->time->toIso8601String(), 'notes' => $log->notes]) }}">
                                                <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $meta['icon'] !!}</svg></span>
                                                <div>
                                                    <div class="st-entry-title">
                                                        <strong>{{ $meta['label'] }}</strong>
                                                        <span class="st-pill lp-tone-{{ $level['tone'] }}">{{ $log->intensity }}/10 · {{ $level['label'] }}</span>
                                                        @if ($log->is($recentLogs->first()))<span class="lp-latest">Latest</span>@endif
                                                    </div>
                                                    @if ($log->notes)<p>{{ $log->notes }}</p>@endif
                                                    @if (! empty($mealsBeforeSymptom[$log->id]))
                                                        <div class="st-meals"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg> Ate before:
                                                            @foreach ($mealsBeforeSymptom[$log->id] as $meal)<span>{{ $meal }}</span>@endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="lp-entry-side">
                                                    <time datetime="{{ $log->time->toIso8601String() }}" title="{{ $log->time->format('M d, Y g:i A') }}">{{ $log->time->format('g:i A') }}</time>
                                                    <div class="lp-entry-actions">
                                                        <button class="lp-icon-btn" type="button" data-edit aria-label="Edit {{ $meta['label'] }} at {{ $log->time->format('g:i A') }}" title="Edit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                                                        <button class="lp-icon-btn is-danger" type="button" data-delete aria-label="Delete {{ $meta['label'] }} at {{ $log->time->format('g:i A') }}" title="Delete"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg></button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <div class="lp-tip">
                        <span class="lp-badge-icon lp-tone-green"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.4 1 1.1 1 1.8V16h5v-.3c0-.7.4-1.4 1-1.8A6 6 0 0 0 12 3Z"/></svg></span>
                        <div><strong>Log while it's fresh</strong><p>Recording symptoms close to when they happen makes it easier to match them with meals you ate up to 6 hours earlier.</p></div>
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
        const timeInput = document.getElementById('time');
        const notesInput = document.getElementById('notes');
        const notesCount = document.getElementById('notes-count');
        const range = document.getElementById('intensity');
        const intensityBox = document.getElementById('intensity-box');
        const intensityValue = document.getElementById('intensity-value');
        const intensityLabel = document.getElementById('intensity-label');
        const intensityCare = document.getElementById('intensity-care');
        const scaleButtons = document.querySelectorAll('[data-level]');
        const presets = document.querySelectorAll('[data-minutes-ago]');
        const token = @json(session('api_token'));
        const submitText = 'Save symptom entry';
        let intensityTouched = false;
        const icons = {
            success: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
            error: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>',
        };
        const severity = (level) => level >= 9 ? ['Severe', 'red'] : level >= 7 ? ['Strong', 'orange'] : level >= 5 ? ['Moderate', 'amber'] : level >= 3 ? ['Mild', 'green'] : ['Very mild', 'green'];

        const toLocalInputValue = (date) => {
            const pad = (value) => String(value).padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
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
            const control = field === 'symptom_type' ? document.getElementById('symptom_type-group') : form.elements[field];
            control?.setAttribute?.('aria-invalid', 'true');
        };

        const updateProgress = () => {
            const done = {
                symptom: !! form.querySelector('input[name="symptom_type"]:checked'),
                intensity: intensityTouched,
                time: timeInput.value !== '',
            };
            Object.entries(done).forEach(([step, isDone]) => document.querySelector(`[data-step="${step}"]`).classList.toggle('is-done', isDone));
        };

        const setIntensity = (level) => {
            const [label, tone] = severity(level);
            range.value = level;
            intensityValue.textContent = level;
            intensityLabel.textContent = label;
            intensityBox.className = `lp-meter lp-tone-${tone}`;
            range.setAttribute('aria-valuetext', `${level} out of 10, ${label}`);
            scaleButtons.forEach((button) => button.classList.toggle('is-active', Number(button.dataset.level) === level));
            intensityCare.classList.toggle('is-on', level >= 8);
            intensityTouched = true;
            updateProgress();
        };

        range.addEventListener('input', () => setIntensity(Number(range.value)));
        scaleButtons.forEach((button) => button.addEventListener('click', () => setIntensity(Number(button.dataset.level))));

        let selectedType = null;
        form.querySelectorAll('input[name="symptom_type"]').forEach((radio) => radio.addEventListener('click', () => {
            if (radio.value === selectedType) {
                radio.checked = false;
                selectedType = null;
            } else {
                selectedType = radio.value;
            }
            updateProgress();
        }));

        const now = new Date();
        timeInput.value = toLocalInputValue(now);
        timeInput.max = toLocalInputValue(now);
        presets.forEach((preset) => preset.addEventListener('click', () => {
            timeInput.max = toLocalInputValue(new Date());
            timeInput.value = toLocalInputValue(new Date(Date.now() - Number(preset.dataset.minutesAgo) * 60000));
            presets.forEach((item) => item.classList.toggle('is-active', item === preset));
            updateProgress();
        }));
        timeInput.addEventListener('input', () => presets.forEach((item) => item.classList.remove('is-active')));

        const updateNotesCount = () => { notesCount.textContent = `${notesInput.value.length} / 2000`; };
        document.querySelectorAll('[data-tag]').forEach((tagButton) => tagButton.addEventListener('click', () => {
            const tag = tagButton.dataset.tag;
            const parts = notesInput.value.split(/\s*;\s*/).filter(Boolean);
            const index = parts.indexOf(tag);
            if (index === -1) { parts.push(tag); } else { parts.splice(index, 1); }
            notesInput.value = parts.join('; ');
            tagButton.classList.toggle('is-active', index === -1);
            tagButton.setAttribute('aria-pressed', String(index === -1));
            updateNotesCount();
        }));
        notesInput.addEventListener('input', () => {
            updateNotesCount();
            const parts = notesInput.value.split(/\s*;\s*/);
            document.querySelectorAll('[data-tag]').forEach((tagButton) => {
                const isActive = parts.includes(tagButton.dataset.tag);
                tagButton.classList.toggle('is-active', isActive);
                tagButton.setAttribute('aria-pressed', String(isActive));
            });
        });

        form.addEventListener('input', updateProgress);
        form.addEventListener('change', updateProgress);
        form.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) { event.preventDefault(); form.requestSubmit(); }
        });
        updateProgress();

        const entryActions = window.createEntryActions({
            form,
            endpoint: '/api/symptom-log',
            token,
            noun: 'symptom entry',
            submitLabel,
            createText: submitText,
            updateText: 'Update this entry',
            onStatus: setStatus,
            fill: (entry) => {
                clearErrors();
                const type = form.querySelector(`input[name="symptom_type"][value="${entry.symptom_type}"]`);
                if (type) { type.checked = true; }
                selectedType = type ? type.value : null;
                setIntensity(Number(entry.intensity));
                timeInput.value = toLocalInputValue(new Date(entry.time));
                presets.forEach((item) => item.classList.remove('is-active'));
                notesInput.value = entry.notes ?? '';
                notesInput.dispatchEvent(new Event('input', { bubbles: true }));
            },
            reset: () => {
                form.reset();
                clearErrors();
                selectedType = null;
                setIntensity(5);
                intensityTouched = false;
                timeInput.value = toLocalInputValue(new Date());
                presets.forEach((item) => item.classList.toggle('is-active', item.dataset.minutesAgo === '0'));
                notesInput.dispatchEvent(new Event('input', { bubbles: true }));
                status.className = 'lp-status';
                updateProgress();
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors();

            const data = Object.fromEntries(new FormData(form).entries());
            let hasError = false;
            if (! data.symptom_type) { showError('symptom_type', 'Choose what you are feeling.'); hasError = true; }
            if (! data.time) { showError('time', 'Add the time it happened.'); hasError = true; }
            else if (new Date(data.time) > new Date()) { showError('time', 'The symptom time cannot be in the future.'); hasError = true; }
            if (hasError) {
                setStatus('error', 'Please complete the highlighted fields.');
                return;
            }

            data.intensity = Number(data.intensity);
            data.time = new Date(data.time).toISOString();
            submit.disabled = true;
            submit.classList.add('is-loading');
            submitLabel.textContent = 'Saving…';
            status.className = 'lp-status';

            const target = entryActions.request();
            const isUpdate = entryActions.isEditing();
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
                setStatus('success', isUpdate
                    ? 'Your symptom entry was updated and your insight was refreshed. Updating your history…'
                    : 'Your symptom was added to your timeline and your insight was refreshed. Updating your history…');
                setTimeout(() => window.location.reload(), 900);
            } catch (error) {
                submit.disabled = false;
                submit.classList.remove('is-loading');
                submitLabel.textContent = isUpdate ? 'Update this entry' : submitText;
                setStatus('error', error.message);
            }
        });
    })();
</script>
@endsection
