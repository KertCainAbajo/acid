<style>
    .fp { position:relative; z-index:1; width:min(460px,100%); padding:clamp(24px,4vw,40px); border:1px solid #fff; border-radius:28px; background:#ffffffeb; box-shadow:0 1px 2px #0f172a0d,0 40px 100px -30px #0a5a4047; backdrop-filter:blur(24px); animation:lgEnter .5s cubic-bezier(.2,.8,.2,1) both; }
    .fp-icon { width:64px; height:64px; display:grid; place-items:center; margin-bottom:18px; border-radius:20px; color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); box-shadow:0 16px 30px -12px #0a5a40aa; }
    .fp-icon svg { width:28px; height:28px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .fp-step { display:inline-block; margin-bottom:8px; color:var(--brand); font-size:11.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .fp h1 { margin:0; color:var(--ink); font-size:26px; font-weight:800; letter-spacing:-.03em; }
    .fp > p { margin:8px 0 22px; color:var(--muted); font-size:14px; line-height:1.6; }
    .fp-success { display:flex; gap:10px; margin:16px 0; padding:14px; border:1px solid #a7f3d0; border-radius:14px; color:#065f46; background:#ecfdf5; font-size:13.5px; line-height:1.5; }
    .fp-success svg { width:20px; height:20px; flex:none; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .fp-hints { display:grid; gap:8px; margin:0 0 16px; padding-left:18px; color:#475569; font-size:13px; line-height:1.5; }
    .fp-dev { margin-bottom:16px; padding:12px 14px; border-radius:13px; color:#92400e; background:#fffbeb; box-shadow:inset 0 0 0 1px #fde68a; font-size:12.5px; line-height:1.5; }
    .fp-dev code { padding:1px 5px; border-radius:5px; background:#fef3c7; font-size:12px; }
    .fp-secondary { width:100%; height:46px; border:1.5px solid var(--line); border-radius:14px; color:var(--ink); background:#fff; font:inherit; font-size:14px; font-weight:700; cursor:pointer; transition:border-color .2s ease,background .2s ease; }
    .fp-secondary:hover { border-color:#a7f3d0; background:#f0fdf4; }
    .fp-back { margin:18px 0 0; text-align:center; }
    .fp-back a { display:inline-flex; align-items:center; gap:6px; color:var(--brand); font-size:13.5px; font-weight:700; }
    .fp-back svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .fp-rules { display:grid; grid-template-columns:1fr 1fr; gap:6px 12px; margin:-6px 0 16px; padding:0; list-style:none; }
    .fp-rules li { display:flex; align-items:center; gap:7px; color:#94a3b8; font-size:12px; }
    .fp-rules li svg { width:14px; height:14px; flex:none; padding:2px; border-radius:50%; fill:none; stroke:#fff; stroke-width:3.4; stroke-linecap:round; stroke-linejoin:round; background:#e2e8f0; transition:background .2s ease; }
    .fp-rules li.is-met { color:#065f46; } .fp-rules li.is-met svg { background:#10b981; }
    .fp-error { margin:-8px 0 14px 4px; color:#b91c1c; font-size:12.5px; font-weight:600; }
</style>
