@extends('layouts.app')

@section('content')
<style>
    .section-main { padding:0; background:radial-gradient(circle at 15% 5%,#e2faef 0,transparent 34%),linear-gradient(135deg,#f1fbf7,#f8fbfc 58%,#fff); }
    .section-page { min-height:calc(100vh - 74px); padding:52px clamp(24px,4vw,68px) 70px; scroll-margin-top:92px; }
    .section-wrap { width:100%; min-width:0; max-width:1420px; margin:0 auto; }
    .section-hero { position:relative; isolation:isolate; overflow:hidden; display:flex; align-items:center; justify-content:space-between; gap:28px; padding:30px 34px; border:1px solid #d4ece2; border-radius:28px; background:linear-gradient(115deg,#fff,#effcf6 66%,#dff8ec); box-shadow:0 22px 55px #17684f12; }
    .section-hero:after { content:""; position:absolute; z-index:-1; right:-58px; top:-80px; width:220px; height:220px; border:30px solid #bcefd9; border-radius:50%; opacity:.55; }
    .section-hero > * { position:relative; z-index:1; }
    .section-eyebrow { display:flex; align-items:center; gap:8px; color:#15805d; font-size:11px; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
    .section-eyebrow i { width:8px; height:8px; border-radius:50%; background:#2bc68a; box-shadow:0 0 0 5px #2bc68a1c; }
    .section-hero h1 { margin:12px 0 8px; color:#102c35; font-size:clamp(31px,3.6vw,52px); line-height:1.08; letter-spacing:-.06em; }
    .section-hero p { max-width:650px; margin:0; color:#60787e; font-size:14px; line-height:1.6; }
    .section-hero-stat { min-width:178px; padding:18px; border:1px solid #c9eadb; border-radius:21px; background:#fff; box-shadow:0 12px 25px #17684f12; text-align:center; }
    .section-hero-stat small { display:block; color:#6e8988; font-size:10px; font-weight:750; text-transform:uppercase; letter-spacing:.06em; }
    .section-hero-stat strong { display:block; margin-top:4px; color:#17684f; font-size:24px; letter-spacing:-.05em; }
    .section-icon { display:inline-grid; place-items:center; width:45px; height:45px; margin-bottom:7px; border-radius:16px; color:#17684f; background:#d9f7e9; font-size:22px; }
    .section-metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin:20px 0; }
    .section-metric-card, .section-card { border:1px solid #dcece7; border-radius:22px; background:#fff; box-shadow:0 15px 35px #174b3810; transition:transform .24s ease,box-shadow .24s ease,border-color .24s ease; }
    .section-metric-card:hover, .section-card:hover { transform:translateY(-4px); border-color:#bce3d4; box-shadow:0 24px 48px #174b3818; }
    .section-metric-card { position:relative; overflow:hidden; padding:20px; }
    .section-metric-card:after { content:""; position:absolute; right:-30px; bottom:-36px; width:100px; height:100px; border-radius:50%; background:#e5f9f0; }
    .section-metric-card small { color:#718789; font-size:11px; font-weight:750; }
    .section-metric-card strong { display:block; margin-top:8px; color:#173e47; font-size:28px; letter-spacing:-.06em; }
    .section-metric-card p { margin:3px 0 0; color:#799095; font-size:11px; }
    .section-content-grid { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(300px,.75fr); gap:20px; align-items:start; }
    .section-hero + .section-content-grid { margin-top:22px; }
    .section-card { padding:26px; }
    .section-card-header { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; margin-bottom:19px; }
    .section-card h2 { margin:0 0 5px; color:#173e47; font-size:22px; letter-spacing:-.04em; }
    .section-card-header p, .section-card > p { margin:0; color:#718789; font-size:12px; line-height:1.55; }
    .risk-pill, .section-chip { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; color:#17684f; background:#dff8ec; font-size:10px; font-weight:800; }
    .risk-pill.medium { color:#9a6500; background:#fff1cc; }
    .risk-pill.high { color:#a32c2c; background:#ffe2df; }
    .featured-insight { position:relative; overflow:hidden; border:1px solid #bfe7d6; border-left:5px solid #197c5d; border-radius:24px; padding:27px; background:linear-gradient(135deg,#fff,#f1fcf6); box-shadow:0 18px 38px #17684f12; transition:transform .24s ease,box-shadow .24s ease; }
    .featured-insight:hover { transform:translateY(-4px); box-shadow:0 25px 48px #17684f1a; }
    .featured-insight:after { content:""; position:absolute; right:-55px; bottom:-80px; width:220px; height:220px; border:28px solid #dff8ec; border-radius:50%; opacity:.7; }
    .featured-insight > * { position:relative; z-index:1; }
    .featured-insight h2 { max-width:690px; margin:18px 0 10px; font-size:25px; }
    .featured-insight .insight-text { max-width:760px; margin:0; color:#58727a; font-size:14px; line-height:1.75; }
    .section-note { margin-top:20px; padding:14px 16px; border-radius:16px; color:#567177; background:#eef5ff; font-size:12px; }
    .history-list, .choice-list, .trigger-list-large { display:grid; gap:10px; }
    .insight-history-row, .history-row, .choice-row, .trigger-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px; border:1px solid #e0eeea; border-radius:16px; background:#fbfefd; transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease; }
    .insight-history-row:hover, .history-row:hover, .choice-row:hover, .trigger-row:hover { transform:translateX(3px); border-color:#bfe4d5; box-shadow:0 10px 22px #174b3810; }
    .insight-history-row strong, .history-row strong, .choice-row strong, .trigger-row strong { display:block; color:#173e47; font-size:13px; }
    .insight-history-row small, .history-row small, .choice-row small, .trigger-row small { display:block; margin-top:3px; color:#75898d; font-size:10px; }
    .choice-row.safe { background:#f0fbf5; border-color:#d6f0e2; }
    .choice-row.avoid { background:#fff8f0; border-color:#f2e2c9; }
    .choice-mark { display:grid; place-items:center; flex:none; width:31px; height:31px; border-radius:11px; color:#17684f; background:#d5f4e3; font-weight:900; }
    .choice-row.avoid .choice-mark { color:#ad6c08; background:#ffedc9; }
    .choice-copy { display:flex; align-items:center; gap:10px; flex:1; }
    .trigger-row { background:#fff7f5; border-color:#f4ded9; }
    .trigger-score { flex:none; padding:5px 8px; border-radius:999px; color:#a2382b; background:#ffe1dc; font-size:10px; font-weight:800; }
    .empty-state { padding:24px; border:1px dashed #cfe4dc; border-radius:17px; color:#718789; background:#fbfffd; text-align:center; font-size:12px; }
    .profile-card { display:flex; align-items:center; gap:15px; margin-bottom:20px; padding:18px; border-radius:18px; background:linear-gradient(135deg,#edf9f3,#f9fffc); }
    .profile-avatar { display:grid; place-items:center; flex:none; width:55px; height:55px; border-radius:18px; color:#fff; background:linear-gradient(135deg,#17684f,#38a47a); font-size:22px; font-weight:800; }
    .profile-card strong { display:block; color:#173e47; font-size:16px; }
    .profile-card small { display:block; margin-top:3px; color:#718789; font-size:11px; }
    .history-row { justify-content:flex-start; align-items:flex-start; }
    .history-dot { flex:none; width:10px; height:10px; margin-top:4px; border-radius:50%; background:#26a875; box-shadow:0 0 0 5px #26a8751b; }
    .history-dot.alert { background:#d95546; box-shadow:0 0 0 5px #d955461b; }
    .history-dot.calm { background:#4d83bf; box-shadow:0 0 0 5px #4d83bf1b; }
    .history-row time { margin-left:auto; flex:none; color:#829597; font-size:10px; }
    .disclaimer { margin-top:20px; color:#829597; font-size:10px; line-height:1.5; }
    @media (max-width:900px) { .section-hero { align-items:flex-start; flex-direction:column; } .section-hero-stat { width:100%; text-align:left; } .section-content-grid { grid-template-columns:1fr; } }
    @media (max-width:650px) { .section-page { padding:30px 15px 40px; } .section-hero, .section-card { padding:21px; border-radius:20px; } .section-hero h1 { font-size:35px; overflow-wrap:anywhere; } .section-hero p { font-size:13px; } .section-card-header { flex-direction:column; gap:10px; } .section-metrics { grid-template-columns:1fr; } .section-metric-card strong { font-size:24px; } }
</style>

<div class="shell">
    <header class="topbar">
        <a class="brand" href="{{ route('dashboard') }}"><img class="brand-logo" src="{{ asset('images/logo1.png') }}" alt="RefluxCare logo"><span>RefluxCare</span></a>
        <div class="top-actions"><span class="pill active">Patient Portal</span><a class="pill" href="{{ route('logs.search') }}">⌕ Search your logs</a><span class="pill">Today, {{ now()->format('M d') }}</span><span class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="pill" style="cursor:pointer">Log out</button></form></div>
    </header>

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => $section])
        <main class="main section-main">
            <div class="section-page">
                <div class="section-wrap">
                    <section class="section-hero">
                        <div><span class="section-icon">{{ $section === 'insights' ? '✦' : ($section === 'recommendations' ? '♧' : '▣') }}</span><div class="section-eyebrow"><i></i> Patient workspace</div><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
                        <div class="section-hero-stat"><small>{{ $section === 'insights' ? 'Current pattern' : ($section === 'recommendations' ? 'Personal triggers' : 'Records saved') }}</small><strong>{{ $section === 'insights' ? ucfirst($latestInsight->risk_level) : ($section === 'recommendations' ? $triggers->count() : $timeline->count()) }}</strong></div>
                    </section>

                    @if($section === 'insights')
                        <section class="section-metrics"><div class="section-metric-card"><small>Food entries · last 7 days</small><strong>{{ $foodCount }}</strong><p>Meals available for comparison</p></div><div class="section-metric-card"><small>Symptoms · last 7 days</small><strong>{{ $symptomCount }}</strong><p>Episodes recorded by you</p></div><div class="section-metric-card"><small>Sleep entries · last 7 days</small><strong>{{ $sleepCount }}</strong><p>Rest and stress signals</p></div></section>
                        <div class="section-content-grid"><section class="featured-insight"><span class="risk-pill {{ $latestInsight->risk_level }}">{{ ucfirst($latestInsight->risk_level) }} risk pattern</span><h2>Your latest pattern summary</h2><p class="insight-text">{{ $latestInsight->insight_text }}</p><div class="section-note">This is a tracking aid, not a medical diagnosis. Use it to prepare questions for your healthcare professional.</div></section><section class="section-card"><div class="section-card-header"><div><h2>Insight history</h2><p>Your previous generated summaries</p></div></div><div class="history-list">@forelse($insightHistory as $insight)<div class="insight-history-row"><div><strong>{{ ucfirst($insight->risk_level) }} pattern</strong><small>{{ $insight->generated_at->format('M d, Y · g:i A') }}</small></div><span class="section-chip">Review</span></div>@empty<div class="empty-state">Your summaries will appear after you record a few entries.</div>@endforelse</div></section></div>
                    @elseif($section === 'recommendations')
                        <div class="section-content-grid"><section class="section-card"><div class="section-card-header"><div><h2>Foods to explore</h2><p>General categories from your health workspace.</p></div><span class="section-chip">Personalized over time</span></div><div class="choice-list">@forelse($safeFoods as $food)<div class="choice-row safe"><div class="choice-copy"><span class="choice-mark">✓</span><div><strong>{{ $food->name }}</strong><small>Often included in a calmer meal plan</small></div></div></div>@empty<div class="empty-state">Safe food suggestions will appear here.</div>@endforelse</div><p class="disclaimer">Recommendations are general tracking suggestions. Your own symptoms and clinician’s advice should guide personal decisions.</p></section><div class="choice-list"><section class="section-card"><div class="section-card-header"><div><h2>Foods to review</h2><p>Categories commonly worth watching.</p></div></div><div class="choice-list">@forelse($avoidFoods as $food)<div class="choice-row avoid"><div class="choice-copy"><span class="choice-mark">!</span><div><strong>{{ $food->name }}</strong><small>Review your response after eating</small></div></div></div>@empty<div class="empty-state">No review categories are set yet.</div>@endforelse</div></section><section class="section-card"><div class="section-card-header"><div><h2>Your possible triggers</h2><p>Based on food and symptom timing.</p></div></div><div class="trigger-list-large">@forelse($triggers as $trigger)<div class="trigger-row"><div><strong>{{ $trigger['food'] }}</strong><small>{{ $trigger['count'] }} related symptom{{ $trigger['count'] === 1 ? '' : 's' }}</small></div><span class="trigger-score">Review</span></div>@empty<div class="empty-state">Log meals and symptoms to discover possible patterns.</div>@endforelse</div></section></div></div>
                    @else
                        <div class="section-content-grid"><section class="section-card"><div class="section-card-header"><div><h2>Your health timeline</h2><p>Food, symptoms, and sleep records in one view.</p></div><span class="section-chip">{{ $timeline->count() }} records</span></div><div class="history-list">@forelse($timeline as $event)<div class="history-row"><span class="history-dot {{ $event['tone'] }}"></span><div><strong>{{ $event['kind'] }} · {{ $event['title'] }}</strong><small>{{ $event['detail'] }}</small></div><time>{{ $event['at']->format('M d, Y') }}</time></div>@empty<div class="empty-state">Your timeline will appear here as you log meals, symptoms, and sleep.</div>@endforelse</div></section><section><div class="section-card profile-card"><span class="profile-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small><small>Patient account · Active</small></div></div><div class="section-metrics" style="grid-template-columns:1fr; margin:0"><div class="section-metric-card"><small>Total food entries</small><strong>{{ $foodCount }}</strong><p>Meals saved in your account</p></div><div class="section-metric-card"><small>Total symptom entries</small><strong>{{ $symptomCount }}</strong><p>Episodes you recorded</p></div><div class="section-metric-card"><small>Total sleep entries</small><strong>{{ $sleepCount }}</strong><p>Sleep and stress records</p></div></div></section></div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
