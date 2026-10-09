@extends('layouts.app')

@section('content')
@php
    $categoryMeta = [
        'acidic' => ['label' => 'Acidic', 'hint' => 'Citrus, tomato, vinegar', 'tone' => 'orange', 'tag' => 'Common trigger', 'icon' => '<circle cx="12" cy="12" r="8"/><path d="M12 4v16M4 12h16M6.3 6.3l11.4 11.4M17.7 6.3 6.3 17.7"/>'],
        'oily' => ['label' => 'Oily or fried', 'hint' => 'Fried food, fatty meat', 'tone' => 'amber', 'tag' => 'Common trigger', 'icon' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11Z"/>'],
        'spicy' => ['label' => 'Spicy', 'hint' => 'Chili, hot sauce, pepper', 'tone' => 'red', 'tag' => 'Common trigger', 'icon' => '<path d="M12 3c1 3 5 5 5 10a5 5 0 0 1-10 0c0-2.2 1.2-3.6 2.4-4.6.1 2 1 3.1 2.1 3.1 0-3.2-.8-5.6.5-8.5Z"/>'],
        'dairy' => ['label' => 'Dairy', 'hint' => 'Milk, cheese, cream', 'tone' => 'sky', 'tag' => 'May trigger', 'icon' => '<path d="M8 3h8v3l2 3v12H6V9l2-3V3Z"/><path d="M6 9h12M10 14h4"/>'],
        'other' => ['label' => 'Other', 'hint' => 'None of these fit', 'tone' => 'green', 'tag' => 'Usually gentle', 'icon' => '<path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/>'],
    ];
    $quickFoods = $recentLogs->unique(fn ($log) => mb_strtolower(trim($log->food_name)))->take(6);
    $historyGroups = $recentLogs->groupBy(fn ($log) => $log->time->isToday() ? 'Today' : ($log->time->isYesterday() ? 'Yesterday' : $log->time->format('l, M j')));
    $topCategory = $weekCount ? array_search(max($categoryBreakdown), $categoryBreakdown, true) : null;
@endphp
@include('logs.partials.styles')

<div class="shell">
    @include('partials.topbar')

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'food'])

        <main class="main lp">
            <section class="lp-hero">
                <div>
                    <span class="lp-crumb"><a href="{{ route('dashboard') }}">Dashboard</a> / Food intake</span>
                    <h1>Food Intake Logger</h1>
                    <p>Record what you ate so RefluxCare can compare meals with your symptoms and reveal your personal triggers.</p>
                </div>
                <div class="lp-hero-stats">
                    <div class="lp-hero-stat"><span>Today</span><strong>{{ $todayCount }}</strong><small>meal{{ $todayCount === 1 ? '' : 's' }} logged</small></div>
                    <div class="lp-hero-stat"><span>Last 7 days</span><strong>{{ $weekCount }}</strong><small>meal{{ $weekCount === 1 ? '' : 's' }} logged</small></div>
                    <div class="lp-hero-stat"><span>Most logged</span><strong class="is-text">{{ $topCategory ? $categoryMeta[$topCategory]['label'] : '—' }}</strong><small>{{ $topCategory ? 'this week' : 'no meals yet' }}</small></div>
                </div>
            </section>

            <div class="lp-grid">
                <section class="lp-panel">
                    <div class="lp-panel-head">
                        <div><h2>What did you eat?</h2><p>Three quick steps. Only the fields marked <span style="color:#dc2626">*</span> are required.</p></div>
                    </div>

                    <div class="lp-progress" aria-hidden="true">
                        <div class="lp-progress-step" data-step="food"><b><span>1</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Food</div>
                        <div class="lp-progress-step" data-step="category"><b><span>2</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Category</div>
                        <div class="lp-progress-step" data-step="time"><b><span>3</span><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></b> Time</div>
                    </div>

                    <form id="log-form" novalidate>
                        <div class="lp-field">
                            <label class="lp-label" for="food_name"><span>What did you eat or drink? <em>*</em></span></label>
                            <div class="lp-input-wrap">
                                <svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg>
                                <input class="lp-input" id="food_name" name="food_name" maxlength="120" autocomplete="off" required placeholder="For example: oatmeal with banana" aria-describedby="food_name-hint food_name-error">
                            </div>
                            <small class="lp-hint" id="food_name-hint">One food, a drink, or the whole meal. Keep it simple.</small>
                            <p class="lp-error" id="food_name-error" data-error-for="food_name"></p>
                            @if ($quickFoods->isNotEmpty())
                                <div class="lp-chips">
                                    <span>Recent:</span>
                                    @foreach ($quickFoods as $quickFood)
                                        <button class="lp-chip lp-tone-{{ $categoryMeta[$quickFood->category]['tone'] ?? 'green' }}" type="button" data-food="{{ $quickFood->food_name }}" data-category="{{ $quickFood->category }}"><i></i>{{ $quickFood->food_name }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="lp-field">
                            <div class="lp-label" id="category-label"><span>Which category fits best? <em>*</em></span><small>Pick the closest match</small></div>
                            <fieldset class="lp-categories" aria-labelledby="category-label" aria-describedby="category-error" id="category-group">
                                @foreach ($categoryMeta as $value => $meta)
                                    <label class="lp-category lp-tone-{{ $meta['tone'] }}">
                                        <input type="radio" name="category" value="{{ $value }}" required>
                                        <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $meta['icon'] !!}</svg></span>
                                        <span><strong>{{ $meta['label'] }}</strong><small>{{ $meta['hint'] }}</small></span>
                                        <span class="lp-category-tag">{{ $meta['tag'] }}</span>
                                        <span class="lp-category-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <p class="lp-error" id="category-error" data-error-for="category"></p>
                        </div>

                        <div class="lp-field">
                            <label class="lp-label" for="time"><span>When did you eat it? <em>*</em></span></label>
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
                            <small class="lp-hint" id="time-hint">The meal time helps compare food with later symptoms.</small>
                            <p class="lp-error" id="time-error" data-error-for="time"></p>
                        </div>

                        <div class="lp-field">
                            <label class="lp-label" for="notes"><span>Anything else to remember? <small>(optional)</small></span><small id="notes-count">0 / 2000</small></label>
                            <textarea class="lp-textarea" id="notes" name="notes" maxlength="2000" placeholder="Example: large portion, drank coffee, ate late, or felt stressed."></textarea>
                            <p class="lp-error" data-error-for="notes"></p>
                        </div>

                        <div class="lp-actions">
                            <button class="lp-submit" id="log-submit" type="submit">
                                <span id="log-submit-label">Save meal to my timeline</span>
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
                        <div class="lp-panel-head"><div><h2>This week's mix</h2><p>Meal categories · last 7 days</p></div></div>
                        @if ($weekCount)
                            <div class="lp-stack" role="img" aria-label="Meal categories logged in the last 7 days">
                                @foreach ($categoryBreakdown as $category => $count)
                                    @if ($count)<i class="lp-tone-{{ $categoryMeta[$category]['tone'] }}" style="width:{{ $count / $weekCount * 100 }}%"></i>@endif
                                @endforeach
                            </div>
                        @endif
                        <div class="lp-legend">
                            @foreach ($categoryBreakdown as $category => $count)
                                <div class="lp-legend-row lp-tone-{{ $categoryMeta[$category]['tone'] }} {{ $count ? '' : 'is-zero' }}"><i></i><span>{{ $categoryMeta[$category]['label'] }}</span><strong>{{ $count }}</strong></div>
                            @endforeach
                        </div>
                    </section>

                    <section class="lp-panel">
                        <div class="lp-panel-head"><div><h2>Your meal history</h2><p>Your latest saved meals</p></div></div>
                        @if ($recentLogs->isEmpty())
                            <div class="lp-empty">
                                <svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg>
                                <strong>No meals yet</strong>
                                Your saved meals will appear here.
                            </div>
                        @else
                            <div class="lp-history">
                                @foreach ($historyGroups as $day => $logs)
                                    <div class="lp-day">
                                        <h3>{{ $day }} <span>{{ $logs->count() }} meal{{ $logs->count() === 1 ? '' : 's' }}</span></h3>
                                        @foreach ($logs as $log)
                                            @php($meta = $categoryMeta[$log->category] ?? $categoryMeta['other'])
                                            <div class="lp-meal lp-tone-{{ $meta['tone'] }}">
                                                <span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $meta['icon'] !!}</svg></span>
                                                <div>
                                                    <strong>{{ $log->food_name }}@if ($log->is($recentLogs->first()))<span class="lp-latest" style="display:inline">Latest</span>@endif</strong>
                                                    <span>{{ $meta['label'] }}{{ $log->notes ? ' · '.$log->notes : '' }}</span>
                                                </div>
                                                <time datetime="{{ $log->time->toIso8601String() }}" title="{{ $log->time->format('M d, Y g:i A') }}">{{ $log->time->format('g:i A') }}</time>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <div class="lp-tip">
                        <span class="lp-badge-icon lp-tone-green"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.4 1 1.1 1 1.8V16h5v-.3c0-.7.4-1.4 1-1.8A6 6 0 0 0 12 3Z"/></svg></span>
                        <div><strong>Why log meals?</strong><p>Over time, RefluxCare compares your meals and symptoms to identify possible personal triggers. Logging soon after eating keeps times accurate.</p></div>
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
        const foodInput = document.getElementById('food_name');
        const timeInput = document.getElementById('time');
        const notesInput = document.getElementById('notes');
        const notesCount = document.getElementById('notes-count');
        const presets = document.querySelectorAll('[data-minutes-ago]');
        const token = @json(session('api_token'));
        const icons = {
            success: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
            error: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>',
        };

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
            const control = field === 'category' ? document.getElementById('category-group') : form.elements[field];
            control?.setAttribute?.('aria-invalid', 'true');
        };

        const updateProgress = () => {
            const done = {
                food: foodInput.value.trim() !== '',
                category: !! form.querySelector('input[name="category"]:checked'),
                time: timeInput.value !== '',
            };
            Object.entries(done).forEach(([step, isDone]) => document.querySelector(`[data-step="${step}"]`).classList.toggle('is-done', isDone));
        };

        const now = new Date();
        timeInput.value = toLocalInputValue(now);
        timeInput.max = toLocalInputValue(now);

        presets.forEach((preset) => preset.addEventListener('click', () => {
            const date = new Date(Date.now() - Number(preset.dataset.minutesAgo) * 60000);
            timeInput.max = toLocalInputValue(new Date());
            timeInput.value = toLocalInputValue(date);
            presets.forEach((item) => item.classList.toggle('is-active', item === preset));
            updateProgress();
        }));
        timeInput.addEventListener('input', () => presets.forEach((item) => item.classList.remove('is-active')));

        let selectedCategory = form.querySelector('input[name="category"]:checked')?.value ?? null;
        form.querySelectorAll('input[name="category"]').forEach((radio) => radio.addEventListener('click', () => {
            if (radio.value === selectedCategory) {
                radio.checked = false;
                selectedCategory = null;
            } else {
                selectedCategory = radio.value;
            }
            updateProgress();
        }));

        document.querySelectorAll('[data-food]').forEach((chip) => chip.addEventListener('click', () => {
            foodInput.value = chip.dataset.food;
            const category = form.querySelector(`input[name="category"][value="${chip.dataset.category}"]`);
            if (category) { category.checked = true; selectedCategory = category.value; }
            updateProgress();
            foodInput.focus();
        }));

        notesInput.addEventListener('input', () => { notesCount.textContent = `${notesInput.value.length} / 2000`; });
        form.addEventListener('input', updateProgress);
        form.addEventListener('change', updateProgress);
        form.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) { event.preventDefault(); form.requestSubmit(); }
        });
        updateProgress();

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors();

            const data = Object.fromEntries(new FormData(form).entries());
            let hasError = false;
            if (! data.food_name?.trim()) { showError('food_name', 'Tell us what you ate or drank.'); hasError = true; }
            if (! data.category) { showError('category', 'Choose the category that fits best.'); hasError = true; }
            if (! data.time) { showError('time', 'Add the time you ate.'); hasError = true; }
            else if (new Date(data.time) > new Date()) { showError('time', 'The meal time cannot be in the future.'); hasError = true; }
            if (hasError) {
                setStatus('error', 'Please complete the highlighted fields.');
                form.querySelector('[aria-invalid="true"] input, input[aria-invalid="true"], [aria-invalid="true"]')?.focus?.();
                return;
            }

            data.time = new Date(data.time).toISOString();
            submit.disabled = true;
            submit.classList.add('is-loading');
            submitLabel.textContent = 'Saving…';
            status.className = 'lp-status';

            try {
                const response = await fetch('/api/food-log', {
                    method: 'POST',
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
                setStatus('success', `“${payload.data?.food_name ?? data.food_name}” was added to your timeline. Updating your history…`);
                setTimeout(() => window.location.reload(), 900);
            } catch (error) {
                submit.disabled = false;
                submit.classList.remove('is-loading');
                submitLabel.textContent = 'Save meal to my timeline';
                setStatus('error', error.message);
            }
        });
    })();
</script>
@endsection
