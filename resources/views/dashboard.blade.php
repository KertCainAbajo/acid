@extends('layouts.app')

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0];
    $localNow = now(config('app.patient_timezone', config('app.timezone')));
    $hour = (int) $localNow->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $riskLevel = $latestInsight->risk_level;
    $riskTone = ['low' => 'green', 'medium' => 'amber', 'high' => 'red'][$riskLevel] ?? 'red';
    $riskScore = (int) ($latestInsight->risk_score ?? (['low' => 15, 'medium' => 45, 'high' => 75][$riskLevel] ?? 75));
    $details = $latestInsight->details ?? [];
    $headline = $details['headline'] ?? (['low' => 'Your recent pattern looks stable.', 'medium' => 'Some patterns need a closer look.', 'high' => 'Your recent pattern deserves attention.'][$riskLevel] ?? 'Your recent pattern deserves attention.');
    $factors = collect($details['factors'] ?? [])->filter(fn (array $factor): bool => ($factor['points'] ?? 0) > 0)->sortByDesc('points')->take(4)->values();
    $factorMax = max(1, (int) $factors->max('points'));
    $trend = $details['trend'] ?? 'steady';
    $trendCopy = ['improving' => 'Improving', 'worsening' => 'Worsening'][$trend] ?? 'Steady';
    $trendTone = ['improving' => 'green', 'worsening' => 'red'][$trend] ?? 'slate';
    $confidence = ucfirst($details['confidence'] ?? 'low');

    $week = collect($weeklyActivity);
    $weekMax = max(1, $week->max(fn (array $day): int => max($day['foods'], $day['symptoms'])));
    $weekFoodTotal = $week->sum('foods');
    $weekSymptomTotal = $week->sum('symptoms');
    $activeDays = $week->filter(fn (array $day): bool => $day['foods'] + $day['symptoms'] > 0)->count();
    $foodSpark = max(1, $week->max('foods'));
    $symptomSpark = max(1, $week->max('symptoms'));
    $streak = 0;
    foreach ($week->reverse()->values() as $offset => $day) {
        if ($day['foods'] + $day['symptoms'] > 0) {
            $streak++;
        } elseif ($offset > 0) {
            break;
        }
    }

    $sleepToday = $sleep && $sleep->logged_on->isToday();
    $sleepPercent = $sleep ? min(100, (int) round($sleep->sleep_hours / 8 * 100)) : 0;
    $mealGoal = 3;
    $planProgress = (int) round((min($todayFoodCount, $mealGoal) / $mealGoal + ($sleepToday ? 1 : 0) + ($todaySymptomCount > 0 || $todayFoodCount > 0 ? 1 : 0)) / 3 * 100);
    $topTriggerCount = max(1, (int) $triggers->max('count'));
