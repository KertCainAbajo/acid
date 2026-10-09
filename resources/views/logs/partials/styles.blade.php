<style>
    .lp { display:grid; gap:20px; }
    .lp-svg { width:19px; height:19px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .lp-tone-orange { --tone:#c2410c; --tone-soft:#fff7ed; --tone-mid:#fed7aa; }
    .lp-tone-amber { --tone:#a16207; --tone-soft:#fefce8; --tone-mid:#fde68a; }
    .lp-tone-red { --tone:#b91c1c; --tone-soft:#fef2f2; --tone-mid:#fecaca; }
    .lp-tone-sky { --tone:#0369a1; --tone-soft:#f0f9ff; --tone-mid:#bae6fd; }
    .lp-tone-green { --tone:#047857; --tone-soft:#ecfdf5; --tone-mid:#a7f3d0; }
    .lp-badge-icon { display:grid; place-items:center; flex:none; width:40px; height:40px; border-radius:12px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); }
    .lp-panel { position:relative; padding:24px; border:1px solid #fff; border-radius:22px; background:#ffffffd9; box-shadow:var(--shadow-card); backdrop-filter:blur(10px); }
    .lp-panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:18px; }
    .lp-panel-head h2 { margin:0; font-size:17px; font-weight:750; letter-spacing:-.02em; }
    .lp-panel-head p { margin:3px 0 0; color:var(--muted); font-size:13px; }

    /* Hero */
    .lp-hero { position:relative; display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:28px; padding:28px 32px; overflow:hidden; isolation:isolate; border-radius:26px; color:#ecfdf5; background:linear-gradient(150deg,#0d4637 0%,#0a3229 55%,#071f1a 100%); box-shadow:0 30px 60px -30px #0a2e26cc; }
    .lp-hero:before { content:""; position:absolute; inset:0; z-index:-1; background-image:linear-gradient(#ffffff0d 1px,transparent 1px),linear-gradient(90deg,#ffffff0d 1px,transparent 1px); background-size:36px 36px; mask-image:radial-gradient(ellipse at 25% 40%,#000 0,transparent 70%); }
    .lp-hero:after { content:""; position:absolute; z-index:-1; top:-120px; right:18%; width:300px; height:300px; border-radius:50%; background:#10b98159; filter:blur(50px); }
    .lp-crumb { display:inline-flex; align-items:center; gap:8px; color:#a7f3d0; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .lp-crumb a { color:#6ee7b7; } .lp-crumb a:hover { color:#fff; }
    .lp-hero h1 { margin:12px 0 8px; color:#fff; font-size:clamp(26px,2.5vw,34px); font-weight:800; line-height:1.1; letter-spacing:-.035em; }
    .lp-hero p { max-width:56ch; margin:0; color:#a9cbbf; font-size:14px; line-height:1.6; }
    .lp-hero-stats { display:grid; grid-template-columns:repeat(3,minmax(118px,1fr)); gap:10px; }
    .lp-hero-stat { padding:14px 16px; border:1px solid #ffffff1f; border-radius:16px; background:#ffffff12; backdrop-filter:blur(10px); }
    .lp-hero-stat span { display:block; color:#a7f3d0; font-size:11px; font-weight:600; }
    .lp-hero-stat strong { display:block; margin-top:4px; color:#fff; font-size:24px; font-weight:800; letter-spacing:-.03em; line-height:1.1; }
    .lp-hero-stat strong.is-text { font-size:16px; padding-top:5px; }
    .lp-hero-stat small { color:#a9cbbf; font-size:11.5px; }

    /* Layout */
    .lp-grid { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(320px,1fr); gap:20px; align-items:start; }
    .lp-side { display:grid; gap:20px; }

    /* Form */
    .lp-progress { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin:0 0 24px; }
    .lp-progress-step { display:flex; align-items:center; gap:9px; padding:10px 12px; border:1px solid #eef2f1; border-radius:13px; color:#64748b; background:#fafcfb; font-size:12.5px; font-weight:600; transition:border-color .25s ease,background .25s ease,color .25s ease; }
    .lp-progress-step b { display:grid; place-items:center; flex:none; width:22px; height:22px; border-radius:50%; color:#64748b; background:#e2e8f0; font-size:11px; transition:background .25s ease,color .25s ease; }
    .lp-progress-step svg { display:none; width:12px; height:12px; fill:none; stroke:#fff; stroke-width:3; stroke-linecap:round; stroke-linejoin:round; }
    .lp-progress-step.is-done { border-color:#a7f3d0; color:var(--green-deep); background:#ecfdf5; }
    .lp-progress-step.is-done b { color:#fff; background:var(--green); }
    .lp-progress-step.is-done b span { display:none; } .lp-progress-step.is-done svg { display:block; }
    .lp-field { margin-bottom:24px; }
    .lp-label { display:flex; align-items:baseline; justify-content:space-between; gap:10px; margin-bottom:9px; color:var(--ink); font-size:14px; font-weight:700; }
    .lp-label em { color:#dc2626; font-style:normal; }
    .lp-label small { color:#94a3b8; font-size:12px; font-weight:500; }
    .lp-input-wrap { position:relative; }
    .lp-input-wrap > svg { position:absolute; top:50%; left:16px; width:19px; height:19px; transform:translateY(-50%); color:#94a3b8; pointer-events:none; transition:color .2s ease; }
    .lp-input-wrap:focus-within > svg { color:var(--green); }
    .lp-input, .lp-textarea { width:100%; border:1.5px solid var(--line); border-radius:14px; color:var(--ink); background:#f8fafa; outline:none; font-size:15px; transition:border-color .2s ease,box-shadow .2s ease,background .2s ease; }
    .lp-input { height:54px; padding:0 16px 0 46px; }
    .lp-input:hover, .lp-textarea:hover { border-color:#cbd5e1; }
    .lp-input:focus, .lp-textarea:focus { border-color:#10b981; background:#fff; box-shadow:0 0 0 4px #d1fae5b3; }
    .lp-input[aria-invalid="true"], .lp-textarea[aria-invalid="true"] { border-color:#f87171; background:#fffafa; }
    .lp-textarea { min-height:104px; padding:14px 16px; font:inherit; font-size:15px; line-height:1.55; resize:vertical; }
    .lp-hint { display:block; margin-top:7px; color:#94a3b8; font-size:12px; }
    .lp-error { display:none; margin-top:7px; color:#b91c1c; font-size:12.5px; font-weight:600; }
    .lp-error.is-on { display:block; }
    .lp-chips { display:flex; flex-wrap:wrap; align-items:center; gap:7px; margin-top:10px; }
    .lp-chips > span { color:#94a3b8; font-size:12px; font-weight:600; }
    .lp-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 11px; border:1px solid var(--line); border-radius:999px; color:#334155; background:#fff; font-size:12.5px; font-weight:600; cursor:pointer; transition:border-color .2s ease,background .2s ease,transform .2s ease; }
    .lp-chip:hover { border-color:#a7f3d0; background:#f0fdf4; transform:translateY(-1px); }
    .lp-chip:focus-visible { outline:2px solid #10b981; outline-offset:2px; }
    .lp-chip i { width:7px; height:7px; border-radius:50%; background:var(--tone); }
    .lp-chip.is-active { border-color:var(--green); color:#fff; background:var(--green); }

    .lp-categories { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin:0; padding:0; border:0; }
    .lp-category { position:relative; display:flex; flex-direction:column; gap:10px; min-height:142px; padding:14px; border:1.5px solid var(--line); border-radius:16px; background:#fff; cursor:pointer; transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease,background .2s ease; }
    .lp-category:hover { border-color:var(--tone-mid); transform:translateY(-2px); box-shadow:0 12px 22px -14px #0f172a40; }
    .lp-category input { position:absolute; opacity:0; pointer-events:none; }
    .lp-category strong { display:block; color:var(--ink); font-size:13.5px; line-height:1.25; }
    .lp-category small { display:block; margin-top:2px; color:var(--muted); font-size:11.5px; line-height:1.35; }
    .lp-category-tag { width:max-content; margin-top:auto; padding:2px 8px; border-radius:999px; color:var(--tone); background:var(--tone-soft); font-size:10.5px; font-weight:700; }
    .lp-category-check { position:absolute; top:12px; right:12px; width:20px; height:20px; display:grid; place-items:center; border:1.5px solid #cbd5e1; border-radius:50%; background:#fff; transition:background .2s ease,border-color .2s ease,transform .2s ease; }
    .lp-category-check svg { width:11px; height:11px; fill:none; stroke:#fff; stroke-width:3.2; stroke-linecap:round; stroke-linejoin:round; opacity:0; }
    .lp-category:has(input:checked) { border-color:var(--tone); background:var(--tone-soft); box-shadow:0 0 0 3px var(--tone-mid); }
    .lp-category:has(input:checked) .lp-category-check { border-color:var(--tone); background:var(--tone); transform:scale(1.08); }
    .lp-category:has(input:checked) .lp-category-check svg { opacity:1; }
    .lp-category:has(input:focus-visible) { outline:2px solid var(--tone); outline-offset:2px; }
    .lp-categories[aria-invalid="true"] .lp-category { border-color:#fecaca; }

    .lp-time { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:10px; align-items:center; }
    .lp-time .lp-input { padding-left:46px; }
    .lp-presets { display:flex; gap:6px; }
    .lp-presets .lp-chip { padding:8px 11px; border-radius:11px; }

    .lp-actions { display:flex; align-items:center; gap:14px; margin-top:6px; padding-top:22px; border-top:1px solid #eef2f1; }
    .lp-submit { position:relative; flex:1; height:56px; display:flex; align-items:center; justify-content:center; gap:10px; overflow:hidden; border:0; border-radius:15px; color:#fff; background:linear-gradient(135deg,#12916a 0%,#0f7a56 45%,#0a5a40 100%); box-shadow:inset 0 1px 0 #ffffff40,0 14px 30px -10px #0a5a40b3; cursor:pointer; font-size:15.5px; font-weight:700; transition:transform .2s ease,box-shadow .2s ease,filter .2s ease; }
    .lp-submit:before { content:""; position:absolute; top:0; left:-60%; width:40%; height:100%; background:linear-gradient(100deg,transparent,#ffffff40,transparent); transform:skewX(-20deg); transition:left .6s ease; }
    .lp-submit:hover:not(:disabled) { transform:translateY(-2px); box-shadow:inset 0 1px 0 #ffffff40,0 20px 36px -10px #0a5a40cc; }
    .lp-submit:hover:before { left:120%; }
    .lp-submit:focus-visible { outline:3px solid #6ee7b7; outline-offset:3px; }
    .lp-submit:disabled { cursor:wait; filter:saturate(.75); }
    .lp-submit .lp-spinner { display:none; width:18px; height:18px; border:2px solid #ffffff59; border-top-color:#fff; border-radius:50%; animation:lpSpin .7s linear infinite; }
    .lp-submit.is-loading .lp-spinner { display:block; } .lp-submit.is-loading .lp-submit-icon { display:none; }
    .lp-submit.is-success { background:linear-gradient(135deg,#10b981,#059669); }
    .lp-kbd { color:#94a3b8; font-size:12px; white-space:nowrap; }
    .lp-kbd kbd { padding:2px 6px; border:1px solid var(--line); border-radius:6px; background:#fff; font:600 11px ui-monospace,monospace; }
    .lp-status { display:none; align-items:center; gap:10px; margin-top:16px; padding:12px 14px; border-radius:13px; font-size:13.5px; font-weight:600; }
    .lp-status svg { width:18px; height:18px; flex:none; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .lp-status.is-success { display:flex; color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0; animation:lpRise .3s ease both; }
    .lp-status.is-error { display:flex; color:#991b1b; background:#fef2f2; border:1px solid #fecaca; animation:lpShake .4s ease both; }

    /* Breakdown */
    .lp-stack { display:flex; height:12px; overflow:hidden; border-radius:999px; background:#f1f5f9; }
    .lp-stack i { height:100%; background:var(--tone); transform-origin:left; animation:lpGrowX .9s cubic-bezier(.2,.8,.2,1) both; }
    .lp-stack i + i { box-shadow:-2px 0 0 #fff; }
    .lp-legend { display:grid; gap:8px; margin-top:16px; }
    .lp-legend-row { display:grid; grid-template-columns:10px minmax(0,1fr) auto; align-items:center; gap:10px; color:#334155; font-size:13px; }
    .lp-legend-row i { width:10px; height:10px; border-radius:3px; background:var(--tone); }
    .lp-legend-row strong { font-size:13px; }
    .lp-legend-row.is-zero { color:#94a3b8; } .lp-legend-row.is-zero i { opacity:.35; }

    /* History */
    .lp-history { display:grid; gap:18px; max-height:620px; overflow-y:auto; margin-right:-8px; padding-right:8px; scrollbar-width:thin; scrollbar-color:#cbd5e1 transparent; }
    .lp-day h3 { position:sticky; top:0; z-index:1; display:flex; align-items:center; justify-content:space-between; margin:0 0 8px; padding:4px 0; color:#64748b; background:linear-gradient(#fffffff2,#ffffffd9); font-size:11.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .lp-day h3 span { color:#94a3b8; font-weight:600; letter-spacing:0; text-transform:none; }
    .lp-meal { display:grid; grid-template-columns:40px minmax(0,1fr) auto; align-items:center; gap:12px; padding:10px; border:1px solid transparent; border-radius:14px; transition:background .2s ease,border-color .2s ease; }
    .lp-meal + .lp-meal { margin-top:4px; }
    .lp-meal:hover { border-color:#eef2f1; background:#fafcfb; }
    .lp-meal strong { display:block; overflow:hidden; color:var(--ink); font-size:14px; text-overflow:ellipsis; white-space:nowrap; }
    .lp-meal span { display:block; overflow:hidden; color:var(--muted); font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
    .lp-meal time { color:#94a3b8; font-size:12px; font-weight:600; white-space:nowrap; }
    .lp-latest { margin-left:6px; padding:1px 7px; border-radius:999px; color:#065f46; background:#d1fae5; font-size:10.5px; font-weight:700; vertical-align:middle; }
    .lp-empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:30px 18px; border:1.5px dashed #d8e3df; border-radius:16px; color:var(--muted); background:#fafcfb; font-size:13px; text-align:center; }
    .lp-empty svg { width:38px; height:38px; padding:9px; border-radius:12px; color:#10b981; background:#ecfdf5; }
    .lp-empty strong { color:var(--ink); font-size:14px; }
    .lp-tip { display:flex; gap:14px; padding:18px; border:1px solid #d1fae5; border-radius:18px; background:linear-gradient(150deg,#f0fdf4,#ffffff); }
    .lp-tip strong { display:block; color:var(--green-deep); font-size:14px; }
    .lp-tip p { margin:4px 0 0; color:#475569; font-size:13px; line-height:1.55; }

    @keyframes lpSpin { to { transform:rotate(360deg); } }
    @keyframes lpGrowX { from { transform:scaleX(0); } }
    @keyframes lpRise { from { opacity:0; transform:translateY(6px); } }
    @keyframes lpShake { 25% { transform:translateX(-5px); } 75% { transform:translateX(5px); } }
    @media (max-width:1400px) { .lp-categories { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width:1180px) { .lp-hero { grid-template-columns:1fr; } .lp-grid { grid-template-columns:minmax(0,1fr); } .lp-history { max-height:none; } }
    @media (max-width:700px) { .lp-hero { padding:24px 20px; } .lp-hero-stats { grid-template-columns:repeat(3,minmax(0,1fr)); } .lp-hero-stat { padding:12px; } .lp-hero-stat strong { font-size:20px; } .lp-panel { padding:18px; } .lp-categories { grid-template-columns:repeat(2,minmax(0,1fr)); } .lp-category { min-height:0; } .lp-time { grid-template-columns:1fr; } .lp-presets { flex-wrap:wrap; } .lp-progress-step { justify-content:center; padding:9px 6px; font-size:12px; } .lp-kbd { display:none; } }

    /* Shared 1-10 meter and weekly chart */
    .lp-meter { padding:18px; border:1.5px solid var(--line); border-radius:16px; background:#fafcfb; transition:border-color .2s ease,box-shadow .2s ease; }
    .lp-meter:focus-within { border-color:#10b981; box-shadow:0 0 0 4px #d1fae5b3; }
    .lp-meter-top { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:16px; }
    .lp-meter-value { display:flex; align-items:baseline; gap:6px; }
    .lp-meter-value strong { color:var(--tone); font-size:40px; font-weight:800; line-height:1; letter-spacing:-.04em; transition:color .2s ease; }
    .lp-meter-value span { color:#94a3b8; font-size:14px; font-weight:600; }
    .lp-meter-label { padding:6px 12px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:13px; font-weight:700; transition:color .2s ease,background .2s ease; }
    .lp-range { width:100%; height:10px; margin:0; border-radius:999px; background:linear-gradient(90deg,#34d399,#facc15 45%,#fb923c 70%,#dc2626); -webkit-appearance:none; appearance:none; cursor:pointer; outline:none; }
    .lp-range::-webkit-slider-thumb { width:28px; height:28px; border:4px solid #fff; border-radius:50%; background:var(--tone); box-shadow:0 0 0 1px #cbd5e1,0 6px 14px -4px #0f172a66; -webkit-appearance:none; appearance:none; transition:transform .15s ease; }
    .lp-range::-webkit-slider-thumb:hover { transform:scale(1.1); }
    .lp-range::-moz-range-thumb { width:22px; height:22px; border:4px solid #fff; border-radius:50%; background:var(--tone); box-shadow:0 0 0 1px #cbd5e1,0 6px 14px -4px #0f172a66; }
    .lp-range:focus-visible::-webkit-slider-thumb { box-shadow:0 0 0 4px #a7f3d0,0 6px 14px -4px #0f172a66; }
    .lp-scale { display:grid; grid-template-columns:repeat(10,1fr); gap:4px; margin-top:12px; }
    .lp-scale button { height:30px; padding:0; border:1px solid var(--line); border-radius:8px; color:#64748b; background:#fff; font-size:12px; font-weight:700; cursor:pointer; transition:background .15s ease,color .15s ease,border-color .15s ease,transform .15s ease; }
    .lp-scale button:hover { border-color:#cbd5e1; transform:translateY(-1px); }
    .lp-scale button.is-active { border-color:var(--tone); color:#fff; background:var(--tone); }
    .lp-scale-ends { display:flex; justify-content:space-between; margin-top:6px; color:#94a3b8; font-size:11.5px; }
    .lp-care { display:none; align-items:flex-start; gap:10px; margin-top:12px; padding:12px 14px; border:1px solid #fecaca; border-radius:13px; color:#991b1b; background:#fef2f2; font-size:13px; line-height:1.5; }
    .lp-care.is-on { display:flex; animation:lpRise .3s ease both; }
    .lp-care svg { flex:none; margin-top:1px; }
    .lp-chart { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; align-items:end; height:140px; padding:0 2px; background:repeating-linear-gradient(to top,#eef2f1 0 1px,transparent 1px 35px); border-bottom:1px solid #e2e8f0; }
    .lp-col { position:relative; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; height:100%; border-radius:10px; }
    .lp-col.is-today { background:linear-gradient(to top,#ecfdf5,transparent); }
    .lp-col i { width:min(22px,60%); min-height:4px; border-radius:7px 7px 2px 2px; background:var(--tone); transform-origin:bottom; animation:lpGrow .8s cubic-bezier(.2,.8,.2,1) both; }
    .lp-col i.is-zero { background:#e2e8f0; }
    .lp-col b { margin-bottom:4px; color:#475569; font-size:11px; }
    .lp-chart-tip { position:absolute; bottom:calc(100% + 6px); left:50%; z-index:5; min-width:120px; padding:8px 10px; border-radius:10px; color:#fff; background:#0b1f2a; font-size:11.5px; line-height:1.45; opacity:0; pointer-events:none; transform:translate(-50%,6px); transition:opacity .18s ease,transform .18s ease; }
    .lp-col:hover .lp-chart-tip { opacity:1; transform:translate(-50%,0); }
    .lp-days { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; margin-top:8px; color:#94a3b8; font-size:11.5px; font-weight:600; text-align:center; }
    .lp-days .is-today { color:var(--green); }
    .lp-chart-legend { display:flex; flex-wrap:wrap; gap:12px; margin-top:14px; color:var(--muted); font-size:11.5px; }
    .lp-chart-legend span { display:inline-flex; align-items:center; gap:5px; }
    .lp-chart-legend i { width:9px; height:9px; border-radius:3px; background:var(--tone); }
    @keyframes lpGrow { from { transform:scaleY(0); } }
</style>
