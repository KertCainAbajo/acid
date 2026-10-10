@include('logs.partials.styles')
<style>
    .lp-tone-violet { --tone:#6d28d9; --tone-soft:#f5f3ff; --tone-mid:#ddd6fe; }
    .lp-tone-slate { --tone:#475569; --tone-soft:#f1f5f9; --tone-mid:#e2e8f0; }
    .au-flash { display:flex; align-items:center; gap:10px; padding:12px 16px; border:1px solid #a7f3d0; border-radius:14px; color:#065f46; background:#ecfdf5; font-size:13.5px; font-weight:600; animation:lpRise .3s ease both; }
    .au-flash.is-error { border-color:#fecaca; color:#991b1b; background:#fef2f2; }
    .au-flash svg { width:18px; height:18px; flex:none; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .au-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:16px; }
    .au-search { position:relative; flex:1; min-width:200px; }
    .au-search svg { position:absolute; top:50%; left:12px; width:16px; height:16px; color:#94a3b8; transform:translateY(-50%); fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }
    .au-input, .au-select { height:42px; border:1.5px solid var(--line); border-radius:12px; color:var(--ink); background:#f8fafa; outline:none; font:inherit; font-size:13.5px; transition:border-color .2s ease,box-shadow .2s ease,background .2s ease; }
    .au-input { width:100%; padding:0 12px; }
    .au-search .au-input { padding-left:36px; }
    .au-select { padding:0 34px 0 12px; cursor:pointer; }
    .au-input:focus, .au-select:focus { border-color:#10b981; background:#fff; box-shadow:0 0 0 4px #d1fae5b3; }
    .au-input[aria-invalid="true"] { border-color:#f87171; }
    .au-tabs { display:flex; flex-wrap:wrap; gap:4px; padding:4px; border:1px solid var(--line); border-radius:12px; background:#f8fafa; }
    .au-tabs a { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:9px; color:#475569; font-size:12.5px; font-weight:600; }
    .au-tabs a:hover { background:#fff; }
    .au-tabs a.is-active { color:#fff; background:var(--green); }
    .au-tabs b { padding:0 6px; border-radius:999px; background:#0000000f; font-size:11px; }
    .au-tabs a.is-active b { background:#ffffff33; }
    .au-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:40px; padding:0 14px; border:1px solid var(--line); border-radius:11px; color:#334155; background:#fff; font:inherit; font-size:13px; font-weight:700; white-space:nowrap; cursor:pointer; transition:border-color .2s ease,background .2s ease,transform .2s ease; }
    .au-btn:hover { border-color:#a7f3d0; background:#f0fdf4; }
    .au-btn svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.1; stroke-linecap:round; stroke-linejoin:round; }
    .au-btn.is-primary { border-color:var(--green); color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); }
    .au-btn.is-primary:hover { transform:translateY(-1px); }
    .au-btn.is-danger { border-color:#fecaca; color:#b91c1c; background:#fff; }
    .au-btn.is-danger:hover { background:#fef2f2; }
    .au-btn.is-solid-danger { border-color:#dc2626; color:#fff; background:#dc2626; }
    .au-btn.is-sm { height:32px; padding:0 10px; border-radius:9px; font-size:12px; }
    .au-table { width:100%; border-collapse:collapse; font-size:13px; }
    .au-table th { padding:0 10px 10px; color:#94a3b8; font-size:11px; font-weight:700; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
    .au-table td { padding:11px 10px; border-top:1px solid #f1f5f4; vertical-align:middle; }
    .au-table tr:hover td { background:#fafcfb; }
    .au-person { display:flex; align-items:center; gap:10px; min-width:0; }
    .au-avatar { width:36px; height:36px; flex:none; display:grid; place-items:center; border-radius:11px; color:#fff; background:linear-gradient(135deg,#34d399,#0f7a56); font-size:13px; font-weight:800; }
    .au-avatar.is-off { background:linear-gradient(135deg,#cbd5e1,#64748b); }
    .au-person strong { display:block; overflow:hidden; color:var(--ink); font-size:13.5px; text-overflow:ellipsis; white-space:nowrap; }
    .au-person small { display:block; overflow:hidden; color:var(--muted); font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
    .au-pill { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:11px; font-weight:700; white-space:nowrap; }
    .au-pill i { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .au-muted { color:#94a3b8; font-size:12px; white-space:nowrap; }
    .au-row-actions { display:flex; justify-content:flex-end; gap:6px; }
    .au-row-actions form { margin:0; }
    .au-pagination { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-top:16px; padding-top:14px; border-top:1px solid #eef2f1; color:var(--muted); font-size:12.5px; }
    .au-pagination nav { display:flex; gap:6px; }
    .au-modal { position:fixed; inset:0; z-index:50; display:none; place-items:center; padding:20px; background:#0b1f2a8c; backdrop-filter:blur(4px); }
    .au-modal.is-open { display:grid; animation:lpRise .2s ease both; }
    .au-modal-card { width:min(460px,100%); padding:24px; border-radius:20px; background:#fff; box-shadow:0 30px 60px -20px #0b1f2acc; }
    .au-modal-card h3 { display:flex; align-items:center; gap:10px; margin:0 0 8px; font-size:18px; }
    .au-modal-card p { margin:0 0 14px; color:#475569; font-size:13.5px; line-height:1.6; }
    .au-modal-card label { display:block; margin-bottom:6px; color:var(--ink); font-size:12.5px; font-weight:700; }
    .au-modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:16px; }
    .au-error { margin:6px 0 0; color:#b91c1c; font-size:12.5px; font-weight:600; }
    @media (max-width:760px) { .au-hide-sm { display:none; } .au-toolbar > * { flex:1 1 100%; } }
</style>
