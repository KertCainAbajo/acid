<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'RefluxCare' }} · Clinical platform</title>
    <style>
        :root { --ink:#102c35; --muted:#6c8085; --green:#17684f; --green-2:#2b7c60; --mint:#ddf8ee; --blue:#eef3ff; --line:#e4ecec; --bg:#f5f8fb; --red:#c93732; --orange:#bd7a0a; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--ink); background:var(--bg); font:14px/1.45 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color:inherit; text-decoration:none; }
        button, input, select { font:inherit; }
        .shell { min-height:100vh; }
        .topbar { height:74px; display:flex; align-items:center; justify-content:space-between; padding:0 clamp(24px,3vw,52px); background:#fff; border-bottom:1px solid var(--line); }
        .brand { display:flex; align-items:center; gap:10px; font-weight:800; color:var(--green); letter-spacing:-.02em; }
        .brand-logo { width:32px; height:32px; display:block; object-fit:contain; border-radius:9px; }
        .brand small { display:block; color:#219279; font-size:9px; letter-spacing:.08em; }
        .top-actions { display:flex; align-items:center; gap:12px; }
        .pill { border:1px solid var(--line); border-radius:999px; padding:9px 14px; background:#fff; color:var(--muted); }
        .pill.active { color:var(--green); background:#eefaf6; border-color:#cceee2; }
        .avatar { width:38px; height:38px; display:grid; place-items:center; color:#fff; background:#e1a68e; border-radius:50%; font-weight:700; }
        .layout { display:flex; min-height:calc(100vh - 74px); }
        .sidebar { width:235px; flex:none; padding:27px 17px; background:#fff; border-right:1px solid var(--line); }
        .side-label { margin:16px 10px 8px; color:#809093; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .side-link { display:flex; gap:11px; align-items:center; margin:4px 0; padding:11px 12px; border-radius:12px; color:#56686c; }
        .side-link:hover, .side-link.current { color:#fff; background:var(--green-2); box-shadow:0 6px 16px #17684f25; }
        .side-link span:first-child { width:18px; text-align:center; }
        .main { flex:1; width:auto; max-width:none; padding:28px clamp(24px,3vw,52px) 56px; margin:0; }
        .welcome { display:flex; align-items:center; justify-content:space-between; gap:20px; min-height:145px; padding:24px; border-radius:22px; background:linear-gradient(110deg,#fff 10%,#f2fffb 65%,#d9fbf1); border:1px solid #e3efed; }
        .eyebrow { display:flex; align-items:center; gap:7px; color:#178264; font-size:11px; font-weight:800; }
        .dot { width:8px; height:8px; background:#35cc8d; border-radius:50%; }
        h1,h2,h3,p { margin-top:0; }
        h1 { margin:7px 0 9px; font-size:29px; line-height:1.1; letter-spacing:-.045em; }
        h2 { margin-bottom:4px; font-size:20px; letter-spacing:-.03em; }
        .subtle { color:var(--muted); }
        .status { display:inline-flex; gap:8px; padding:7px 12px; color:var(--green); background:#dff9ed; border-radius:999px; font-size:12px; }
        .quick-actions { display:flex; gap:10px; }
        .quick { width:92px; height:92px; border:0; border-radius:50%; color:#fff; background:var(--green); font-weight:800; cursor:pointer; }
        .quick.red { color:#bd2626; background:#ffe1df; }
        .quick.blue { color:#214f99; background:#dfeaff; }
        .cards { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin:18px 0; }
        .card { padding:18px; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:0 4px 18px #0d42400a; }
        .card-head { display:flex; justify-content:space-between; gap:10px; color:#526e71; font-size:12px; }
        .metric { margin:12px 0 4px; font-size:25px; font-weight:800; letter-spacing:-.05em; }
        .metric.green { color:var(--green); }
        .metric.red { color:var(--red); }
        .metric small { color:var(--muted); font-size:12px; font-weight:500; letter-spacing:0; }
        .tag { display:inline-block; margin-top:10px; padding:5px 9px; border-radius:999px; color:var(--green); background:#d9f8ee; font-size:11px; }
        .tag.alert { color:#a64c00; background:#fff0cf; }
        .progress { height:7px; margin:10px 0; border-radius:10px; background:#edf2f3; overflow:hidden; }
        .progress i { display:block; height:100%; border-radius:inherit; background:var(--green); }
        .two-col { display:grid; grid-template-columns:1.3fr .9fr; gap:18px; }
        .section-title { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
        .insight { border:1px solid #bce8d8; border-left:4px solid var(--green); border-radius:17px; background:#fff; padding:19px; }
        .insight h2 { margin-top:10px; }
        .recommendation { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:16px; padding:14px; border-radius:14px; background:#f0f5ff; }
        .btn { display:inline-flex; justify-content:center; align-items:center; gap:7px; padding:11px 17px; border:0; border-radius:999px; color:#fff; background:var(--green); font-weight:750; cursor:pointer; }
        .btn.secondary { color:var(--green); background:#e7f7f1; }
        .btn.google { color:#253b40; background:#fff; border:1px solid var(--line); }
        .chart { min-height:255px; display:flex; flex-direction:column; justify-content:space-between; }
        .bars { height:145px; display:flex; align-items:end; justify-content:space-around; gap:10px; padding:0 8px 8px; border-bottom:1px solid #d9e7e7; background:repeating-linear-gradient(to bottom, transparent 0 35px, #edf3f3 36px); }
        .bar { width:22px; min-height:12px; border-radius:6px 6px 0 0; background:var(--green); }
        .bar.alert { background:#d34435; }
        .timeline, .trigger-list { display:grid; gap:10px; }
        .timeline-row, .trigger { display:flex; align-items:center; gap:14px; padding:12px; border:1px solid #edf1f1; border-radius:14px; }
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
        @media (max-width:1000px) { .cards { grid-template-columns:repeat(2,1fr); } .two-col { grid-template-columns:1fr; } .welcome { align-items:flex-start; flex-direction:column; } .main { width:auto; } .sidebar { width:78px; } .side-link { justify-content:center; } .side-link span:last-child,.side-label { display:none; } }
        @media (max-width:700px) { .topbar { padding:0 15px; } .topbar .pill { display:none; } .main { width:100%; padding:15px; } .sidebar { display:none; } .cards { grid-template-columns:1fr; } .auth-grid { grid-template-columns:1fr; margin-top:35px; gap:25px; } .auth-copy h1 { font-size:38px; } .auth-bg { padding:18px; } }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
