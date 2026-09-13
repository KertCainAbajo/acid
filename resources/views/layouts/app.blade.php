<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'RefluxCare' }} · Clinical platform</title>
    <style>
        :root { --ink:#102c35; --muted:#6c8085; --green:#17684f; --green-2:#2b7c60; --mint:#ddf8ee; --blue:#eef3ff; --line:#e4ecec; --bg:#f5f8fb; --red:#c93732; --orange:#bd7a0a; --shadow-soft:0 14px 32px #174b3810; --shadow-card:0 18px 42px #174b3812; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--ink); background:radial-gradient(circle at 8% 0%,#e6faf1 0,transparent 27%),radial-gradient(circle at 95% 22%,#edf5ff 0,transparent 25%),var(--bg); font:14px/1.45 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color:inherit; text-decoration:none; }
        button, input, select { font:inherit; }
        .shell { min-height:100vh; }
        .topbar { position:sticky; top:0; z-index:20; height:74px; display:flex; align-items:center; justify-content:space-between; padding:0 clamp(24px,3vw,52px); background:#fffffff2; border-bottom:1px solid #dfece8; box-shadow:0 8px 24px #174b3808; backdrop-filter:blur(18px); }
        .brand { display:flex; align-items:center; gap:10px; font-weight:800; color:var(--green); letter-spacing:-.02em; transition:transform .2s ease; }
        .brand:hover { transform:translateY(-1px); }
        .brand-logo { width:32px; height:32px; display:block; object-fit:contain; border-radius:9px; }
        .brand small { display:block; color:#219279; font-size:9px; letter-spacing:.08em; }
        .top-actions { display:flex; align-items:center; gap:12px; }
        .pill { border:1px solid var(--line); border-radius:999px; padding:9px 14px; background:#fff; color:var(--muted); transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease; }
        .pill:hover { transform:translateY(-1px); border-color:#bfe6d6; box-shadow:0 8px 16px #174b3810; }
        .pill.active { color:var(--green); background:#eefaf6; border-color:#cceee2; }
        .avatar { width:38px; height:38px; display:grid; place-items:center; color:#fff; background:linear-gradient(135deg,#e1a68e,#c98068); border-radius:50%; font-weight:700; box-shadow:0 7px 15px #9a63451f; }
        .layout { display:flex; min-width:0; min-height:calc(100vh - 74px); }
        .sidebar { position:sticky; top:74px; z-index:10; width:270px; height:calc(100vh - 74px); flex:none; align-self:flex-start; overflow-y:auto; padding:22px 14px 18px; background:linear-gradient(180deg,#ffffff 0%,#f8fcfa 52%,#f1faf6 100%); border-right:1px solid #dfece7; scrollbar-width:thin; scrollbar-color:#c9ddd7 transparent; }
        .sidebar-heading { display:flex; align-items:center; gap:10px; margin:0 7px 25px; padding:13px 12px; border:1px solid #d9eee5; border-radius:18px; background:linear-gradient(135deg,#f5fffa,#e9f9f1); box-shadow:0 8px 18px #17684f0b; }
        .sidebar-heading-icon { display:grid; place-items:center; flex:none; width:34px; height:34px; border-radius:11px; background:#fff; box-shadow:0 5px 12px #17684f18; }
        .sidebar-heading-icon img { width:26px; height:26px; object-fit:contain; }
        .sidebar-heading-copy { display:flex; min-width:0; flex-direction:column; gap:2px; }
        .sidebar-heading-copy strong { color:#145b48; font-size:12px; letter-spacing:-.01em; }
        .sidebar-heading-copy small { color:#6c9388; font-size:8px; font-weight:800; letter-spacing:.08em; }
        .sidebar-nav { display:grid; gap:6px; }
        .side-label { margin:20px 11px 9px; color:#78928d; font-size:9px; font-weight:850; letter-spacing:.11em; text-transform:uppercase; }
        .side-link { position:relative; display:flex; align-items:center; gap:11px; min-height:55px; margin:0; padding:9px 11px; border:1px solid transparent; border-radius:16px; color:#49656a; transition:background .2s ease,border-color .2s ease,box-shadow .2s ease,transform .2s ease,color .2s ease; }
        .side-link:hover { color:#145b48; border-color:#cceade; background:#effbf5; box-shadow:0 8px 18px #17684f0d; transform:translateX(2px); }
        .side-link.current { color:#fff; border-color:#237b61; background:linear-gradient(135deg,#237c61,#2f8a6d); box-shadow:0 10px 22px #17684f30; }
        .side-icon { display:grid; place-items:center; flex:none; width:32px; height:32px; border-radius:11px; color:#73918d; background:#eef7f3; }
        .side-icon svg { width:17px; height:17px; fill:none; stroke:currentColor; stroke-width:1.7; stroke-linecap:round; stroke-linejoin:round; }
        .side-link.current .side-icon { color:#17684f; background:#d8f5e7; }
        .side-copy { display:flex; min-width:0; flex:1; flex-direction:column; gap:2px; }
        .side-copy strong { overflow:hidden; color:inherit; font-size:12px; font-weight:750; text-overflow:ellipsis; white-space:nowrap; }
        .side-copy small { overflow:hidden; color:inherit; opacity:.68; font-size:10px; text-overflow:ellipsis; white-space:nowrap; }
        .side-arrow { color:inherit; opacity:0; font-size:16px; transform:translateX(-4px); transition:opacity .2s ease,transform .2s ease; }
        .side-link:hover .side-arrow, .side-link.current .side-arrow { opacity:.8; transform:translateX(0); }
        .sidebar-footer { display:flex; align-items:flex-start; gap:9px; margin:24px 7px 0; padding:12px; border:1px solid #dceee6; border-radius:15px; background:#fff; }
        .sidebar-footer-dot { flex:none; width:8px; height:8px; margin-top:4px; border-radius:50%; background:#26ba7d; box-shadow:0 0 0 4px #26ba7d1c; }
        .sidebar-footer strong { display:block; color:#17684f; font-size:10px; }
        .sidebar-footer small { display:block; margin-top:2px; color:#78908e; font-size:9px; line-height:1.35; }
        .main { position:relative; isolation:isolate; min-width:0; flex:1; width:auto; max-width:none; padding:32px clamp(24px,3vw,52px) 64px; margin:0; }
        .main:before { content:""; position:absolute; z-index:-1; top:0; right:4%; width:340px; height:340px; border-radius:50%; background:#dff8ed; filter:blur(1px); opacity:.36; pointer-events:none; }
        .main > * { position:relative; z-index:1; }
        .welcome { position:relative; isolation:isolate; overflow:hidden; display:flex; align-items:center; justify-content:space-between; gap:20px; min-height:160px; padding:28px; border-radius:26px; background:linear-gradient(110deg,#fff 10%,#f2fffb 65%,#d9fbf1); border:1px solid #d8eee6; box-shadow:var(--shadow-card); }
        .welcome:before { content:""; position:absolute; z-index:-1; right:-74px; top:-110px; width:285px; height:285px; border-radius:50%; background:#b9f1db; opacity:.42; }
        .welcome:after { content:""; position:absolute; z-index:-1; right:150px; bottom:-120px; width:220px; height:220px; border-radius:50%; border:32px solid #fff8; }
        .eyebrow { display:flex; align-items:center; gap:7px; color:#178264; font-size:11px; font-weight:800; }
        .dot { width:8px; height:8px; background:#35cc8d; border-radius:50%; }
        h1,h2,h3,p { margin-top:0; }
        h1 { margin:7px 0 9px; font-size:29px; line-height:1.1; letter-spacing:-.045em; }
        h2 { margin-bottom:4px; font-size:20px; letter-spacing:-.03em; }
        .subtle { color:var(--muted); }
        .status { display:inline-flex; gap:8px; padding:7px 12px; color:var(--green); background:#dff9ed; border-radius:999px; font-size:12px; }
        .quick-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:14px; max-width:370px; }
        .quick { display:flex; flex:0 0 104px; width:104px; height:104px; flex-direction:column; justify-content:center; align-items:center; gap:5px; padding:10px; border:1px solid #126247; border-radius:50%; color:#fff; background:linear-gradient(145deg,#1a7458,#0f5a43); font-size:13px; line-height:1.2; text-align:center; font-weight:800; cursor:pointer; box-shadow:0 13px 24px #17684f2b; transition:transform .25s ease,box-shadow .25s ease,filter .25s ease; }
        .quick-icon { display:grid; place-items:center; min-height:21px; font-size:19px; line-height:1; }
        .quick:hover { transform:translateY(-5px) scale(1.03); box-shadow:0 19px 30px #17684f35; filter:saturate(1.08); }
        .quick:focus-visible { outline:3px solid #8fe1bf; outline-offset:4px; }
        .quick.red { color:#bd2626; border-color:#ffd1cd; background:linear-gradient(145deg,#fff0ed,#ffdeda); box-shadow:0 13px 24px #c9373220; }
        .quick.blue { color:#214f99; border-color:#c9daf8; background:linear-gradient(145deg,#edf3ff,#d8e6ff); box-shadow:0 13px 24px #214f991c; }
        .cards { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin:22px 0; }
        .card { position:relative; overflow:hidden; padding:20px; border:1px solid #dce9e8; border-radius:22px; background:#fff; box-shadow:var(--shadow-soft); transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease; }
        .card:after { content:""; position:absolute; right:-35px; bottom:-50px; width:110px; height:110px; border-radius:50%; background:#e9faf3; opacity:.7; pointer-events:none; }
        .card:hover { transform:translateY(-5px); border-color:#bee5d6; box-shadow:0 22px 44px #174b3818; }
        .card-head { display:flex; justify-content:space-between; gap:10px; color:#526e71; font-size:12px; }
        .metric { margin:12px 0 4px; font-size:25px; font-weight:800; letter-spacing:-.05em; }
        .metric.green { color:var(--green); }
        .metric.red { color:var(--red); }
        .metric small { color:var(--muted); font-size:12px; font-weight:500; letter-spacing:0; }
        .tag { display:inline-block; margin-top:10px; padding:6px 10px; border-radius:999px; color:var(--green); background:#d9f8ee; font-size:11px; font-weight:700; }
        .tag.alert { color:#a64c00; background:#fff0cf; }
        .progress { height:7px; margin:10px 0; border-radius:10px; background:#edf2f3; overflow:hidden; }
        .progress i { display:block; height:100%; border-radius:inherit; background:var(--green); }
        .two-col { display:grid; grid-template-columns:1.3fr .9fr; gap:18px; }
        .section-title { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
        .insight { border:1px solid #bce8d8; border-left:4px solid var(--green); border-radius:21px; background:#fff; padding:21px; box-shadow:var(--shadow-card); transition:transform .25s ease,box-shadow .25s ease; }
        .insight:hover { transform:translateY(-4px); box-shadow:0 24px 48px #174b381b; }
        .insight h2 { margin-top:10px; }
        .recommendation { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:16px; padding:14px; border-radius:14px; background:#f0f5ff; }
        .btn { display:inline-flex; justify-content:center; align-items:center; gap:7px; padding:12px 18px; border:0; border-radius:999px; color:#fff; background:linear-gradient(135deg,#17684f,#0e9b6b); font-weight:750; cursor:pointer; box-shadow:0 10px 20px #17684f24; transition:transform .2s ease,box-shadow .2s ease,filter .2s ease; }
        .btn:hover { transform:translateY(-2px); box-shadow:0 14px 24px #17684f32; filter:saturate(1.08); }
        .btn:focus-visible { outline:3px solid #8fe1bf; outline-offset:3px; }
        .btn.secondary { color:var(--green); background:#e7f7f1; box-shadow:none; }
        .btn.google { color:#253b40; background:#fff; border:1px solid var(--line); box-shadow:none; }
        .chart { min-height:255px; display:flex; flex-direction:column; justify-content:space-between; transition:transform .25s ease,box-shadow .25s ease; }
        .chart:hover { transform:translateY(-4px); box-shadow:0 22px 44px #174b3818; }
        .bars { height:145px; display:flex; align-items:end; justify-content:space-around; gap:10px; padding:0 8px 8px; border-bottom:1px solid #d9e7e7; background:repeating-linear-gradient(to bottom, transparent 0 35px, #edf3f3 36px); }
        .bar { width:22px; min-height:12px; border-radius:6px 6px 0 0; background:var(--green); }
        .bar.alert { background:#d34435; }
        .timeline, .trigger-list { display:grid; gap:10px; }
        .timeline-row, .trigger { display:flex; align-items:center; gap:14px; padding:14px; border:1px solid #e5efed; border-radius:17px; background:#ffffffc9; transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease; }
        .timeline-row:hover, .trigger:hover { transform:translateX(3px); border-color:#bee5d6; box-shadow:0 10px 20px #174b3810; }
        .timeline-time { width:66px; color:var(--muted); font-size:11px; }
        .timeline-line { width:4px; align-self:stretch; border-radius:5px; background:var(--green); }
        .timeline-line.alert { background:var(--red); }
        .timeline-copy { flex:1; }
        .timeline-copy strong { display:block; }
        .trigger { justify-content:space-between; background:#f6f8fe; }
        .trigger strong { display:block; }
        .match { padding:5px 9px; color:#a02525; background:#ffe0dd; border-radius:999px; font-size:11px; white-space:nowrap; }
        .auth-bg { min-height:100vh; padding:26px; background:radial-gradient(circle at 10% 10%,#dcf8ef,transparent 35%), #f5f8fc; }
        .auth-nav { max-width:1180px; margin:auto; display:flex; justify-content:space-between; align-items:center; }
        .auth-grid { max-width:1180px; margin:70px auto 0; display:grid; grid-template-columns:1.05fr .95fr; align-items:center; gap:80px; }
        .auth-copy h1 { max-width:560px; font-size:52px; }
        .auth-copy h1 span { color:var(--green); }
        .auth-copy p { max-width:510px; color:var(--muted); font-size:16px; }
        .auth-card { padding:29px; border:1px solid var(--line); border-radius:22px; background:#fff; box-shadow:0 20px 50px #12463b12; }
        .auth-card h2 { font-size:26px; }
        .field { margin:15px 0; }
        .field label { display:block; margin-bottom:7px; color:#446267; font-size:12px; font-weight:750; }
        .field input { width:100%; padding:13px 14px; border:1px solid #dce8e8; border-radius:11px; outline:none; }
        .field input:focus { border-color:#54ae91; box-shadow:0 0 0 3px #dff6ec; }
        .auth-card .btn { width:100%; margin-top:9px; }
        .divider { display:flex; align-items:center; gap:10px; margin:19px 0; color:#99a7a8; font-size:12px; }
        .divider:before,.divider:after { content:""; height:1px; flex:1; background:var(--line); }
        .error { margin-bottom:13px; padding:10px 12px; color:#a42626; background:#fff0ef; border-radius:10px; font-size:13px; }
        .auth-footer { margin-top:20px; text-align:center; color:var(--muted); font-size:13px; }
        .feature-row { display:flex; flex-wrap:wrap; gap:10px; margin-top:25px; }
        .feature { padding:8px 11px; color:var(--green); background:#e6f8f1; border-radius:999px; font-size:12px; }
        @keyframes pageEnter { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
        @media (prefers-reduced-motion: no-preference) { .main > * { animation:pageEnter .55s cubic-bezier(.2,.75,.25,1) both; } .main > *:nth-child(2) { animation-delay:.06s; } .main > *:nth-child(3) { animation-delay:.11s; } .main > *:nth-child(4) { animation-delay:.16s; } }
        @media (prefers-reduced-motion: reduce) { *, *:before, *:after { scroll-behavior:auto !important; transition-duration:0.01ms !important; animation-duration:0.01ms !important; animation-iteration-count:1 !important; } }
        @media (max-width:1000px) { .cards { grid-template-columns:repeat(2,1fr); } .two-col { grid-template-columns:1fr; } .welcome { align-items:flex-start; flex-direction:column; } .quick-actions { justify-content:flex-start; max-width:none; } .main { width:auto; } .sidebar { width:78px; padding:22px 10px 18px; } .sidebar-heading { justify-content:center; margin:0 0 24px; padding:8px; } .sidebar-heading-copy,.sidebar-footer,.side-copy,.side-arrow,.side-label { display:none; } .side-link { justify-content:center; padding:9px; } }
        @media (max-width:900px) { .top-actions .pill:not(.active), .top-actions form { display:none; } }
        @media (max-width:700px) { .topbar { height:64px; padding:0 15px; } .topbar .pill { display:none; } .topbar .pill.active { display:inline-flex; padding:7px 10px; font-size:11px; } .main { width:100%; padding:15px; } .sidebar { display:none; } .cards { grid-template-columns:1fr; } .quick-actions { width:100%; justify-content:space-between; gap:8px; } .quick { flex:1 1 0; width:auto; height:auto; min-width:0; aspect-ratio:1; } .auth-grid { grid-template-columns:1fr; margin-top:35px; gap:25px; } .auth-copy h1 { font-size:38px; } .auth-bg { padding:18px; } }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
