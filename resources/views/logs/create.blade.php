@extends('layouts.app')

@section('content')
@php
    $isFood = $type === 'food';
    $isSymptom = $type === 'symptom';
    $endpoint = $isFood ? 'food-log' : ($isSymptom ? 'symptom-log' : 'sleep-log');
    $description = $isFood
        ? 'Record what you ate so RefluxCare can compare meals with your symptoms.'
        : ($isSymptom
            ? 'Record how you feel so RefluxCare can identify patterns and possible triggers.'
            : 'Record your sleep and stress so your daily health pattern is more complete.');
@endphp

<style>
    .log-main { padding:0; background:radial-gradient(circle at 12% 8%,#dff9ed 0,transparent 34%),radial-gradient(circle at 88% 82%,#e8fbf3 0,transparent 30%),linear-gradient(135deg,#e9faf3,#f8fffc 50%,#fff); }
    .log-screen { min-height:calc(100vh - 74px); padding:44px clamp(30px,5vw,88px) 72px; }
    .log-layout { width:100%; max-width:1500px; display:grid; grid-template-columns:minmax(340px,.85fr) minmax(600px,1.15fr); gap:clamp(42px,6vw,96px); align-items:start; margin:0 auto; }
    .log-intro { padding:28px 0; }
    .log-eyebrow { display:inline-flex; align-items:center; gap:8px; color:#178264; font-size:11px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    .log-eyebrow span { width:8px; height:8px; border-radius:50%; background:#35cc8d; box-shadow:0 0 0 5px #35cc8d20; }
    .log-intro h1 { max-width:480px; margin:18px 0 12px; font-size:clamp(34px,4vw,54px); line-height:1.02; letter-spacing:-.06em; }
    .log-intro > p { max-width:490px; color:#637880; font-size:15px; line-height:1.65; }
    .log-steps { display:grid; gap:12px; margin-top:30px; }
    .log-step { display:flex; align-items:flex-start; gap:14px; max-width:520px; padding:17px 18px; border:1px solid #d8eee5; border-radius:20px; background:#fff; box-shadow:0 18px 38px #174b3814,0 3px 8px #174b3808; }
    .log-step b { display:grid; place-items:center; flex:none; width:25px; height:25px; border-radius:50%; color:#fff; background:var(--green); font-size:11px; }
    .log-step strong { display:block; margin-bottom:2px; color:#173e47; font-size:12px; }
    .log-step small { color:#70868b; font-size:11px; }
    .log-card { padding:clamp(30px,3vw,44px); border:1px solid #d4ebe1; border-radius:32px; background:#fff; box-shadow:0 30px 80px #174b3820,0 5px 16px #174b380d; }
    .log-card-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:24px; }
    .log-card h2 { margin:0 0 5px; font-size:24px; letter-spacing:-.04em; }
    .log-card-header p { margin:0; color:#70868b; font-size:12px; }
    .log-icon { display:grid; place-items:center; flex:none; width:50px; height:50px; border-radius:17px; color:var(--green); background:#ddf8ee; font-size:22px; box-shadow:inset 0 0 0 1px #c7efdf; }
    .log-field { margin-bottom:18px; }
    .log-field label { display:block; margin-bottom:7px; color:#263f48; font-size:12px; font-weight:750; }
    .log-field input, .log-field select, .log-field textarea { width:100%; padding:13px 15px; border:1px solid #d9e7e5; border-radius:15px; color:#173e47; background:#fbfefd; outline:none; font-size:13px; }
    .log-field textarea { min-height:88px; resize:vertical; }
    .log-field input:focus, .log-field select:focus, .log-field textarea:focus { border-color:#54ae91; box-shadow:0 0 0 3px #dff6ec; }
    .log-field hint, .log-hint { display:block; margin-top:5px; color:#829598; font-size:10px; }
    .log-two { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .log-submit { width:100%; min-height:50px; border:0; border-radius:16px; color:#fff; background:linear-gradient(100deg,#17684f,#0b9b68); box-shadow:0 13px 25px #17684f32; cursor:pointer; font-weight:800; transition:transform .2s ease,box-shadow .2s ease; }
    .log-submit:hover { transform:translateY(-2px); box-shadow:0 17px 30px #17684f3d; }
    .log-submit:disabled { cursor:wait; opacity:.7; }
    .log-status { min-height:20px; margin-top:13px; color:#5f7779; font-size:12px; }
    .log-status.success { color:#08734d; }
    .log-status.error { color:#b83232; }
    .recent-logs { margin-top:18px; padding-top:20px; border-top:1px solid #e4efec; }
    .recent-logs h3 { margin:0 0 11px; color:#173e47; font-size:15px; letter-spacing:-.02em; }
    .recent-log-list { display:grid; gap:8px; }
    .recent-log { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 15px; border:1px solid #e0efea; border-radius:17px; background:#fbfefd; box-shadow:0 8px 18px #174b3808; }
    .recent-log strong { display:block; color:#173e47; font-size:12px; }
    .recent-log small { display:block; margin-top:2px; color:#788b8d; font-size:10px; }
    .recent-log-badge { flex:none; padding:4px 7px; border-radius:999px; color:var(--green); background:#e1f8ee; font-size:9px; font-weight:800; }
    @media (max-width:1100px) { .log-layout { grid-template-columns:minmax(300px,.8fr) minmax(500px,1.2fr); gap:34px; } }
    @media (max-width:800px) { .log-screen { padding:24px 18px 40px; } .log-layout { grid-template-columns:1fr; gap:5px; } .log-intro { padding:10px 0 20px; } .log-intro h1 { font-size:42px; } .log-card { padding:22px; border-radius:24px; } }
    @media (max-width:480px) { .log-two { grid-template-columns:1fr; gap:0; } }
</style>

<div class="shell">
    <header class="topbar">
        <a class="brand" href="{{ route('dashboard') }}"><img class="brand-logo" src="{{ asset('images/logo1.png') }}" alt="RefluxCare logo"><span>RefluxCare<small>CLINICAL PLATFORM</small></span></a>
        <div class="top-actions"><span class="pill active">Patient Portal</span><span class="pill">Search your logs</span><span class="pill">Today, {{ now()->format('M d') }}</span><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="pill" style="cursor:pointer">Log out</button></form></div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <div class="side-label">Patient module</div>
            <a class="side-link" href="{{ route('dashboard') }}"><span>◉</span><span>Dashboard</span></a>
            <a class="side-link {{ $type === 'food' ? 'current' : '' }}" href="{{ route('food.log') }}"><span>🍴</span><span>Food Intake Logger</span></a>
            <a class="side-link {{ $type === 'symptom' ? 'current' : '' }}" href="{{ route('symptom.log') }}"><span>⌁</span><span>Symptom Tracker</span></a>
            <a class="side-link {{ $type === 'sleep' ? 'current' : '' }}" href="{{ route('sleep.log') }}"><span>☾</span><span>Sleep &amp; Stress</span></a>
            <a class="side-link" href="{{ route('dashboard') }}#insights"><span>✣</span><span>AI Pattern Insights</span></a>
            <a class="side-link" href="{{ route('dashboard') }}#recommendations"><span>♧</span><span>Food Recommendations</span></a>
            <a class="side-link" href="{{ route('dashboard') }}#history"><span>▣</span><span>Health History / Profile</span></a>
            <div class="side-label">Admin module</div>
            <a class="side-link" href="#"><span>⌁</span><span>Analytics Overview</span></a>
            <a class="side-link" href="#"><span>⚒</span><span>Category &amp; Trigger Rules</span></a>
            <a class="side-link" href="#"><span>♧</span><span>Cohort Management</span></a>
        </aside>

        <main class="main log-main">
            <div class="log-screen">
    <div class="log-layout">
        <section class="log-intro">
            <div class="log-eyebrow"><span></span> Simple daily check-in</div>
            <h1>{{ $title }}</h1>
            <p>{{ $description }}</p>
            <div class="log-steps">
                <div class="log-step"><b>1</b><div><strong>Enter today’s details</strong><small>Use the form on this page. Only the fields marked required need to be completed.</small></div></div>
                <div class="log-step"><b>2</b><div><strong>Save your entry</strong><small>Your information is stored privately under your patient account.</small></div></div>
                <div class="log-step"><b>3</b><div><strong>See your new entry here</strong><small>After saving, this page refreshes and shows the new record below the form.</small></div></div>
            </div>
        </section>

        <section class="log-card">
            <div class="log-card-header">
                <div><h2>{{ $isFood ? 'What did you eat?' : ($isSymptom ? 'How are you feeling?' : 'How did you sleep?') }}</h2><p>Complete this check-in to keep your health timeline up to date.</p></div>
                <div class="log-icon">{{ $isFood ? '🍴' : ($isSymptom ? '⌁' : '☾') }}</div>
            </div>

            <form id="log-form">
                @if($isFood)
                    <div class="log-field"><label for="food_name">Food or meal name <span>*</span></label><input id="food_name" name="food_name" required placeholder="Example: oatmeal with banana"><small class="log-hint">You can enter one food or a complete meal.</small></div>
                    <div class="log-two"><div class="log-field"><label for="category">Food category <span>*</span></label><select id="category" name="category" required><option value="" selected disabled>Select a category</option><option value="acidic">Acidic</option><option value="oily">Oily</option><option value="spicy">Spicy</option><option value="dairy">Dairy</option><option value="other">Other / none of these</option></select><small class="log-hint">Choose “Other / none of these” when the food is not acidic, oily, spicy, or dairy.</small></div><div class="log-field"><label for="time">When did you eat it? <span>*</span></label><input id="time" type="datetime-local" name="time" value="{{ now()->format('Y-m-d\TH:i') }}" required></div></div>
                    <div class="log-field"><label for="notes">Notes <small>(optional)</small></label><textarea id="notes" name="notes" placeholder="How much did you eat? Any preparation details?"></textarea></div>
                @elseif($isSymptom)
                    <div class="log-two"><div class="log-field"><label for="symptom_type">Symptom <span>*</span></label><select id="symptom_type" name="symptom_type" required><option value="heartburn">Heartburn</option><option value="bloating">Bloating</option><option value="pain">Pain</option></select></div><div class="log-field"><label for="intensity">Intensity <span>*</span></label><select id="intensity" name="intensity" required><option value="1">1 — Very mild</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5" selected>5 — Moderate</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10 — Severe</option></select></div></div>
                    <div class="log-field"><label for="time">When did it happen? <span>*</span></label><input id="time" type="datetime-local" name="time" value="{{ now()->format('Y-m-d\TH:i') }}" required><small class="log-hint">Logging the time helps compare symptoms with meals and sleep.</small></div>
                    <div class="log-field"><label for="notes">What did it feel like? <small>(optional)</small></label><textarea id="notes" name="notes" placeholder="Add anything that may help you remember the episode."></textarea></div>
                @else
                    <div class="log-two"><div class="log-field"><label for="sleep_hours">Hours slept <span>*</span></label><input id="sleep_hours" type="number" step="0.1" min="0" max="24" name="sleep_hours" value="7" required></div><div class="log-field"><label for="sleep_quality">Sleep quality <span>*</span></label><select id="sleep_quality" name="sleep_quality" required><option value="1">1 — Poor</option><option value="2">2</option><option value="3" selected>3 — Average</option><option value="4">4</option><option value="5">5 — Excellent</option></select></div></div>
                    <div class="log-field"><label for="stress_level">Stress level <span>*</span></label><select id="stress_level" name="stress_level" required><option value="1">1 — Very low</option><option value="2">2</option><option value="3" selected>3 — Moderate</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10 — Very high</option></select></div>
                    <div class="log-field"><label for="logged_on">Date <span>*</span></label><input id="logged_on" type="date" name="logged_on" value="{{ now()->toDateString() }}" required></div>
                    <div class="log-field"><label for="notes">Notes <small>(optional)</small></label><textarea id="notes" name="notes" placeholder="Anything that affected your sleep or stress?"></textarea></div>
                @endif
                <button class="log-submit" id="log-submit" type="submit">Save {{ $isFood ? 'food entry' : ($isSymptom ? 'symptom entry' : 'sleep entry') }}</button>
                <p id="log-status" class="log-status" aria-live="polite"></p>
            </form>
            <section class="recent-logs">
                <h3>{{ $isFood ? 'Your recent food entries' : ($isSymptom ? 'Your recent symptom entries' : 'Your recent sleep entries') }}</h3>
                <div class="recent-log-list">
                    @forelse($recentLogs as $log)
                        <div class="recent-log">
                            <div>
                                @if($isFood)
                                    <strong>{{ $log->food_name }}</strong><small>{{ ucfirst($log->category) }} · {{ $log->time->format('M d, Y g:i A') }}</small>
                                @elseif($isSymptom)
                                    <strong>{{ ucfirst($log->symptom_type) }}</strong><small>Intensity {{ $log->intensity }}/10 · {{ $log->time->format('M d, Y g:i A') }}</small>
                                @else
                                    <strong>{{ number_format($log->sleep_hours, 1) }} hours of sleep</strong><small>Quality {{ $log->sleep_quality }}/5 · Stress {{ $log->stress_level }}/10 · {{ $log->logged_on->format('M d, Y') }}</small>
                                @endif
                            </div>
                            @if($loop->first)<span class="recent-log-badge">Latest</span>@endif
                        </div>
                    @empty
                        <p class="log-hint">No entries yet. Your saved records will appear here.</p>
                    @endforelse
                </div>
            </section>
        </section>
    </div>
            </div>
        </main>
    </div>
</div>

<script>
    const logForm = document.getElementById('log-form');
    const logStatus = document.getElementById('log-status');
    const logSubmit = document.getElementById('log-submit');
    const logToken = @json(session('api_token'));
    const endpoint = @json($endpoint);

    logForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        logSubmit.disabled = true;
        logStatus.className = 'log-status';
        logStatus.textContent = 'Saving your entry…';

        const data = Object.fromEntries(new FormData(logForm).entries());
        if (data.time) data.time = new Date(data.time).toISOString();

        try {
            const response = await fetch(`/api/${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${logToken}`,
                },
                body: JSON.stringify(data),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Please check the form and try again.');
            logStatus.className = 'log-status success';
            logStatus.textContent = 'Saved successfully. Updating your entries below…';
            setTimeout(() => window.location.reload(), 800);
        } catch (error) {
            logSubmit.disabled = false;
            logStatus.className = 'log-status error';
            logStatus.textContent = error.message;
        }
    });
</script>
@endsection
