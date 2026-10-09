<style>
    .lg { --ink:#0b1b24; --muted:#5f6f7e; --line:#e3e9ee; --brand:#0f7a56; --brand-deep:#0a5a40; --mint:#34d399; --mint-soft:#d1fae5; position:relative; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:clamp(16px,2.6vw,36px); overflow:hidden; color:var(--ink); background:#eef3f1; }
    .lg:before { content:""; position:fixed; inset:-20%; z-index:0; background:radial-gradient(40% 40% at 15% 20%,#bbf7d0aa 0,transparent 70%),radial-gradient(35% 35% at 85% 85%,#bae6fd99 0,transparent 70%),radial-gradient(30% 30% at 70% 10%,#d9f99d66 0,transparent 70%); filter:blur(20px); animation:lgDrift 22s ease-in-out infinite alternate; }
    .lg-shell { position:relative; z-index:1; width:min(1240px,100%); display:grid; grid-template-columns:minmax(0,1.08fr) minmax(0,1fr); padding:10px; border:1px solid #ffffffcc; border-radius:32px; background:#ffffff8c; box-shadow:0 1px 2px #0f172a0d,0 40px 100px -30px #0a5a4047; backdrop-filter:blur(24px); animation:lgEnter .6s cubic-bezier(.2,.8,.2,1) both; }

    /* ---------- Brand side ---------- */
    .lg-brand { position:relative; display:flex; flex-direction:column; gap:32px; padding:clamp(28px,3.2vw,44px); overflow:hidden; isolation:isolate; border-radius:24px; color:#ecfdf5; background:linear-gradient(160deg,#0d4637 0%,#0a3229 50%,#071f1a 100%); }
    .lg-aurora { position:absolute; inset:0; z-index:-2; overflow:hidden; }
    .lg-aurora i { position:absolute; border-radius:50%; filter:blur(60px); opacity:.55; animation:lgFloat 14s ease-in-out infinite alternate; }
    .lg-aurora i:nth-child(1) { width:340px; height:340px; top:-80px; right:-60px; background:#10b981; }
    .lg-aurora i:nth-child(2) { width:280px; height:280px; bottom:-60px; left:-80px; background:#0ea5e9; opacity:.28; animation-duration:18s; }
    .lg-aurora i:nth-child(3) { width:200px; height:200px; top:45%; left:45%; background:#a3e635; opacity:.18; animation-duration:20s; }
    .lg-brand:before { content:""; position:absolute; inset:0; z-index:-1; background-image:linear-gradient(#ffffff0f 1px,transparent 1px),linear-gradient(90deg,#ffffff0f 1px,transparent 1px); background-size:40px 40px; mask-image:radial-gradient(ellipse at 60% 35%,#000 10%,transparent 75%); }
    .lg-brand-top { display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .lg-logo { display:inline-flex; align-items:center; gap:12px; }
    .lg-logo img { width:44px; height:44px; padding:5px; object-fit:contain; border-radius:14px; background:#fff; box-shadow:0 10px 24px #0000004d,0 0 0 4px #ffffff14; }
    .lg-logo strong { display:block; color:#fff; font-size:16px; font-weight:800; letter-spacing:-.02em; }
    .lg-logo small { display:block; margin-top:2px; color:#86efac; font-size:9px; font-weight:700; letter-spacing:.16em; }
    .lg-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 12px 6px 9px; border:1px solid #6ee7b733; border-radius:999px; color:#a7f3d0; background:#ffffff0f; font-size:10.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; backdrop-filter:blur(8px); white-space:nowrap; }
    .lg-badge i { width:7px; height:7px; border-radius:50%; background:var(--mint); animation:lgPulse 2.4s ease-in-out infinite; }
    .lg-hero h1 { margin:0 0 16px; color:#fff; font-size:clamp(34px,3.4vw,50px); font-weight:800; line-height:1.06; letter-spacing:-.045em; }
    .lg-hero h1 em { display:block; font-style:normal; color:transparent; background:linear-gradient(95deg,#6ee7b7 0%,#34d399 45%,#a7f3d0 100%); background-size:200% auto; -webkit-background-clip:text; background-clip:text; animation:lgShine 6s linear infinite; }
    .lg-hero p { max-width:46ch; margin:0; color:#b9d6cc; font-size:15px; line-height:1.65; }

    /* Cards stage */
    .lg-stage { position:relative; width:min(100%,460px); margin:auto 0 30px; padding:14px 26px 0 0; perspective:1200px; }
    .lg-widget { position:relative; padding:14px; border:1px solid #ffffff24; border-radius:22px; background:linear-gradient(160deg,#ffffff1c,#ffffff0a); box-shadow:0 30px 60px -18px #00000080,inset 0 1px 0 #ffffff1f; backdrop-filter:blur(16px); transform-style:preserve-3d; transition:transform .35s cubic-bezier(.2,.8,.2,1); }
    .lg-widget-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:2px 4px 12px; }
    .lg-widget-head div { display:flex; align-items:center; gap:9px; color:#fff; font-size:13px; font-weight:700; }
    .lg-widget-head img { width:26px; height:26px; padding:3px; object-fit:contain; border-radius:8px; background:#fff; }
    .lg-chip { padding:4px 10px; border-radius:999px; color:#064e3b; background:#6ee7b7; font-size:10px; font-weight:800; }
    .lg-item { display:flex; align-items:center; gap:12px; margin-top:8px; padding:12px; border-radius:14px; background:#fff; box-shadow:0 6px 16px #00000024; transition:transform .25s ease,box-shadow .25s ease; animation:lgRise .55s cubic-bezier(.2,.8,.2,1) both; }
    .lg-item:nth-of-type(2) { animation-delay:.1s; } .lg-item:nth-of-type(3) { animation-delay:.2s; } .lg-item:nth-of-type(4) { animation-delay:.3s; }
    .lg-item:hover { transform:translateX(5px); box-shadow:0 12px 26px #00000033; }
    .lg-icon { width:38px; height:38px; flex:none; display:grid; place-items:center; border-radius:12px; }
    .lg-icon svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .lg-icon.is-food { color:#c2410c; background:linear-gradient(145deg,#fff7ed,#ffedd5); } .lg-icon.is-sleep { color:#4338ca; background:linear-gradient(145deg,#eef2ff,#e0e7ff); } .lg-icon.is-insight { color:#047857; background:linear-gradient(145deg,#ecfdf5,#d1fae5); }
    .lg-item-copy { flex:1; min-width:0; }
    .lg-item-copy strong { display:block; color:#0b1b24; font-size:13px; font-weight:700; }
    .lg-item-copy span { display:block; margin-top:2px; color:#64748b; font-size:11.5px; }
    .lg-tag { padding:3px 8px; border-radius:999px; font-size:10px; font-weight:700; white-space:nowrap; }
    .lg-tag.is-warn { color:#9a3412; background:#ffedd5; } .lg-tag.is-ok { color:#3730a3; background:#e0e7ff; }
    .lg-bars { display:flex; align-items:flex-end; gap:3px; height:30px; }
    .lg-bars i { width:5px; border-radius:3px; background:#a7f3d0; transform-origin:bottom; animation:lgGrow .8s cubic-bezier(.2,.8,.2,1) both; }
    .lg-bars i.is-high { background:#10b981; }
    .lg-bars i:nth-child(2) { animation-delay:.05s; } .lg-bars i:nth-child(3) { animation-delay:.1s; } .lg-bars i:nth-child(4) { animation-delay:.15s; } .lg-bars i:nth-child(5) { animation-delay:.2s; } .lg-bars i:nth-child(6) { animation-delay:.25s; } .lg-bars i:nth-child(7) { animation-delay:.3s; }

    /* Floating accents */
    .lg-float { position:absolute; z-index:2; display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid #ffffffe6; border-radius:16px; background:#ffffffeb; box-shadow:0 18px 40px -10px #00000066; backdrop-filter:blur(12px); animation:lgBob 6s ease-in-out infinite; }
    .lg-float strong { display:block; color:#0b1b24; font-size:12px; font-weight:800; }
    .lg-float span { display:block; color:#64748b; font-size:10.5px; }
    .lg-float.is-ring { top:-26px; right:-6px; }
    .lg-float.is-toast { bottom:-34px; right:-2px; animation-delay:-3s; }
    .lg-ring { position:relative; width:40px; height:40px; flex:none; }
    .lg-ring svg { width:40px; height:40px; transform:rotate(-90deg); }
    .lg-ring circle { fill:none; stroke-width:5; }
    .lg-ring .track { stroke:#e2f5ec; } .lg-ring .value { stroke:#10b981; stroke-linecap:round; stroke-dasharray:100; stroke-dashoffset:29; animation:lgRing 1.4s .4s cubic-bezier(.2,.8,.2,1) both; }
    .lg-ring b { position:absolute; inset:0; display:grid; place-items:center; color:#065f46; font-size:10px; font-weight:800; }
    .lg-float .lg-toast-dot { width:30px; height:30px; flex:none; display:grid; place-items:center; border-radius:10px; color:#fff; background:linear-gradient(145deg,#34d399,#059669); }
    .lg-toast-dot svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.4; stroke-linecap:round; stroke-linejoin:round; }

    .lg-brand-foot { display:flex; flex-wrap:wrap; align-items:center; gap:8px 20px; padding-top:18px; border-top:1px solid #ffffff1a; color:#9cc5b8; font-size:12px; }
    .lg-brand-foot span { display:inline-flex; align-items:center; gap:6px; margin-right:auto; }
    .lg-brand-foot svg { width:14px; height:14px; fill:none; stroke:#6ee7b7; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .lg-brand-foot a { color:#d1fae5; font-weight:600; transition:color .2s ease; } .lg-brand-foot a:hover { color:#fff; text-decoration:underline; }

    /* ---------- Form side ---------- */
    .lg-panel { position:relative; display:flex; flex-direction:column; padding:clamp(24px,3vw,40px) clamp(24px,4.4vw,64px); }
    .lg-panel-top { display:flex; align-items:center; justify-content:flex-end; gap:12px; color:var(--muted); font-size:13px; }
    .lg-ghost { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border:1px solid var(--line); border-radius:999px; color:var(--ink); background:#fff; font-weight:600; transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease; }
    .lg-ghost:hover { border-color:#a7f3d0; box-shadow:0 6px 16px #0a5a401a; transform:translateY(-1px); }
    .lg-ghost svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .lg-form { width:min(100%,420px); margin:auto; padding:32px 0; animation:lgRise .6s .12s cubic-bezier(.2,.8,.2,1) both; }
    .lg-eyebrow { display:inline-flex; align-items:center; gap:8px; margin-bottom:14px; color:var(--brand); font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .lg-eyebrow:before { content:""; width:18px; height:2px; border-radius:2px; background:currentColor; }
    .lg-form h2 { margin:0; color:var(--ink); font-size:clamp(28px,2.4vw,34px); font-weight:800; line-height:1.1; letter-spacing:-.04em; }
    .lg-intro { margin:10px 0 28px; color:var(--muted); font-size:14.5px; line-height:1.6; }
    .lg-alert { display:flex; align-items:flex-start; gap:10px; margin-bottom:18px; padding:12px 14px; border:1px solid #fecaca; border-radius:14px; color:#991b1b; background:#fef2f2; font-size:13px; animation:lgShake .4s ease both; }
    .lg-alert svg { width:18px; height:18px; flex:none; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }
    .lg-google { width:100%; height:50px; display:flex; align-items:center; justify-content:center; gap:10px; border:1px solid var(--line); border-radius:14px; color:#1e293b; background:#fff; box-shadow:0 1px 2px #0f172a0d; font-size:14px; font-weight:600; transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease; }
    .lg-google:hover { border-color:#cbd5e1; box-shadow:0 8px 20px -6px #0f172a26; transform:translateY(-1px); }
    .lg-google:focus-visible { outline:3px solid #a7f3d0; outline-offset:2px; }
    .lg-google svg { width:19px; height:19px; }
    .lg-divider { display:flex; align-items:center; gap:14px; margin:24px 0; color:#94a3b8; font-size:12px; }
    .lg-divider:before,.lg-divider:after { content:""; flex:1; height:1px; background:linear-gradient(90deg,transparent,var(--line) 30%,var(--line) 70%,transparent); }

    /* Floating-label fields */
    .lg-field { position:relative; margin-bottom:16px; }
    .lg-field > svg { position:absolute; top:28px; left:16px; z-index:1; width:18px; height:18px; fill:none; stroke:#94a3b8; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; transform:translateY(-50%); pointer-events:none; transition:stroke .2s ease; }
    .lg-input { width:100%; height:56px; padding:20px 16px 6px 46px; border:1.5px solid var(--line); border-radius:14px; color:var(--ink); background:#f8fafb; outline:none; font-size:14.5px; transition:border-color .2s ease,box-shadow .2s ease,background .2s ease; }
    .lg-input:hover { border-color:#cbd5e1; }
    .lg-input:focus { border-color:#10b981; background:#fff; box-shadow:0 0 0 4px #d1fae5b3; }
    .lg-input.has-toggle { padding-right:52px; }
    .lg-input[aria-invalid="true"] { border-color:#f87171; background:#fffafa; }
    .lg-field label { position:absolute; top:28px; left:46px; color:#7b8a99; font-size:14px; transform:translateY(-50%); transform-origin:left center; pointer-events:none; transition:top .18s ease,transform .18s ease,color .18s ease,font-weight .18s ease; }
    .lg-input:focus ~ label, .lg-input:not(:placeholder-shown) ~ label { top:17px; transform:translateY(-50%) scale(.78); color:var(--brand); font-weight:600; }
    .lg-input:not(:focus):not(:placeholder-shown) ~ label { color:#64748b; }
    .lg-field:focus-within > svg { stroke:var(--brand); }
    .password-toggle { position:absolute; top:28px; right:10px; display:grid; place-items:center; width:36px; height:36px; padding:0; border:0; border-radius:10px; color:#64748b; background:transparent; cursor:pointer; transform:translateY(-50%); }
    .password-toggle:hover { color:var(--brand); background:#ecfdf5; }
    .password-toggle:focus-visible { outline:2px solid #10b981; }
    .password-toggle svg { position:absolute; width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; transition:opacity .18s ease,transform .18s ease; }
    .password-toggle .eye-open { opacity:0; transform:scale(.72) rotate(-10deg); }
    .password-toggle.is-visible .eye-open { opacity:1; transform:none; }
    .password-toggle.is-visible .eye-closed { opacity:0; transform:scale(.72) rotate(10deg); }
    .lg-caps { display:none; align-items:center; gap:6px; margin:8px 0 0 4px; color:#b45309; font-size:12px; font-weight:600; } .lg-caps.is-on { display:flex; }

    .lg-options { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:6px 0 24px; }
    .lg-check { display:inline-flex; align-items:center; gap:10px; color:#334155; font-size:13.5px; cursor:pointer; user-select:none; }
    .lg-check input { position:absolute; opacity:0; pointer-events:none; }
    .lg-check-box { width:20px; height:20px; display:grid; place-items:center; border:1.5px solid #cbd5e1; border-radius:6px; background:#fff; transition:background .2s ease,border-color .2s ease,box-shadow .2s ease; }
    .lg-check-box svg { width:12px; height:12px; fill:none; stroke:#fff; stroke-width:3; stroke-linecap:round; stroke-linejoin:round; stroke-dasharray:20; stroke-dashoffset:20; transition:stroke-dashoffset .25s ease; }
    .lg-check input:checked + .lg-check-box { border-color:var(--brand); background:var(--brand); }
    .lg-check input:checked + .lg-check-box svg { stroke-dashoffset:0; }
    .lg-check input:focus-visible + .lg-check-box { box-shadow:0 0 0 4px #d1fae5; }
    .lg-link { color:var(--brand); font-size:13.5px; font-weight:600; background:linear-gradient(currentColor,currentColor) 0 100%/0 1.5px no-repeat; transition:background-size .25s ease,color .2s ease; }
    .lg-link:hover { color:var(--brand-deep); background-size:100% 1.5px; }

    .lg-submit { position:relative; width:100%; height:54px; display:flex; align-items:center; justify-content:center; gap:10px; overflow:hidden; border:0; border-radius:14px; color:#fff; background:linear-gradient(135deg,#12916a 0%,#0f7a56 45%,#0a5a40 100%); box-shadow:inset 0 1px 0 #ffffff40,0 14px 30px -10px #0a5a40b3; cursor:pointer; font-size:15px; font-weight:700; letter-spacing:.01em; transition:transform .2s ease,box-shadow .2s ease; }
    .lg-submit:before { content:""; position:absolute; top:0; left:-60%; width:40%; height:100%; background:linear-gradient(100deg,transparent,#ffffff40,transparent); transform:skewX(-20deg); transition:left .6s ease; }
    .lg-submit:hover { transform:translateY(-2px); box-shadow:inset 0 1px 0 #ffffff40,0 20px 36px -10px #0a5a40cc; }
    .lg-submit:hover:before { left:120%; }
    .lg-submit:active { transform:translateY(0); }
    .lg-submit:focus-visible { outline:3px solid #6ee7b7; outline-offset:3px; }
    .lg-submit svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; transition:transform .2s ease; }
    .lg-submit:hover svg { transform:translateX(4px); }
    .lg-spinner { display:none; width:18px; height:18px; border:2px solid #ffffff59; border-top-color:#fff; border-radius:50%; animation:lgSpin .7s linear infinite; }
    .lg-submit.is-loading { pointer-events:none; }
    .lg-submit.is-loading .lg-spinner { display:block; } .lg-submit.is-loading svg { display:none; }

    .lg-trust { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:28px; }
    .lg-trust div { display:flex; flex-direction:column; align-items:center; gap:6px; padding:12px 8px; border:1px solid #edf2f0; border-radius:14px; background:#fbfdfc; text-align:center; transition:border-color .2s ease,transform .2s ease; }
    .lg-trust div:hover { border-color:#bbf7d0; transform:translateY(-2px); }
    .lg-trust svg { width:30px; height:30px; padding:7px; border-radius:9px; fill:none; stroke:#059669; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; background:#ecfdf5; }
    .lg-trust span { color:#334155; font-size:11.5px; font-weight:600; }

    @keyframes lgEnter { from { opacity:0; transform:translateY(16px) scale(.985); } to { opacity:1; transform:none; } }
    @keyframes lgRise { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
    @keyframes lgGrow { from { transform:scaleY(0); } to { transform:scaleY(1); } }
    @keyframes lgRing { from { stroke-dashoffset:100; } }
    @keyframes lgBob { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-6px); } }
    @keyframes lgFloat { from { transform:translate(0,0) scale(1); } to { transform:translate(-40px,30px) scale(1.15); } }
    @keyframes lgDrift { from { transform:translate(0,0); } to { transform:translate(4%,-3%); } }
    @keyframes lgShine { to { background-position:200% center; } }
    @keyframes lgPulse { 0%,100% { box-shadow:0 0 0 3px #34d39940; } 50% { box-shadow:0 0 0 7px #34d39912; } }
    @keyframes lgSpin { to { transform:rotate(360deg); } }
    @keyframes lgShake { 0%,100% { transform:none; } 25% { transform:translateX(-5px); } 75% { transform:translateX(5px); } }
    @media (prefers-reduced-motion:reduce) { .lg *, .lg:before, .lg-shell { animation:none !important; transition:none !important; } }
    @media (max-width:1024px) { .lg-shell { grid-template-columns:minmax(0,1fr); } .lg-stage { width:min(100%,520px); } .lg-form { padding:24px 0 8px; } }
    @media (max-width:560px) { .lg { padding:0; } .lg-shell { padding:0; border:0; border-radius:0; } .lg-brand { border-radius:0; padding:26px 20px; } .lg-brand-top { flex-direction:column; align-items:flex-start; } .lg-panel { padding:22px 20px 36px; } .lg-stage { padding-right:0; } .lg-float.is-ring { right:4px; } .lg-float.is-toast { display:none; } .lg-trust { grid-template-columns:1fr 1fr 1fr; gap:6px; } }
</style>