@endphp
<style>
    .db { display:grid; gap:20px; }
    .db-icon { display:grid; place-items:center; flex:none; width:40px; height:40px; border-radius:12px; }
    .db-icon svg { width:19px; height:19px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .db-icon.is-green { color:#047857; background:linear-gradient(145deg,#ecfdf5,#d1fae5); }
    .db-icon.is-orange { color:#c2410c; background:linear-gradient(145deg,#fff7ed,#ffedd5); }
    .db-icon.is-red { color:#b91c1c; background:linear-gradient(145deg,#fef2f2,#fee2e2); }
    .db-icon.is-indigo { color:#4338ca; background:linear-gradient(145deg,#eef2ff,#e0e7ff); }
    .db-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:11.5px; font-weight:700; white-space:nowrap; }
    .db-chip.is-green { color:#065f46; background:#d1fae5; } .db-chip.is-amber { color:#92400e; background:#fef3c7; } .db-chip.is-red { color:#991b1b; background:#fee2e2; } .db-chip.is-slate { color:#475569; background:#f1f5f9; } .db-chip.is-indigo { color:#3730a3; background:#e0e7ff; }
    .db-chip i { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .db-panel { position:relative; padding:22px; border:1px solid #fff; border-radius:22px; background:#ffffffd9; box-shadow:var(--shadow-card); backdrop-filter:blur(10px); transition:transform .25s ease,box-shadow .25s ease; animation:dbRise .5s cubic-bezier(.2,.8,.2,1) both; }
    .db-panel.is-lift:hover { transform:translateY(-3px); box-shadow:0 1px 2px #0f172a0a,0 28px 50px -24px #0a5a4059; }
    .db-panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:18px; }
    .db-panel-head h2 { margin:0; font-size:17px; font-weight:750; letter-spacing:-.02em; }
    .db-panel-head p { margin:3px 0 0; color:var(--muted); font-size:13px; }
    .db-link { display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border:0; border-radius:10px; color:var(--green); background:#ecfdf5; font:inherit; font-size:12.5px; font-weight:700; white-space:nowrap; cursor:pointer; transition:background .2s ease,transform .2s ease; }
    .db-link:hover { background:#d1fae5; transform:translateY(-1px); }
    .db-link svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .db-empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:26px 16px; border:1.5px dashed #d8e3df; border-radius:16px; color:var(--muted); background:#fafcfb; font-size:13px; text-align:center; }
    .db-empty svg { width:34px; height:34px; padding:8px; border-radius:11px; fill:none; stroke:#10b981; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; background:#ecfdf5; }
    .db-empty strong { color:var(--ink); font-size:13.5px; }

    /* Hero */
    .db-hero { position:relative; display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:28px; padding:30px 32px; overflow:hidden; isolation:isolate; border-radius:28px; color:#ecfdf5; background:radial-gradient(90% 120% at 100% 0%,#13775a 0%,transparent 55%),linear-gradient(160deg,#0c4434 0%,#082c24 55%,#05181a 100%); box-shadow:0 30px 60px -30px #0a2e26cc; animation:dbRise .5s cubic-bezier(.2,.8,.2,1) both; }
    .db-hero:before { content:""; position:absolute; inset:0; z-index:-1; background:radial-gradient(#ffffff14 1px,transparent 1.2px) 0 0/22px 22px; mask-image:radial-gradient(ellipse at 25% 30%,#000 0,transparent 70%); }
    .db-hero-glow { position:absolute; z-index:-1; border-radius:50%; filter:blur(60px); animation:dbFloat 14s ease-in-out infinite alternate; }
    .db-hero-glow.one { top:-140px; left:30%; width:340px; height:340px; background:#10b98159; }
    .db-hero-glow.two { bottom:-160px; left:-80px; width:300px; height:300px; background:#0ea5e933; animation-duration:18s; }
    .db-hero-main { display:flex; flex-direction:column; min-width:0; }
    .db-live { display:inline-flex; align-self:flex-start; align-items:center; gap:8px; padding:5px 12px 5px 9px; border:1px solid #6ee7b733; border-radius:999px; color:#a7f3d0; background:#ffffff0f; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .db-live i { width:7px; height:7px; border-radius:50%; background:#34d399; animation:dbPulse 2.2s ease-in-out infinite; }
    .db-hero h1 { margin:16px 0 8px; color:#fff; font-size:clamp(28px,2.8vw,40px); font-weight:800; line-height:1.05; letter-spacing:-.045em; }
    .db-hero h1 span { color:transparent; background:linear-gradient(95deg,#a7f3d0,#34d399 45%,#5eead4); background-size:200% auto; -webkit-background-clip:text; background-clip:text; animation:dbShine 7s linear infinite; }
    .db-hero-lead { max-width:56ch; margin:0; color:#b4d3c8; font-size:14.5px; line-height:1.6; }
    .db-hero-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; max-width:560px; margin-top:22px; }
    .db-hero-stat { padding:12px 14px; border:1px solid #ffffff1c; border-radius:16px; background:#ffffff0d; backdrop-filter:blur(10px); }
    .db-hero-stat span { display:flex; align-items:center; gap:6px; color:#a7f3d0; font-size:11px; font-weight:600; }
    .db-hero-stat span svg { width:13px; height:13px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .db-hero-stat strong { display:block; margin-top:4px; color:#fff; font-size:20px; font-weight:800; letter-spacing:-.03em; }
    .db-hero-stat strong small { color:#9cc5b8; font-size:12px; font-weight:600; letter-spacing:0; }
    .db-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:auto; padding-top:22px; }
    .db-action { display:inline-flex; align-items:center; gap:9px; padding:9px 14px 9px 9px; border:1px solid #ffffff24; border-radius:14px; color:#fff; background:#ffffff12; font-size:13px; font-weight:650; transition:background .2s ease,transform .2s ease,border-color .2s ease; }
    .db-action:hover { border-color:#6ee7b766; background:#ffffff21; transform:translateY(-2px); }
    .db-action:focus-visible { outline:3px solid #6ee7b7; outline-offset:2px; }
    .db-action .db-icon { width:30px; height:30px; border-radius:9px; }
    .db-action .db-icon svg { width:15px; height:15px; }
    .db-action.is-primary { border-color:transparent; color:#064e3b; background:linear-gradient(135deg,#a7f3d0,#34d399); box-shadow:0 12px 24px -12px #34d399; }
    .db-action.is-primary:hover { background:linear-gradient(135deg,#d1fae5,#6ee7b7); }
    .db-action.is-primary .db-icon { color:#fff; background:#047857; }

    /* Today's plan */
    .db-plan { display:flex; flex-direction:column; gap:14px; padding:18px; border:1px solid #ffffff1f; border-radius:22px; background:linear-gradient(160deg,#ffffff17,#ffffff08); box-shadow:inset 0 1px 0 #ffffff1a; backdrop-filter:blur(16px); }
    .db-plan-head { display:flex; align-items:center; gap:14px; }
    .db-plan-ring { position:relative; width:76px; height:76px; flex:none; }
    .db-plan-ring svg { width:76px; height:76px; transform:rotate(-90deg); }
    .db-plan-ring circle { fill:none; stroke-width:8; }
    .db-plan-ring .track { stroke:#ffffff17; } .db-plan-ring .value { stroke:url(#db-plan-gradient); stroke-linecap:round; stroke-dasharray:100; animation:dbDraw 1.3s .3s cubic-bezier(.2,.8,.2,1) both; }
    .db-plan-ring b { position:absolute; inset:0; display:grid; place-items:center; color:#fff; font-size:18px; font-weight:800; }
    .db-plan-head small { display:block; color:#a7f3d0; font-size:10.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .db-plan-head strong { display:block; margin-top:3px; color:#fff; font-size:16px; font-weight:750; }
    .db-plan-head span { display:block; margin-top:2px; color:#9cc5b8; font-size:12px; }
    .db-tasks { display:grid; gap:8px; margin:0; padding:0; list-style:none; }
    .db-task a { display:grid; grid-template-columns:24px minmax(0,1fr) auto; align-items:center; gap:10px; padding:10px 12px; border-radius:13px; color:#ecfdf5; background:#ffffff0d; transition:background .2s ease; }
    .db-task a:hover { background:#ffffff1a; }
    .db-check { width:22px; height:22px; display:grid; place-items:center; border:1.5px solid #ffffff4d; border-radius:50%; }
    .db-check svg { width:12px; height:12px; fill:none; stroke:#fff; stroke-width:3; stroke-linecap:round; stroke-linejoin:round; opacity:0; }
    .db-task.is-done .db-check { border-color:transparent; background:#10b981; } .db-task.is-done .db-check svg { opacity:1; }
    .db-task.is-done strong { color:#9cc5b8; text-decoration:line-through; text-decoration-color:#ffffff4d; }
    .db-task strong { display:block; font-size:13px; font-weight:650; }
    .db-task small { color:#9cc5b8; font-size:11px; }
    .db-task em { color:#a7f3d0; font-size:11.5px; font-style:normal; font-weight:700; }

    /* KPI cards */
    .db-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
    .db-kpi { display:flex; flex-direction:column; gap:12px; padding:20px; overflow:hidden; }
    .db-kpi:before { content:""; position:absolute; top:0; left:20px; right:20px; height:3px; border-radius:0 0 4px 4px; background:var(--accent,#10b981); }
    .db-kpis .db-kpi:nth-child(2) { animation-delay:.05s; } .db-kpis .db-kpi:nth-child(3) { animation-delay:.1s; } .db-kpis .db-kpi:nth-child(4) { animation-delay:.15s; }
    .db-kpi.is-green { --accent:linear-gradient(90deg,#34d399,#059669); } .db-kpi.is-amber { --accent:linear-gradient(90deg,#fbbf24,#d97706); } .db-kpi.is-red { --accent:linear-gradient(90deg,#f87171,#dc2626); } .db-kpi.is-orange { --accent:linear-gradient(90deg,#fdba74,#ea580c); } .db-kpi.is-indigo { --accent:linear-gradient(90deg,#a5b4fc,#4f46e5); }
    .db-kpi-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .db-kpi-label { color:#475569; font-size:13px; font-weight:650; }
    .db-kpi-row { display:flex; align-items:flex-end; justify-content:space-between; gap:12px; }
    .db-kpi-value { display:flex; align-items:baseline; gap:6px; font-size:30px; font-weight:800; line-height:1; letter-spacing:-.045em; }
    .db-kpi-value small { color:var(--muted); font-size:13px; font-weight:500; letter-spacing:0; }
    .db-kpi-value.is-green { color:var(--green); } .db-kpi-value.is-amber { color:#b45309; } .db-kpi-value.is-red { color:#b91c1c; }
    .db-kpi p { margin:0; color:var(--muted); font-size:12.5px; line-height:1.5; }
    .db-kpi-foot { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:auto; padding-top:12px; border-top:1px solid #eef2f1; }
    .db-gauge { position:relative; width:78px; height:44px; flex:none; }
    .db-gauge svg { width:78px; height:44px; overflow:visible; }
    .db-gauge path { fill:none; stroke-width:8; stroke-linecap:round; }
    .db-gauge .track { stroke:#eef2f1; }
    .db-gauge .value { stroke:url(#db-risk-gradient); stroke-dasharray:100; animation:dbDraw 1.2s .3s cubic-bezier(.2,.8,.2,1) both; }
    .db-gauge b { position:absolute; left:0; right:0; bottom:-2px; color:var(--ink); font-size:12px; font-weight:800; text-align:center; }
    .db-spark { display:flex; align-items:flex-end; gap:3px; height:36px; flex:none; }
    .db-spark i { width:7px; min-height:3px; border-radius:3px; background:#e2e8f0; transform-origin:bottom; animation:dbGrow .8s cubic-bezier(.2,.8,.2,1) both; }
    .db-spark.is-orange i.is-on { background:linear-gradient(to top,#ea580c,#fdba74); }
    .db-spark.is-red i.is-on { background:linear-gradient(to top,#dc2626,#f87171); }
    .db-spark i.is-today { outline:2px solid #fff; box-shadow:0 0 0 3px #e2e8f0; }
    .db-ring { position:relative; width:52px; height:52px; flex:none; }
    .db-ring svg { width:52px; height:52px; transform:rotate(-90deg); }
    .db-ring circle { fill:none; stroke-width:6; }
    .db-ring .track { stroke:#eef2ff; } .db-ring .value { stroke:#6366f1; stroke-linecap:round; stroke-dasharray:100; animation:dbDraw 1.2s .3s cubic-bezier(.2,.8,.2,1) both; }
    .db-ring b { position:absolute; inset:0; display:grid; place-items:center; color:#3730a3; font-size:11px; font-weight:800; }

    /* Main grid */
    .db-grid { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1fr); gap:20px; }
    .db-insight { overflow:hidden; isolation:isolate; }
    .db-insight:before { content:""; position:absolute; inset:0; z-index:-1; padding:1.5px; border-radius:inherit; background:linear-gradient(135deg,#34d399,#a7f3d0 40%,#e0f2fe 70%,#c7d2fe); -webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0); mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0); -webkit-mask-composite:xor; mask-composite:exclude; }
    .db-insight:after { content:""; position:absolute; top:-90px; right:-90px; z-index:-1; width:240px; height:240px; border-radius:50%; background:radial-gradient(circle,#d1fae5,transparent 70%); }
    .db-insight-tag { display:inline-flex; align-items:center; gap:8px; color:var(--green); font-size:11.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .db-insight-tag b { width:28px; height:28px; display:grid; place-items:center; border-radius:9px; color:#fff; background:conic-gradient(from 200deg,#10b981,#14b8a6,#34d399,#10b981); }
    .db-insight-tag svg { width:15px; height:15px; fill:currentColor; }
    .db-insight-meta { display:flex; flex-wrap:wrap; align-items:center; gap:6px; }
    .db-insight h2 { margin:16px 0 10px; font-size:23px; font-weight:800; letter-spacing:-.035em; line-height:1.2; }
    .db-insight-text { margin:0; color:#475569; font-size:14px; line-height:1.7; }
    .db-factors { display:grid; gap:10px; margin-top:18px; padding:16px; border:1px solid #eef2f1; border-radius:16px; background:#fafcfb; }
    .db-factors h3 { margin:0 0 2px; color:#334155; font-size:12px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .db-factor { display:grid; grid-template-columns:minmax(0,1fr) 120px; align-items:center; gap:14px; }
    .db-factor strong { display:block; font-size:13px; }
    .db-factor small { display:block; color:var(--muted); font-size:11.5px; }
    .db-factor-bar { height:7px; overflow:hidden; border-radius:99px; background:#eef2f1; }
    .db-factor-bar i { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#fbbf24,#f97316,#dc2626); transform-origin:left; animation:dbGrowX .9s .2s cubic-bezier(.2,.8,.2,1) both; }
    .db-insight-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-top:18px; padding-top:16px; border-top:1px solid #eef2f1; }
    .db-disclaimer { display:flex; align-items:center; gap:7px; margin:0; color:#94a3b8; font-size:11.5px; }
    .db-disclaimer svg { width:13px; height:13px; flex:none; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }
    .db-insight-actions { display:flex; gap:8px; }
    .db-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border:0; border-radius:12px; color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); box-shadow:inset 0 1px 0 #ffffff33,0 12px 22px -10px #0a5a40cc; font:inherit; font-size:13px; font-weight:700; white-space:nowrap; cursor:pointer; transition:transform .2s ease,box-shadow .2s ease; }
    .db-btn:hover { transform:translateY(-2px); box-shadow:inset 0 1px 0 #ffffff33,0 16px 28px -10px #0a5a40e6; }
    .db-btn svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; transition:transform .2s ease; }
    .db-btn:hover svg { transform:translateX(3px); }
    .db-refresh svg { transition:transform .5s ease; } .db-refresh:hover svg { transform:rotate(180deg); }

    /* Weekly chart */
    .db-legend { display:flex; gap:12px; color:var(--muted); font-size:12px; }
    .db-legend span { display:inline-flex; align-items:center; gap:6px; }
    .db-legend i { width:10px; height:10px; border-radius:3px; }
    .db-legend .is-food { background:#10b981; } .db-legend .is-symptom { background:#f87171; }
    .db-chart { position:relative; display:grid; grid-template-columns:repeat(7,1fr); gap:8px; height:180px; padding:0 4px; background:repeating-linear-gradient(to top,#eef2f1 0 1px,transparent 1px 45px); border-bottom:1px solid #e2e8f0; }
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
    .db-stat strong small { color:var(--muted); font-size:12px; font-weight:600; }

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
    .db-tip-card { display:flex; gap:12px; margin-top:14px; padding:14px; border-radius:16px; color:#1e3a8a; background:linear-gradient(120deg,#eef2ff,#f0fdf4); }
    .db-tip-card strong { display:block; color:#1e293b; font-size:13px; }
    .db-tip-card p { margin:2px 0 0; color:#475569; font-size:12.5px; line-height:1.5; }

    @keyframes dbRise { from { opacity:0; transform:translateY(10px); } }
    @keyframes dbFloat { to { transform:translate(-40px,30px) scale(1.15); } }
    @keyframes dbPulse { 0%,100% { box-shadow:0 0 0 3px #34d39940; } 50% { box-shadow:0 0 0 7px #34d39910; } }
    @keyframes dbShine { to { background-position:200% center; } }
    @keyframes dbDraw { from { stroke-dashoffset:100; } }
    @keyframes dbGrow { from { transform:scaleY(0); } }
    @keyframes dbGrowX { from { transform:scaleX(0); } }
    @media (prefers-reduced-motion:reduce) { .db *, .db { animation:none !important; } }
    @media (max-width:1280px) { .db-hero { grid-template-columns:1fr; } .db-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:1000px) { .db-grid { grid-template-columns:minmax(0,1fr); } }
    @media (max-width:700px) { .db-hero { padding:24px 20px; } .db-hero-stats { grid-template-columns:1fr 1fr 1fr; } .db-hero-stat { padding:10px; } .db-hero-stat strong { font-size:17px; } .db-kpis { grid-template-columns:minmax(0,1fr); } .db-panel { padding:18px; } .db-factor { grid-template-columns:1fr; gap:6px; } .db-stats { grid-template-columns:1fr 1fr 1fr; } }
</style>

<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
    <linearGradient id="db-risk-gradient" x1="0" x2="1"><stop offset="0" stop-color="#34d399"/><stop offset=".55" stop-color="#f59e0b"/><stop offset="1" stop-color="#dc2626"/></linearGradient>
    <linearGradient id="db-plan-gradient" x1="0" x2="1"><stop offset="0" stop-color="#a7f3d0"/><stop offset="1" stop-color="#34d399"/></linearGradient>
</defs></svg>

<div class="shell">
    @include('partials.topbar')
    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'dashboard'])
        <main class="main db">
            <section class="db-hero">
                <span class="db-hero-glow one" aria-hidden="true"></span><span class="db-hero-glow two" aria-hidden="true"></span>
                <div class="db-hero-main">
                    <span class="db-live"><i></i> {{ $localNow->format('l, M j') }} · Live health overview</span>
                    <h1><span data-greeting>{{ $greeting }}</span>, {{ $firstName }}</h1>
                    <p class="db-hero-lead">{{ $headline }}</p>

                    <div class="db-hero-stats">
                        <div class="db-hero-stat"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/></svg>Reflux risk</span><strong>{{ ucfirst($riskLevel) }} <small>{{ $riskScore }}/100</small></strong></div>
                        <div class="db-hero-stat"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c1.6 2.2 5 4.5 5 8.4A5 5 0 0 1 7 11.4C7 8.1 9.6 6 12 3Z"/></svg>Logging streak</span><strong>{{ $streak }} <small>day{{ $streak === 1 ? '' : 's' }}</small></strong></div>
                        <div class="db-hero-stat"><span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18"/></svg>Active this week</span><strong>{{ $activeDays }}<small>/7 days</small></strong></div>
                    </div>

                    <nav class="db-actions" aria-label="Quick check-in actions">
                        <a class="db-action is-primary" href="{{ route('food.log') }}"><span class="db-icon"><svg viewBox="0 0 24 24"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg></span>Log food</a>
                        <a class="db-action" href="{{ route('symptom.log') }}"><span class="db-icon is-red"><svg viewBox="0 0 24 24"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg></span>Log reflux</a>
                        <a class="db-action" href="{{ route('sleep.log') }}"><span class="db-icon is-indigo"><svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg></span>Sleep &amp; stress</a>
                        <a class="db-action" href="{{ route('insights') }}"><span class="db-icon is-green"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span>Insights</a>
                    </nav>
                </div>

                <aside class="db-plan" aria-label="Today's plan">
                    <div class="db-plan-head">
                        <div class="db-plan-ring" role="img" aria-label="Today's plan {{ $planProgress }} percent complete">
                            <svg viewBox="0 0 76 76"><circle class="track" cx="38" cy="38" r="31" pathLength="100"/><circle class="value" cx="38" cy="38" r="31" pathLength="100" @style(['stroke-dashoffset: '.(100 - max(2, $planProgress))])/></svg>
                            <b>{{ $planProgress }}%</b>
                        </div>
                        <div>
                            <small>Today's plan</small>
                            <strong>{{ $planProgress >= 100 ? 'All done for today' : ($planProgress > 0 ? 'Keep it going' : 'Let\'s get started') }}</strong>
                            <span>Small daily check-ins make patterns clearer.</span>
                        </div>
                    </div>
                    <ul class="db-tasks">
                        <li class="db-task {{ $todayFoodCount >= $mealGoal ? 'is-done' : '' }}"><a href="{{ route('food.log') }}"><span class="db-check"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span><span><strong>Log your meals</strong><small>Breakfast, lunch and dinner</small></span><em>{{ min($todayFoodCount, $mealGoal) }}/{{ $mealGoal }}</em></a></li>
                        <li class="db-task {{ $todayFoodCount + $todaySymptomCount > 0 ? 'is-done' : '' }}"><a href="{{ route('symptom.log') }}"><span class="db-check"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span><span><strong>Check in on symptoms</strong><small>{{ $todaySymptomCount ? $todaySymptomCount.' logged today' : 'Log reflux if you feel any' }}</small></span><em>{{ $todaySymptomCount ?: '—' }}</em></a></li>
                        <li class="db-task {{ $sleepToday ? 'is-done' : '' }}"><a href="{{ route('sleep.log') }}"><span class="db-check"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span><span><strong>Sleep &amp; stress check-in</strong><small>{{ $sleepToday ? number_format($sleep->sleep_hours, 1).' h sleep logged' : 'Takes 10 seconds' }}</small></span><em>{{ $sleepToday ? '1/1' : '0/1' }}</em></a></li>
                    </ul>
                </aside>
            </section>

            <section class="db-kpis">
                <article class="db-panel db-kpi is-lift is-{{ $riskTone }}">
                    <div class="db-kpi-head"><span class="db-kpi-label">Acid-Risk Index</span><span class="db-icon is-green"><svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/></svg></span></div>
                    <div class="db-kpi-row">
                        <div class="db-kpi-value is-{{ $riskTone }}">{{ ucfirst($riskLevel) }} <small>risk</small></div>
                        <span class="db-gauge" aria-hidden="true"><svg viewBox="0 0 72 40"><path class="track" d="M6 36a30 30 0 0 1 60 0" pathLength="100"/><path class="value" d="M6 36a30 30 0 0 1 60 0" pathLength="100" @style(['stroke-dashoffset: '.(100 - max(4, min(100, $riskScore)))])/></svg><b>{{ $riskScore }}</b></span>
                    </div>
                    <p>Scored from your symptoms, food, sleep and stress patterns.</p>
                    <div class="db-kpi-foot"><span class="db-chip is-{{ $trendTone }}"><i></i> {{ $trendCopy }} trend</span><a class="db-link" href="{{ route('insights') }}">Details</a></div>
                </article>

                <article class="db-panel db-kpi is-lift is-orange">
                    <div class="db-kpi-head"><span class="db-kpi-label">Today's Intake</span><span class="db-icon is-orange"><svg viewBox="0 0 24 24"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg></span></div>
                    <div class="db-kpi-row">
                        <div class="db-kpi-value">{{ $todayFoodCount }} <small>meal{{ $todayFoodCount === 1 ? '' : 's' }}</small></div>
                        <span class="db-spark is-orange" aria-hidden="true">@foreach ($weeklyActivity as $index => $day)<i class="{{ $day['foods'] ? 'is-on' : '' }} {{ $day['isToday'] ? 'is-today' : '' }}" @style(['height: '.max(8, $day['foods'] / $foodSpark * 100).'%', 'animation-delay: '.($index * 0.04).'s'])></i>@endforeach</span>
                    </div>
                    <p>{{ $weekFoodTotal }} food entr{{ $weekFoodTotal === 1 ? 'y' : 'ies' }} this week. Consistent logging sharpens trigger matching.</p>
                    <div class="db-kpi-foot"><span class="db-chip {{ $todayFoodCount ? 'is-green' : 'is-slate' }}"><i></i> {{ $todayFoodCount ? 'Logging started' : 'No meals yet' }}</span><a class="db-link" href="{{ route('food.log') }}">Add</a></div>
                </article>

                <article class="db-panel db-kpi is-lift {{ $todaySymptomCount ? 'is-red' : 'is-green' }}">
                    <div class="db-kpi-head"><span class="db-kpi-label">Symptom Activity</span><span class="db-icon is-red"><svg viewBox="0 0 24 24"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg></span></div>
                    <div class="db-kpi-row">
                        <div class="db-kpi-value {{ $todaySymptomCount ? 'is-red' : 'is-green' }}">{{ $todaySymptomCount ?: 'Calm' }} <small>{{ $todaySymptomCount ? 'today' : 'day so far' }}</small></div>
                        <span class="db-spark is-red" aria-hidden="true">@foreach ($weeklyActivity as $index => $day)<i class="{{ $day['symptoms'] ? 'is-on' : '' }} {{ $day['isToday'] ? 'is-today' : '' }}" @style(['height: '.max(8, $day['symptoms'] / $symptomSpark * 100).'%', 'animation-delay: '.($index * 0.04).'s'])></i>@endforeach</span>
                    </div>
                    <p>{{ $weekSymptomTotal }} symptom{{ $weekSymptomTotal === 1 ? '' : 's' }} this week · average intensity {{ number_format($weeklyAverageIntensity, 1) }}/10.</p>
                    <div class="db-kpi-foot"><span class="db-chip {{ $todaySymptomCount ? 'is-amber' : 'is-green' }}"><i></i> {{ $todaySymptomCount ? 'Monitor your pattern' : 'No acute reflux' }}</span><a class="db-link" href="{{ route('symptom.log') }}">Log</a></div>
                </article>

                <article class="db-panel db-kpi is-lift is-indigo">
                    <div class="db-kpi-head"><span class="db-kpi-label">Sleep &amp; Stress</span><span class="db-icon is-indigo"><svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg></span></div>
                    <div class="db-kpi-row">
                        <div class="db-kpi-value">{{ $sleep ? number_format($sleep->sleep_hours, 1) : '—' }} <small>{{ $sleep ? 'hrs sleep' : 'no data' }}</small></div>
                        <span class="db-ring" aria-hidden="true"><svg viewBox="0 0 52 52"><circle class="track" cx="26" cy="26" r="21" pathLength="100"/><circle class="value" cx="26" cy="26" r="21" pathLength="100" @style(['stroke-dashoffset: '.(100 - $sleepPercent)])/></svg><b>{{ $sleepPercent }}%</b></span>
                    </div>
                    <p>{{ $sleep ? 'Stress '.$sleep->stress_level.'/10 · Quality '.$sleep->sleep_quality.'/5 · '.$sleep->logged_on->format('M j') : 'Sleep data improves your risk estimate.' }}</p>
                    <div class="db-kpi-foot"><span class="db-chip {{ $sleepToday ? 'is-green' : 'is-slate' }}"><i></i> {{ $sleepToday ? 'Checked in today' : ($sleep ? 'Last: '.$sleep->logged_on->diffForHumans() : 'Not logged yet') }}</span><a class="db-link" href="{{ route('sleep.log') }}">{{ $sleepToday ? 'Update' : 'Add' }}</a></div>
                </article>
            </section>

            <div class="db-grid">
                <section class="db-panel db-insight" id="insights">
                    <div class="db-panel-head" style="margin-bottom:0">
                        <span class="db-insight-tag"><b><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9zM19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z"/></svg></b> AI Pattern Insight</span>
                        <div class="db-insight-meta">
                            <span class="db-chip is-{{ $riskTone }}"><i></i> {{ ucfirst($riskLevel) }} risk</span>
                            <span class="db-chip is-indigo"><i></i> {{ $confidence }} confidence</span>
                        </div>
                    </div>
                    <h2>{{ $headline }}</h2>
                    <p class="db-insight-text">{{ $latestInsight->insight_text }}</p>

                    @if ($factors->isNotEmpty())
                        <div class="db-factors">
                            <h3>What is driving your score</h3>
                            @foreach ($factors as $factor)
                                <div class="db-factor">
                                    <div><strong>{{ $factor['label'] }}</strong><small>{{ $factor['detail'] }}</small></div>
                                    <div class="db-factor-bar"><i @style(['width: '.($factor['points'] / $factorMax * 100).'%'])></i></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="db-factors">
                            <h3>What is driving your score</h3>
                            <div class="db-factor">
                                <div><strong>No risk factors detected</strong><small>Nothing in your recent logs is raising your score. Keep logging to keep this estimate accurate.</small></div>
                                <span class="db-chip is-green"><i></i> All clear</span>
                            </div>
                        </div>
                    @endif

                    <div class="db-insight-foot">
                        <p class="db-disclaimer"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg> Updated {{ $latestInsight->generated_at->diffForHumans() }} · a tracking aid, not a diagnosis</p>
                        <div class="db-insight-actions">
                            <form method="POST" action="{{ route('insights.refresh') }}">
                                @csrf
                                <button class="db-link db-refresh" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/></svg> Refresh</button>
                            </form>
                            <a class="db-btn" href="{{ route('insights') }}">Full analysis <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                        </div>
                    </div>
                </section>

                <section class="db-panel is-lift">
                    <div class="db-panel-head">
                        <div><h2>Weekly correlation</h2><p>Symptoms vs. food logging · last 7 days</p></div>
                        <div class="db-legend"><span><i class="is-food"></i> Food</span><span><i class="is-symptom"></i> Symptoms</span></div>
                    </div>
                    <div class="db-chart" role="img" aria-label="Food and symptom logs for the last 7 days">
                        @foreach ($weeklyActivity as $index => $day)
                            <div class="db-day {{ $day['isToday'] ? 'is-today' : '' }}">
                                <i class="db-bar is-food {{ $day['foods'] ? '' : 'is-zero' }}" @style(['height: '.($day['foods'] / $weekMax * 100).'%', 'animation-delay: '.($index * 0.05).'s'])></i>
                                <i class="db-bar is-symptom {{ $day['symptoms'] ? '' : 'is-zero' }}" @style(['height: '.($day['symptoms'] / $weekMax * 100).'%', 'animation-delay: '.($index * 0.05 + 0.03).'s'])></i>
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
                        <div class="db-stat"><span>Avg. severity</span><strong>{{ number_format($weeklyAverageIntensity, 1) }}<small>/10</small></strong></div>
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
                                <div><strong>{{ $trigger['food'] }}</strong><div class="db-trigger-bar"><i @style(['width: '.($trigger['count'] / $topTriggerCount * 100).'%'])></i></div></div>
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
                    <div class="db-tip-card">
                        <span class="db-icon is-indigo"><svg viewBox="0 0 24 24"><path d="M9 18h6M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2Z"/></svg></span>
                        <div><strong>Tip</strong><p>Log symptoms within a few hours of eating. Foods eaten up to {{ \App\Services\PatternAnalysisService::MEAL_WINDOW_HOURS }} hours before a symptom are checked as possible triggers.</p></div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
<script>
    (() => {
        const hour = new Date().getHours();
        const greeting = document.querySelector('[data-greeting]');
        if (greeting) { greeting.textContent = hour < 12 ? 'Good morning' : (hour < 18 ? 'Good afternoon' : 'Good evening'); }
    })();
</script>
@endsection
