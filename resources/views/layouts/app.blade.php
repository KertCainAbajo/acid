<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'RefluxCare' }} · Patient portal</title>
    <style>
        :root { --ink:#0b1f2a; --muted:#64748b; --green:#0f7a56; --green-2:#12916a; --green-deep:#0a5a40; --mint:#d1fae5; --blue:#eef3ff; --line:#e5ece9; --bg:#f3f6f5; --red:#dc2626; --orange:#c2620a; --shadow-soft:0 1px 2px #0f172a0a,0 8px 24px -12px #0a5a4026; --shadow-card:0 1px 2px #0f172a0a,0 20px 40px -20px #0a5a4033; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--ink); background:radial-gradient(60% 50% at 0% 0%,#dcfce7 0,transparent 60%),radial-gradient(50% 40% at 100% 0%,#e0f2fe 0,transparent 60%),var(--bg); background-attachment:fixed; font:14px/1.5 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; -webkit-font-smoothing:antialiased; }
        a { color:inherit; text-decoration:none; }
        button, input, select { font:inherit; }
        .shell { min-height:100vh; animation:shellEnter .38s cubic-bezier(.2,.75,.25,1) both; }
        .topbar { position:sticky; top:0; z-index:20; height:72px; display:flex; align-items:center; gap:24px; padding:0 clamp(18px,2.4vw,32px); background:#ffffffcc; border-bottom:1px solid #e5ece980; backdrop-filter:saturate(1.6) blur(18px); }
        .brand { display:flex; align-items:center; gap:11px; width:234px; flex:none; color:var(--ink); transition:transform .2s ease; }
        .brand:hover { transform:translateY(-1px); }
        .brand-logo { width:40px; height:40px; display:block; object-fit:contain; padding:5px; border-radius:13px; background:#fff; box-shadow:0 0 0 1px #d1fae5,0 6px 16px -6px #0a5a4059; }
        .brand-copy { display:flex; flex-direction:column; line-height:1.15; }
        .brand-copy span { font-size:16px; font-weight:800; letter-spacing:-.03em; }
        .brand-copy small, .brand small { color:var(--green); font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .topbar-search { flex:1; max-width:520px; height:44px; display:flex; align-items:center; gap:10px; padding:0 8px 0 14px; border:1px solid var(--line); border-radius:14px; color:#94a3b8; background:#f8fafa; font-size:13px; transition:border-color .2s ease,box-shadow .2s ease,background .2s ease; }
        .topbar-search:hover { border-color:#a7f3d0; background:#fff; box-shadow:0 0 0 4px #d1fae580; }
        .topbar-search svg { width:17px; height:17px; flex:none; fill:none; stroke:#64748b; stroke-width:2; stroke-linecap:round; }
        .topbar-search span { flex:1; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
        .topbar-search kbd { display:grid; place-items:center; min-width:24px; height:24px; padding:0 6px; border:1px solid var(--line); border-radius:7px; color:#64748b; background:#fff; font:600 11px/1 ui-monospace,monospace; box-shadow:0 1px 0 #e2e8f0; }
        .top-actions { display:flex; align-items:center; gap:10px; margin-left:auto; }
        .pill { display:inline-flex; align-items:center; gap:7px; min-height:40px; border:1px solid var(--line); border-radius:12px; padding:8px 13px; background:#fff; color:#475569; font-size:12.5px; font-weight:600; transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease,background .2s ease,color .2s ease; }
        .pill svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
        .pill:hover { border-color:#a7f3d0; box-shadow:0 6px 14px -8px #0a5a4059; }
        .pill.active { color:var(--green-deep); background:#ecfdf5; border-color:#a7f3d0; font-weight:700; }
        .topbar-date { color:#334155; }
        .topbar-date svg { color:var(--green); }
        .topbar-user { display:flex; align-items:center; gap:10px; padding:4px 14px 4px 4px; border:1px solid var(--line); border-radius:999px; background:#fff; }
        .topbar-user-copy { display:flex; flex-direction:column; line-height:1.2; }
        .topbar-user-copy strong { max-width:140px; overflow:hidden; color:var(--ink); font-size:12.5px; text-overflow:ellipsis; white-space:nowrap; }
        .topbar-user-copy small { color:var(--muted); font-size:11px; }
        .avatar { width:34px; height:34px; flex:none; display:grid; place-items:center; color:#fff; background:linear-gradient(135deg,#34d399,#0f7a56); border-radius:50%; font-size:13px; font-weight:800; box-shadow:0 0 0 2px #fff,0 0 0 4px #a7f3d0; }
        .top-actions form { margin:0; }
        .topbar-logout { cursor:pointer; color:#64748b; background:transparent; border-color:transparent; }
        .topbar-logout:hover { color:#b91c1c; background:#fef2f2; border-color:#fecaca; box-shadow:none; }
        .layout { display:flex; min-width:0; min-height:calc(100vh - 72px); }
        .sidebar { position:sticky; top:72px; z-index:10; width:262px; height:calc(100vh - 72px); flex:none; align-self:flex-start; display:flex; flex-direction:column; overflow-y:auto; padding:20px 14px 18px; background:#ffffffb3; border-right:1px solid #e5ece980; backdrop-filter:blur(12px); scrollbar-width:thin; scrollbar-color:#cbd5e1 transparent; }
        .sidebar-heading { display:flex; align-items:center; gap:10px; margin:0 0 10px; padding:12px; border-radius:16px; color:#fff; background:radial-gradient(circle at 100% 0%,#34d39966,transparent 55%),linear-gradient(140deg,#0d4637,#0a2e26); box-shadow:0 14px 28px -14px #0a2e26b3; }
        .sidebar-heading-icon { display:grid; place-items:center; flex:none; width:34px; height:34px; border-radius:10px; background:#fff; }
        .sidebar-heading-icon img { width:26px; height:26px; object-fit:contain; }
        .sidebar-heading-copy { display:flex; min-width:0; flex-direction:column; gap:1px; }
        .sidebar-heading-copy strong { color:#fff; font-size:13px; letter-spacing:-.01em; }
        .sidebar-heading-copy small { color:#86efac; font-size:9.5px; font-weight:700; letter-spacing:.1em; }
        .sidebar-nav { display:grid; gap:3px; }
        .side-label { margin:20px 10px 8px; color:#94a3b8; font-size:10.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .side-link { position:relative; display:flex; align-items:center; gap:11px; min-height:52px; margin:0; padding:8px 10px; border-radius:14px; color:#475569; transition:background .2s ease,color .2s ease,box-shadow .2s ease; }
        .side-link:before { content:""; position:absolute; left:-14px; top:50%; width:4px; height:0; border-radius:0 4px 4px 0; background:var(--green); transform:translateY(-50%); transition:height .25s ease; }
        .side-link:hover { color:var(--ink); background:#f1f5f4; }
        .side-link.current { color:var(--green-deep); background:linear-gradient(90deg,#ecfdf5,#f0fdf4 70%,#ffffff00); box-shadow:inset 0 0 0 1px #d1fae5; }
        .side-link.current:before { height:28px; }
        .side-icon { display:grid; place-items:center; flex:none; width:34px; height:34px; border-radius:11px; color:#64748b; background:#f1f5f9; transition:background .2s ease,color .2s ease,transform .2s ease; }
        .side-icon svg { width:17px; height:17px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .side-link:hover .side-icon { color:var(--green); background:#fff; box-shadow:0 0 0 1px var(--line); transform:scale(1.05); }
        .side-link.current .side-icon { color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); box-shadow:0 8px 16px -6px #0a5a40aa; }
        .side-copy { display:flex; min-width:0; flex:1; flex-direction:column; gap:1px; }
        .side-copy strong { overflow:hidden; color:inherit; font-size:13px; font-weight:650; text-overflow:ellipsis; white-space:nowrap; }
        .side-copy small { overflow:hidden; color:#94a3b8; font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
        .side-link.current .side-copy small { color:#059669; }
        .side-arrow { color:var(--green); opacity:0; font-size:15px; transform:translateX(-4px); transition:opacity .2s ease,transform .2s ease; }
        .side-link:hover .side-arrow, .side-link.current .side-arrow { opacity:1; transform:translateX(0); }
        .sidebar-footer { display:flex; align-items:flex-start; gap:10px; margin:auto 0 0; padding:14px; border:1px solid #d1fae5; border-radius:16px; background:linear-gradient(160deg,#f0fdf4,#ffffff); }
        .sidebar-footer-dot { flex:none; width:8px; height:8px; margin-top:5px; border-radius:50%; background:#10b981; box-shadow:0 0 0 4px #10b98126; }
        .sidebar-footer strong { display:block; color:var(--green-deep); font-size:12.5px; }
        .sidebar-footer small { display:block; margin-top:2px; color:var(--muted); font-size:11px; line-height:1.4; }
        .main { position:relative; isolation:isolate; min-width:0; flex:1; width:auto; max-width:none; padding:28px clamp(18px,2.6vw,40px) 64px; margin:0; }
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
        .quick-actions { width:min(390px,100%); padding:12px; border:1px solid #d3eee3; border-radius:24px; background:#ffffff9c; box-shadow:0 16px 32px #17684f10; backdrop-filter:blur(10px); }
        .quick-actions-heading { display:flex; align-items:center; gap:7px; margin:0 0 9px 3px; color:#17684f; font-size:10px; font-weight:850; letter-spacing:.06em; text-transform:uppercase; }
        .quick-actions-heading .dot { width:7px; height:7px; }
        .quick-action-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:9px; }
        .quick { position:relative; display:flex; min-height:104px; flex-direction:column; justify-content:center; align-items:center; gap:4px; padding:10px 7px; border:1px solid #126247; border-radius:20px; color:#fff; background:linear-gradient(145deg,#1a7458,#0f5a43); font-size:12px; line-height:1.15; text-align:center; font-weight:800; cursor:pointer; box-shadow:0 10px 20px #17684f24; transition:transform .25s ease,box-shadow .25s ease,filter .25s ease; }
        .quick small { color:inherit; opacity:.78; font-size:9px; font-weight:600; line-height:1.2; }
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
        @keyframes shellEnter { from { opacity:0; transform:translateY(7px); } to { opacity:1; transform:none; } }
        @keyframes shellLeave { to { opacity:0; transform:translateY(-8px); filter:blur(1px); } }
        body.page-leaving .shell { pointer-events:none; animation:shellLeave .18s ease-in both; }
        @keyframes pageEnter { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
        @media (prefers-reduced-motion: no-preference) { .main > * { animation:pageEnter .55s cubic-bezier(.2,.75,.25,1) both; } .main > *:nth-child(2) { animation-delay:.06s; } .main > *:nth-child(3) { animation-delay:.11s; } .main > *:nth-child(4) { animation-delay:.16s; } }
        @media (prefers-reduced-motion: reduce) { *, *:before, *:after { scroll-behavior:auto !important; transition-duration:0.01ms !important; animation-duration:0.01ms !important; animation-iteration-count:1 !important; } }
        @media (max-width:1000px) { .cards { grid-template-columns:repeat(2,1fr); } .two-col { grid-template-columns:1fr; } .welcome { align-items:flex-start; flex-direction:column; } .quick-actions { width:100%; max-width:460px; } .main { width:auto; } .brand { width:auto; } .sidebar { width:82px; padding:18px 12px; } .sidebar-heading { justify-content:center; margin:0 0 12px; padding:8px; } .side-link:before { left:-12px; } .sidebar-heading-copy,.sidebar-footer,.side-copy,.side-arrow,.side-label { display:none; } .side-link { justify-content:center; padding:9px; } }
        @media (max-width:1240px) { .topbar-user-copy, .topbar-logout span { display:none; } .topbar-user { padding:3px; } }
        @media (max-width:900px) { .topbar-date { display:none; } .topbar-search { flex:0 0 44px; padding:0; justify-content:center; } .topbar-search span, .topbar-search kbd { display:none; } }
        @media (max-width:700px) { .topbar { height:64px; gap:10px; padding:0 14px; } .brand { gap:8px; } .brand-logo { width:36px; height:36px; } .brand-copy small { display:none; } .top-actions { gap:6px; } .topbar-search { margin-left:auto; } .topbar-logout { padding:8px 10px; } .main { width:100%; padding:15px; } .sidebar { display:none; } .cards { grid-template-columns:1fr; } .quick-actions { width:100%; } .quick { min-height:92px; border-radius:18px; } .auth-grid { grid-template-columns:1fr; margin-top:35px; gap:25px; } .auth-copy h1 { font-size:38px; } .auth-bg { padding:18px; } }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @yield('content')
</body>
</html>
