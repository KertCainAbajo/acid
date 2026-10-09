@extends('layouts.app')

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0];
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $riskLevel = $latestInsight->risk_level;
    $riskGauge = ['low' => 30, 'medium' => 62, 'high' => 92][$riskLevel] ?? 30;
    $riskCopy = ['low' => 'Your recent pattern looks stable.', 'medium' => 'Some patterns need a closer look.', 'high' => 'Your recent pattern deserves attention.'][$riskLevel] ?? 'Your recent pattern deserves attention.';
    $weekMax = max(1, collect($weeklyActivity)->max(fn (array $day): int => max($day['foods'], $day['symptoms'])));
    $weekFoodTotal = collect($weeklyActivity)->sum('foods');
    $weekSymptomTotal = collect($weeklyActivity)->sum('symptoms');
    $activeDays = collect($weeklyActivity)->filter(fn (array $day): bool => $day['foods'] + $day['symptoms'] > 0)->count();
    $topTriggerCount = max(1, (int) $triggers->max('count'));
    $sleepPercent = $sleep ? min(100, round($sleep->sleep_hours / 8 * 100)) : 0;
@endphp
<style>
    .db { display:grid; gap:20px; }
    .db-icon { display:grid; place-items:center; flex:none; width:40px; height:40px; border-radius:12px; }
    .db-icon svg, .db-svg { width:19px; height:19px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .db-icon.is-green { color:#047857; background:linear-gradient(145deg,#ecfdf5,#d1fae5); }
    .db-icon.is-orange { color:#c2410c; background:linear-gradient(145deg,#fff7ed,#ffedd5); }
    .db-icon.is-red { color:#b91c1c; background:linear-gradient(145deg,#fef2f2,#fee2e2); }
    .db-icon.is-indigo { color:#4338ca; background:linear-gradient(145deg,#eef2ff,#e0e7ff); }
    .db-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:11.5px; font-weight:700; white-space:nowrap; }
    .db-chip.is-green { color:#065f46; background:#d1fae5; } .db-chip.is-amber { color:#92400e; background:#fef3c7; } .db-chip.is-red { color:#991b1b; background:#fee2e2; } .db-chip.is-slate { color:#475569; background:#f1f5f9; }
    .db-chip i { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .db-panel { position:relative; padding:22px; border:1px solid #ffffff; border-radius:22px; background:#ffffffd9; box-shadow:var(--shadow-card); backdrop-filter:blur(10px); transition:transform .25s ease,box-shadow .25s ease; }
    .db-panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:18px; }
    .db-panel-head h2 { margin:0; font-size:17px; font-weight:750; letter-spacing:-.02em; }
    .db-panel-head p { margin:3px 0 0; color:var(--muted); font-size:13px; }
    .db-link { display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:10px; color:var(--green); background:#ecfdf5; font-size:12.5px; font-weight:700; white-space:nowrap; transition:background .2s ease,transform .2s ease; }
    .db-link:hover { background:#d1fae5; transform:translateY(-1px); }
    .db-link svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .db-empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:26px 16px; border:1.5px dashed #d8e3df; border-radius:16px; color:var(--muted); background:#fafcfb; font-size:13px; text-align:center; }
    .db-empty svg { width:34px; height:34px; padding:8px; border-radius:11px; fill:none; stroke:#10b981; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; background:#ecfdf5; }
    .db-empty strong { color:var(--ink); font-size:13.5px; }

    /* Hero */
    .db-hero { position:relative; display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:28px; padding:30px 32px; overflow:hidden; isolation:isolate; border-radius:26px; color:#ecfdf5; background:linear-gradient(150deg,#0d4637 0%,#0a3229 55%,#071f1a 100%); box-shadow:0 30px 60px -30px #0a2e26cc; }
    .db-hero:before { content:""; position:absolute; inset:0; z-index:-1; background-image:linear-gradient(#ffffff0d 1px,transparent 1px),linear-gradient(90deg,#ffffff0d 1px,transparent 1px); background-size:36px 36px; mask-image:radial-gradient(ellipse at 30% 40%,#000 0,transparent 70%); }
    .db-hero-glow { position:absolute; z-index:-1; border-radius:50%; filter:blur(50px); animation:dbFloat 14s ease-in-out infinite alternate; }
    .db-hero-glow.one { top:-120px; right:20%; width:320px; height:320px; background:#10b98166; }
    .db-hero-glow.two { bottom:-140px; left:-60px; width:280px; height:280px; background:#0ea5e933; animation-duration:18s; }
    .db-live { display:inline-flex; align-items:center; gap:8px; padding:5px 11px 5px 9px; border:1px solid #6ee7b733; border-radius:999px; color:#a7f3d0; background:#ffffff0f; font-size:10.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .db-live i { width:7px; height:7px; border-radius:50%; background:#34d399; animation:dbPulse 2.2s ease-in-out infinite; }
    .db-hero h1 { margin:14px 0 10px; color:#fff; font-size:clamp(26px,2.5vw,34px); font-weight:800; line-height:1.1; letter-spacing:-.035em; }
    .db-hero h1 span { color:transparent; background:linear-gradient(95deg,#6ee7b7,#34d399 60%,#a7f3d0); -webkit-background-clip:text; background-clip:text; }
    .db-hero-status { display:flex; flex-wrap:wrap; gap:8px; }
    .db-hero-status span { display:inline-flex; align-items:center; gap:7px; padding:6px 12px; border:1px solid #ffffff1f; border-radius:999px; color:#d1fae5; background:#ffffff12; font-size:12.5px; }
    .db-hero-status strong { color:#fff; }
    .db-hero-status svg { width:14px; height:14px; fill:none; stroke:#6ee7b7; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .db-hero p { max-width:52ch; margin:14px 0 0; color:#a9cbbf; font-size:13.5px; }
    .db-quick { padding:14px; border:1px solid #ffffff1f; border-radius:22px; background:#ffffff0f; backdrop-filter:blur(14px); }
    .db-quick-title { display:flex; align-items:center; justify-content:space-between; gap:10px; margin:0 4px 12px; color:#a7f3d0; font-size:10.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
    .db-quick-grid { display:grid; grid-template-columns:repeat(3,128px); gap:10px; }
    .db-quick a { position:relative; display:flex; flex-direction:column; gap:12px; padding:14px; border-radius:16px; color:var(--ink); background:#fff; box-shadow:0 10px 24px -12px #000000aa; transition:transform .25s cubic-bezier(.2,.8,.2,1),box-shadow .25s ease; }
    .db-quick a:hover { transform:translateY(-4px); box-shadow:0 18px 32px -12px #000000cc; }
    .db-quick a:focus-visible { outline:3px solid #6ee7b7; outline-offset:3px; }
    .db-quick strong { display:block; font-size:13.5px; font-weight:750; }
    .db-quick small { display:block; margin-top:1px; color:var(--muted); font-size:11.5px; }
    .db-quick-plus { position:absolute; top:12px; right:12px; width:22px; height:22px; display:grid; place-items:center; border-radius:50%; color:#94a3b8; background:#f1f5f9; transition:background .2s ease,color .2s ease,transform .25s ease; }
    .db-quick-plus svg { width:12px; height:12px; fill:none; stroke:currentColor; stroke-width:2.6; stroke-linecap:round; }
    .db-quick a:hover .db-quick-plus { color:#fff; background:var(--green); transform:rotate(90deg); }

    /* KPI cards */
    .db-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
    .db-kpi { display:flex; flex-direction:column; gap:14px; padding:20px; }
    .db-kpi:hover, .db-panel.is-lift:hover { transform:translateY(-3px); box-shadow:0 1px 2px #0f172a0a,0 28px 50px -24px #0a5a4059; }
    .db-kpi-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .db-kpi-label { color:#475569; font-size:13px; font-weight:600; }
    .db-kpi-value { display:flex; align-items:baseline; gap:6px; margin-top:-4px; font-size:28px; font-weight:800; line-height:1.1; letter-spacing:-.04em; }
    .db-kpi-value small { color:var(--muted); font-size:13px; font-weight:500; letter-spacing:0; }
    .db-kpi-value.is-green { color:var(--green); } .db-kpi-value.is-amber { color:#b45309; } .db-kpi-value.is-red { color:#b91c1c; }
    .db-kpi p { margin:0; color:var(--muted); font-size:12.5px; line-height:1.5; }
    .db-kpi-foot { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:auto; padding-top:14px; border-top:1px solid #eef2f1; }
    .db-gauge { position:relative; width:72px; height:40px; flex:none; }
    .db-gauge svg { width:72px; height:40px; overflow:visible; }
    .db-gauge path { fill:none; stroke-width:8; stroke-linecap:round; }
    .db-gauge .track { stroke:#eef2f1; }
    .db-gauge .value { stroke:url(#db-risk-gradient); stroke-dasharray:100; animation:dbDraw 1.2s .3s cubic-bezier(.2,.8,.2,1) both; }
    .db-meter { display:flex; gap:4px; }
    .db-meter i { flex:1; height:6px; border-radius:4px; background:#eef2f1; }
    .db-meter i.is-on { background:linear-gradient(90deg,#34d399,#0f7a56); }
    .db-meter.is-red i.is-on { background:linear-gradient(90deg,#fb923c,#dc2626); }
    .db-ring { position:relative; width:52px; height:52px; flex:none; }
    .db-ring svg { width:52px; height:52px; transform:rotate(-90deg); }
    .db-ring circle { fill:none; stroke-width:6; }
    .db-ring .track { stroke:#eef2ff; } .db-ring .value { stroke:#6366f1; stroke-linecap:round; stroke-dasharray:100; animation:dbDraw 1.2s .3s cubic-bezier(.2,.8,.2,1) both; }
    .db-ring b { position:absolute; inset:0; display:grid; place-items:center; color:#3730a3; font-size:11px; font-weight:800; }
    .db-kpi-row { display:flex; align-items:center; justify-content:space-between; gap:12px; }

    /* Main grid */
    .db-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(0,1fr); gap:20px; }
    .db-insight { overflow:hidden; isolation:isolate; }
    .db-insight:before { content:""; position:absolute; inset:0; z-index:-1; padding:1.5px; border-radius:inherit; background:linear-gradient(135deg,#34d399,#a7f3d0 40%,#e0f2fe 70%,#c7d2fe); -webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0); -webkit-mask-composite:xor; mask-composite:exclude; }
    .db-insight:after { content:""; position:absolute; top:-80px; right:-80px; z-index:-1; width:220px; height:220px; border-radius:50%; background:radial-gradient(circle,#d1fae5,transparent 70%); }
    .db-insight-tag { display:inline-flex; align-items:center; gap:7px; color:var(--green); font-size:11.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .db-insight-tag svg { width:16px; height:16px; fill:currentColor; stroke:none; }
    .db-insight h2 { margin:14px 0 10px; font-size:22px; font-weight:800; letter-spacing:-.03em; }
    .db-insight-text { margin:0; color:#475569; font-size:14px; line-height:1.7; }
    .db-next { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:20px; padding:16px; border:1px solid #e0e7ff; border-radius:16px; background:linear-gradient(120deg,#f5f7ff,#f0fdf4); }
    .db-next-copy { display:flex; align-items:center; gap:12px; }
    .db-next strong { display:block; font-size:14px; }
    .db-next span { color:var(--muted); font-size:12.5px; }
    .db-btn { display:inline-flex; align-items:center; gap:8px; padding:11px 18px; border:0; border-radius:12px; color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); box-shadow:inset 0 1px 0 #ffffff33,0 12px 22px -10px #0a5a40cc; font-size:13.5px; font-weight:700; white-space:nowrap; cursor:pointer; transition:transform .2s ease,box-shadow .2s ease; }
    .db-btn:hover { transform:translateY(-2px); box-shadow:inset 0 1px 0 #ffffff33,0 16px 28px -10px #0a5a40e6; }
    .db-btn svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; transition:transform .2s ease; }
    .db-btn:hover svg { transform:translateX(3px); }
    .db-disclaimer { display:flex; align-items:center; gap:7px; margin:14px 0 0; color:#94a3b8; font-size:11.5px; }
    .db-disclaimer svg { width:13px; height:13px; flex:none; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }

    /* Weekly chart */
    .db-legend { display:flex; gap:12px; color:var(--muted); font-size:12px; }
    .db-legend span { display:inline-flex; align-items:center; gap:6px; }
    .db-legend i { width:10px; height:10px; border-radius:3px; }
    .db-legend .is-food { background:#10b981; } .db-legend .is-symptom { background:#f87171; }
    .db-chart { position:relative; display:grid; grid-template-columns:repeat(7,1fr); gap:8px; height:170px; padding:0 4px; background:repeating-linear-gradient(to top,#eef2f1 0 1px,transparent 1px 42px); border-bottom:1px solid #e2e8f0; }
    .db-day { position:relative; display:flex; align-items:flex-end; justify-content:center; gap:4px; height:100%; border-radius:10px; transition:background .2s ease; }
    .db-day:hover { background:#f0fdf499; }
    .db-day.is-today { background:linear-gradient(to top,#ecfdf5,transparent); }
    .db-bar { width:min(14px,32%); min-height:4px; border-radius:6px 6px 2px 2px; transform-origin:bottom; animation:dbGrow .8s cubic-bezier(.2,.8,.2,1) both; }
    .db-bar.is-food { background:linear-gradient(to top,#059669,#34d399); } .db-bar.is-symptom { background:linear-gradient(to top,#dc2626,#f87171); }
    .db-bar.is-zero { opacity:.35; }
    .db-tip { position:absolute; bottom:calc(100% + 8px); left:50%; z-index:5; min-width:130px; padding:9px 11px; border-radius:11px; color:#fff; background:#0b1f2a; box-shadow:0 12px 24px -8px #0b1f2a99; font-size:11.5px; line-height:1.5; opacity:0; pointer-events:none; transform:translate(-50%,6px); transition:opacity .18s ease,transform .18s ease; }
    .db-tip strong { display:block; margin-bottom:2px; font-size:12px; }
    .db-day:hover .db-tip { opacity:1; transform:translate(-50%,0); }
    .db-chart-empty { position:absolute; inset:0; display:grid; place-content:center; justify-items:center; gap:4px; color:var(--muted); font-size:12.5px; text-align:center; }
    .db-chart-empty strong { color:var(--ink); font-size:13.5px; }
    .db-days { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; margin-top:8px; padding:0 4px; color:#94a3b8; font-size:11.5px; font-weight:600; text-align:center; }
    .db-days .is-today { color:var(--green); }
    .db-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:18px; }
    .db-stat { padding:12px; border:1px solid #eef2f1; border-radius:14px; background:#fafcfb; }
    .db-stat span { display:block; color:var(--muted); font-size:11.5px; }
    .db-stat strong { display:block; margin-top:2px; font-size:19px; font-weight:800; letter-spacing:-.03em; }

    /* Timeline */
    .db-timeline { position:relative; display:grid; gap:4px; }
    .db-event { position:relative; display:grid; grid-template-columns:40px minmax(0,1fr) auto; align-items:center; gap:14px; padding:10px 12px 10px 0; border-radius:14px; transition:background .2s ease; }
    .db-event:hover { background:#f8fafa; }
    .db-event:not(:last-child):after { content:""; position:absolute; left:19px; top:52px; bottom:-12px; width:2px; background:linear-gradient(#e2e8f0,#f1f5f9); }
    .db-event .db-icon { position:relative; z-index:1; box-shadow:0 0 0 4px #fff; }
    .db-event strong { display:block; overflow:hidden; font-size:13.5px; text-overflow:ellipsis; white-space:nowrap; }
    .db-event span { color:var(--muted); font-size:12px; }
    .db-event time { color:#94a3b8; font-size:11.5px; white-space:nowrap; }

    /* Triggers */
    .db-triggers { display:grid; gap:10px; }
    .db-trigger { display:grid; grid-template-columns:28px minmax(0,1fr) auto; align-items:center; gap:12px; padding:12px 14px; border:1px solid #f1f5f4; border-radius:14px; background:#fafcfb; transition:border-color .2s ease,transform .2s ease; }
    .db-trigger:hover { border-color:#fecaca; transform:translateX(3px); }
    .db-rank { width:28px; height:28px; display:grid; place-items:center; border-radius:9px; color:#b91c1c; background:#fee2e2; font-size:12px; font-weight:800; }
    .db-trigger strong { display:block; font-size:13.5px; }
    .db-trigger-bar { height:6px; margin-top:7px; border-radius:6px; background:#f1f5f9; overflow:hidden; }
    .db-trigger-bar i { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#fb923c,#dc2626); transform-origin:left; animation:dbGrowX .9s .2s cubic-bezier(.2,.8,.2,1) both; }

    @keyframes dbFloat { to { transform:translate(-40px,30px) scale(1.15); } }
    @keyframes dbPulse { 0%,100% { box-shadow:0 0 0 3px #34d39940; } 50% { box-shadow:0 0 0 7px #34d39910; } }
    @keyframes dbDraw { from { stroke-dashoffset:100; } }
    @keyframes dbGrow { from { transform:scaleY(0); } }
    @keyframes dbGrowX { from { transform:scaleX(0); } }
    @media (max-width:1280px) { .db-hero { grid-template-columns:1fr; } .db-quick-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .db-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:1000px) { .db-grid { grid-template-columns:minmax(0,1fr); } }
    @media (max-width:700px) { .db-hero { padding:24px 20px; } .db-kpis { grid-template-columns:minmax(0,1fr); } .db-quick a { padding:12px; gap:10px; } .db-quick small { display:none; } .db-next { flex-direction:column; align-items:flex-start; } .db-stats { grid-template-columns:1fr 1fr 1fr; } .db-panel { padding:18px; } }
    @media (max-width:420px) { .db-quick-grid { grid-template-columns:minmax(0,1fr); } .db-quick small { display:block; } }
</style>

<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs><linearGradient id="db-risk-gradient" x1="0" x2="1"><stop offset="0" stop-color="#34d399"/><stop offset=".55" stop-color="#f59e0b"/><stop offset="1" stop-color="#dc2626"/></linearGradient></defs></svg>

<div class="shell">
    @include('partials.topbar')
    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'dashboard'])
        <main class="main db">
            <section class="db-hero">
                <span class="db-hero-glow one" aria-hidden="true"></span><span class="db-hero-glow two" aria-hidden="true"></span>
                <div>
                    <span class="db-live"><i></i> Live health timeline · Updated just now</span>
                    <h1>{{ $greeting }}, <span>{{ $firstName }}</span></h1>
                    <div class="db-hero-status">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c1.6 2.2 5 4.5 5 8.4A5 5 0 0 1 7 11.4C7 8.1 9.6 6 12 3Z"/></svg> Esophagus state: <strong>{{ $todaySymptomCount ? 'Symptoms reported today' : 'Calm tracking day' }}</strong></span>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/></svg> <strong>{{ ucfirst($riskLevel) }}</strong> risk</span>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18"/></svg> Logged on <strong>{{ $activeDays }}/7</strong> days</span>
                    </div>
                    <p>Keep logging consistently to make your weekly patterns more reliable.</p>
                </div>

                <div class="db-quick" aria-label="Quick check-in actions">
                    <div class="db-quick-title"><span>Quick check-in</span><span>{{ now()->format('l') }}</span></div>
                    <div class="db-quick-grid">
                        <a href="{{ route('food.log') }}">
                            <span class="db-icon is-green"><svg viewBox="0 0 24 24"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg></span>
                            <span><strong>Log Food</strong><small>Meal or drink</small></span>
                            <span class="db-quick-plus"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span>
                        </a>
                        <a href="{{ route('symptom.log') }}">
                            <span class="db-icon is-red"><svg viewBox="0 0 24 24"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg></span>
                            <span><strong>Log Reflux</strong><small>How you feel</small></span>
                            <span class="db-quick-plus"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span>
                        </a>
                        <a href="{{ route('sleep.log') }}">
                            <span class="db-icon is-indigo"><svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg></span>
                            <span><strong>Sleep &amp; Stress</strong><small>Rest check-in</small></span>
                            <span class="db-quick-plus"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span>
                        </a>
                    </div>
                </div>
            </section>

            <section class="db-kpis">
                <article class="db-panel db-kpi">
                    <div class="db-kpi-head"><span class="db-kpi-label">Acid-Risk Index</span><span class="db-icon is-green"><svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/></svg></span></div>
                    <div class="db-kpi-row">
                        <div class="db-kpi-value {{ $riskLevel === 'low' ? 'is-green' : ($riskLevel === 'medium' ? 'is-amber' : 'is-red') }}">{{ ucfirst($riskLevel) }} <small>risk</small></div>
                        <span class="db-gauge" aria-hidden="true"><svg viewBox="0 0 72 40"><path class="track" d="M6 36a30 30 0 0 1 60 0" pathLength="100"/><path class="value" d="M6 36a30 30 0 0 1 60 0" pathLength="100" style="stroke-dashoffset:{{ 100 - $riskGauge }}"/></svg></span>
                    </div>
                    <p>Based on your recent symptom, food, sleep and stress patterns.</p>
                    <div class="db-kpi-foot"><span class="db-chip is-green"><i></i> Weekly insight ready</span></div>
                </article>

                <article class="db-panel db-kpi">
                    <div class="db-kpi-head"><span class="db-kpi-label">Today's Intake</span><span class="db-icon is-orange"><svg viewBox="0 0 24 24"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg></span></div>
                    <div class="db-kpi-value">{{ $todayFoodCount }} <small>food entr{{ $todayFoodCount === 1 ? 'y' : 'ies' }}</small></div>
                    <div class="db-meter" aria-hidden="true">@for ($meal = 1; $meal <= 4; $meal++)<i class="{{ $todayFoodCount >= $meal ? 'is-on' : '' }}"></i>@endfor</div>
                    <p>Keep meals and notes consistent for better trigger matching.</p>
                    <div class="db-kpi-foot"><span class="db-chip {{ $todayFoodCount ? 'is-green' : 'is-slate' }}"><i></i> {{ $todayFoodCount ? 'Logging started' : 'No meals logged yet' }}</span><a class="db-link" href="{{ route('food.log') }}">Add</a></div>
                </article>

                <article class="db-panel db-kpi">
                    <div class="db-kpi-head"><span class="db-kpi-label">Symptom Activity</span><span class="db-icon is-red"><svg viewBox="0 0 24 24"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg></span></div>
                    <div class="db-kpi-value {{ $todaySymptomCount ? 'is-red' : 'is-green' }}">{{ $todaySymptomCount ? $todaySymptomCount.' logged' : 'No acute reflux' }}</div>
                    <div class="db-meter is-red" aria-hidden="true">@for ($level = 1; $level <= 4; $level++)<i class="{{ $todaySymptomCount >= $level ? 'is-on' : '' }}"></i>@endfor</div>
                    <p>{{ $todaySymptomCount ? 'Review the latest symptom entry below.' : 'No symptoms logged today.' }}</p>
                    <div class="db-kpi-foot"><span class="db-chip {{ $todaySymptomCount ? 'is-amber' : 'is-green' }}"><i></i> {{ $todaySymptomCount ? 'Monitor your pattern' : 'Quiet tracking day' }}</span></div>
                </article>

                <article class="db-panel db-kpi">
                    <div class="db-kpi-head"><span class="db-kpi-label">Sleep &amp; Stress</span><span class="db-icon is-indigo"><svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg></span></div>
                    <div class="db-kpi-row">
                        <div class="db-kpi-value">{{ $sleep ? number_format($sleep->sleep_hours, 1) : '—' }} <small>{{ $sleep ? 'hrs sleep' : 'no data' }}</small></div>
                        <span class="db-ring" aria-hidden="true"><svg viewBox="0 0 52 52"><circle class="track" cx="26" cy="26" r="21" pathLength="100"/><circle class="value" cx="26" cy="26" r="21" pathLength="100" style="stroke-dashoffset:{{ 100 - $sleepPercent }}"/></svg><b>{{ $sleepPercent }}%</b></span>
                    </div>
                    <p>{{ $sleep ? 'Stress '.$sleep->stress_level.'/10 · Quality '.$sleep->sleep_quality.'/5' : 'Sleep data improves your risk estimate.' }}</p>
                    <div class="db-kpi-foot"><span class="db-chip {{ $sleep ? 'is-green' : 'is-slate' }}"><i></i> {{ $sleep ? 'Latest check-in' : 'Not logged yet' }}</span><a class="db-link" href="{{ route('sleep.log') }}">{{ $sleep ? 'Update' : 'Add data' }}</a></div>
                </article>
            </section>

            <div class="db-grid">
                <section class="db-panel db-insight is-lift" id="insights">
                    <div class="db-panel-head" style="margin-bottom:0">
                        <span class="db-insight-tag"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9zM19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z"/></svg> AI Pattern Insight</span>
                        <span class="db-chip {{ $riskLevel === 'low' ? 'is-green' : ($riskLevel === 'medium' ? 'is-amber' : 'is-red') }}"><i></i> {{ ucfirst($riskLevel) }} risk</span>
                    </div>
                    <h2>{{ $riskCopy }}</h2>
                    <p class="db-insight-text">{{ $latestInsight->insight_text }}</p>
                    <div class="db-next">
                        <div class="db-next-copy">
                            <span class="db-icon is-green"><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                            <div><strong>Recommended next step</strong><span>Log your next meal or symptom while it is fresh.</span></div>
                        </div>
                        <a class="db-btn" href="{{ route('food.log') }}">Add a log <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                    </div>
                    <p class="db-disclaimer"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg> This is a tracking aid, not a medical diagnosis.</p>
                </section>

                <section class="db-panel is-lift">
                    <div class="db-panel-head">
                        <div><h2>Weekly correlation</h2><p>Symptoms vs. food logging · last 7 days</p></div>
                        <div class="db-legend"><span><i class="is-food"></i> Food</span><span><i class="is-symptom"></i> Symptoms</span></div>
                    </div>
                    <div class="db-chart" role="img" aria-label="Food and symptom logs for the last 7 days">
                        @foreach ($weeklyActivity as $index => $day)
                            <div class="db-day {{ $day['isToday'] ? 'is-today' : '' }}">
                                <i class="db-bar is-food {{ $day['foods'] ? '' : 'is-zero' }}" style="height:{{ $day['foods'] / $weekMax * 100 }}%;animation-delay:{{ $index * 0.05 }}s"></i>
                                <i class="db-bar is-symptom {{ $day['symptoms'] ? '' : 'is-zero' }}" style="height:{{ $day['symptoms'] / $weekMax * 100 }}%;animation-delay:{{ $index * 0.05 + 0.03 }}s"></i>
                                <span class="db-tip"><strong>{{ $day['label'] }}, {{ $day['date'] }}</strong>{{ $day['foods'] }} food · {{ $day['symptoms'] }} symptom{{ $day['symptoms'] === 1 ? '' : 's' }}@if ($day['symptoms'])<br>Peak intensity {{ $day['maxIntensity'] }}/10 @endif</span>
                            </div>
                        @endforeach
                        @if ($weekFoodTotal + $weekSymptomTotal === 0)
                            <div class="db-chart-empty"><strong>No logs in the last 7 days</strong>Your weekly pattern appears as you log meals and symptoms.</div>
                        @endif
                    </div>
                    <div class="db-days">@foreach ($weeklyActivity as $day)<span class="{{ $day['isToday'] ? 'is-today' : '' }}">{{ $day['isToday'] ? 'Today' : $day['label'] }}</span>@endforeach</div>
                    <div class="db-stats">
                        <div class="db-stat"><span>Food logs</span><strong>{{ $weekFoodTotal }}</strong></div>
                        <div class="db-stat"><span>Symptoms</span><strong>{{ $weekSymptomTotal }}</strong></div>
                        <div class="db-stat"><span>Avg. severity</span><strong>{{ number_format($weeklyAverageIntensity, 1) }}<small style="color:var(--muted);font-size:12px;font-weight:600">/10</small></strong></div>
                    </div>
                </section>

                <section class="db-panel is-lift" id="history">
                    <div class="db-panel-head">
                        <div><h2>Recent health timeline</h2><p>Your latest food and symptom records</p></div>
                        <a class="db-link" href="{{ route('history') }}">View all <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                    </div>
                    <div class="db-timeline">
                        @forelse ($timeline as $event)
                            <div class="db-event">
                                @if ($event['kind'] === 'food')
                                    <span class="db-icon is-green"><svg viewBox="0 0 24 24"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg></span>
                                @else
                                    <span class="db-icon is-red"><svg viewBox="0 0 24 24"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg></span>
                                @endif
                                <div><strong>{{ $event['title'] }}</strong><span>{{ $event['kind'] === 'food' ? 'Food' : 'Symptom' }} · {{ $event['meta'] }}</span></div>
                                <time datetime="{{ $event['at']->toIso8601String() }}" title="{{ $event['at']->format('M j, Y g:i A') }}">{{ $event['at']->diffForHumans() }}</time>
                            </div>
                        @empty
                            <div class="db-empty">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg>
                                <strong>No entries yet</strong>
                                Your timeline will appear here as you log data.
                                <a class="db-link" href="{{ route('food.log') }}">Log your first meal</a>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="db-panel is-lift" id="recommendations">
                    <div class="db-panel-head">
                        <div><h2>Dominant triggers</h2><p>Based on your last 30 days</p></div>
                        <a class="db-link" href="{{ route('recommendations') }}">Safer choices</a>
                    </div>
                    <div class="db-triggers">
                        @forelse ($triggers as $trigger)
                            <div class="db-trigger">
                                <span class="db-rank">{{ $loop->iteration }}</span>
                                <div><strong>{{ $trigger['food'] }}</strong><div class="db-trigger-bar"><i style="width:{{ $trigger['count'] / $topTriggerCount * 100 }}%"></i></div></div>
                                <span class="db-chip is-red">{{ $trigger['count'] }} symptom{{ $trigger['count'] === 1 ? '' : 's' }}</span>
                            </div>
                        @empty
                            <div class="db-empty">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                                <strong>No consistent trigger identified yet</strong>
                                Log meals and symptoms together so patterns can be matched.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<div id="logger" style="display:none;position:fixed;inset:0;z-index:10;background:#102c3566;padding:30px"><div class="auth-card" style="max-width:480px;margin:8vh auto"><div class="section-title"><h2 id="logger-title">Add a log</h2><button class="pill" onclick="closeLogger()">Close</button></div><form id="logger-form"><div id="logger-fields"></div><button class="btn" type="submit">Save log</button></form><p id="logger-result" class="subtle" style="margin:12px 0 0"></p></div></div>
<script>
    window.refluxApiToken = @json(session('api_token'));
    const logger = document.getElementById('logger');
    const fields = document.getElementById('logger-fields');
    const result = document.getElementById('logger-result');
    let loggerType = 'food';
    function openLogger(type) { loggerType = type; result.textContent = ''; logger.style.display = 'block'; const now = new Date().toISOString().slice(0,16); if (type === 'food') { document.getElementById('logger-title').textContent = 'Log food intake'; fields.innerHTML = `<div class="field"><label>Food name</label><input name="food_name" required placeholder="e.g. oatmeal"></div><div class="field"><label>Category</label><select name="category" style="width:100%;padding:13px;border:1px solid #dce8e8;border-radius:11px"><option value="acidic">Acidic</option><option value="oily">Oily</option><option value="spicy">Spicy</option><option value="dairy">Dairy</option></select></div><div class="field"><label>Time</label><input type="datetime-local" name="time" value="${now}" required></div><div class="field"><label>Notes</label><input name="notes" placeholder="Optional notes"></div>`; } else if (type === 'symptom') { document.getElementById('logger-title').textContent = 'Log reflux symptom'; fields.innerHTML = `<div class="field"><label>Symptom</label><select name="symptom_type" style="width:100%;padding:13px;border:1px solid #dce8e8;border-radius:11px"><option value="heartburn">Heartburn</option><option value="bloating">Bloating</option><option value="pain">Pain</option></select></div><div class="field"><label>Intensity (1–10)</label><input type="number" min="1" max="10" name="intensity" value="5" required></div><div class="field"><label>Time</label><input type="datetime-local" name="time" value="${now}" required></div><div class="field"><label>Notes</label><input name="notes" placeholder="What happened?"></div>`; } else { document.getElementById('logger-title').textContent = 'Log sleep & stress'; fields.innerHTML = `<div class="field"><label>Sleep hours</label><input type="number" step="0.1" min="0" max="24" name="sleep_hours" value="7" required></div><div class="field"><label>Sleep quality (1–5)</label><input type="number" min="1" max="5" name="sleep_quality" value="4" required></div><div class="field"><label>Stress level (1–10)</label><input type="number" min="1" max="10" name="stress_level" value="3" required></div><div class="field"><label>Date</label><input type="date" name="logged_on" value="${new Date().toISOString().slice(0,10)}" required></div><div class="field"><label>Notes</label><input name="notes" placeholder="Optional notes"></div>`; } }
    function closeLogger() { logger.style.display = 'none'; }
    document.getElementById('logger-form').addEventListener('submit', async (event) => { event.preventDefault(); const data = Object.fromEntries(new FormData(event.target).entries()); if (data.time) data.time = new Date(data.time).toISOString(); result.textContent = 'Saving…'; const endpoint = loggerType === 'food' ? 'food-log' : (loggerType === 'symptom' ? 'symptom-log' : 'sleep-log'); try { const response = await fetch(`/api/${endpoint}`, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','Authorization':`Bearer ${window.refluxApiToken}`}, body:JSON.stringify(data) }); const payload = await response.json(); if (!response.ok) throw new Error(payload.message || 'Please check the fields and try again.'); result.textContent = 'Saved. Refreshing your dashboard…'; setTimeout(() => window.location.reload(), 650); } catch (error) { result.textContent = error.message; } });
</script>
@endsection
