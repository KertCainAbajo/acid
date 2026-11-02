@extends('layouts.app')

@section('content')
@include('auth.partials.styles')
<style>
    /* ---------- Login-only layer on top of the shared auth styles ---------- */
    .lx .lg-shell { grid-template-columns:minmax(0,1.12fr) minmax(0,1fr); }
    .lx .lg-brand { gap:26px; background:radial-gradient(120% 80% at 100% 0%,#13775a 0%,transparent 55%),linear-gradient(165deg,#0c4434 0%,#082c24 52%,#05181a 100%); }
    .lx-noise { position:absolute; inset:0; z-index:-1; opacity:.5; background:radial-gradient(#ffffff14 1px,transparent 1.2px) 0 0/22px 22px; mask-image:linear-gradient(180deg,#000,transparent 85%); }
    .lx-beam { position:absolute; z-index:-1; top:-30%; left:30%; width:2px; height:160%; background:linear-gradient(180deg,transparent,#6ee7b766,transparent); transform:rotate(28deg); filter:blur(1px); animation:lxBeam 9s ease-in-out infinite; }
    .lx-hero h1 { margin:0 0 14px; color:#fff; font-size:clamp(34px,3.3vw,50px); font-weight:800; line-height:1.04; letter-spacing:-.05em; }
    .lx-hero h1 em { font-style:normal; color:transparent; background:linear-gradient(95deg,#a7f3d0,#34d399 40%,#5eead4 70%,#a7f3d0); background-size:220% auto; -webkit-background-clip:text; background-clip:text; animation:lgShine 7s linear infinite; }
    .lx-hero p { max-width:48ch; margin:0; color:#b4d3c8; font-size:14.5px; line-height:1.65; }
    .lx-points { display:flex; flex-wrap:wrap; gap:8px; margin-top:18px; }
    .lx-points span { display:inline-flex; align-items:center; gap:7px; padding:6px 11px; border:1px solid #ffffff1c; border-radius:999px; color:#d1fae5; background:#ffffff0d; font-size:12px; font-weight:600; backdrop-filter:blur(8px); }
    .lx-points svg { width:13px; height:13px; fill:none; stroke:#6ee7b7; stroke-width:2.4; stroke-linecap:round; stroke-linejoin:round; }

    /* Bento preview */
    .lx-bento { position:relative; display:grid; grid-template-columns:minmax(0,1.35fr) minmax(0,1fr); grid-template-rows:auto auto; gap:12px; margin-top:auto; perspective:1400px; }
    .lx-card { position:relative; padding:16px; overflow:hidden; border:1px solid #ffffff1f; border-radius:20px; background:linear-gradient(160deg,#ffffff17,#ffffff08); box-shadow:inset 0 1px 0 #ffffff1a,0 24px 50px -24px #000000b3; backdrop-filter:blur(16px); transition:transform .4s cubic-bezier(.2,.8,.2,1),border-color .3s ease; animation:lgRise .7s cubic-bezier(.2,.8,.2,1) both; }
    .lx-card:hover { border-color:#6ee7b74d; }
    .lx-card:nth-child(2) { animation-delay:.08s; } .lx-card:nth-child(3) { animation-delay:.16s; } .lx-card:nth-child(4) { animation-delay:.24s; }
    .lx-card.is-chart { grid-row:span 2; display:flex; flex-direction:column; }
    .lx-card-head { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .lx-label { color:#a7f3d0; font-size:10.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .lx-live { display:inline-flex; align-items:center; gap:6px; padding:3px 9px; border-radius:999px; color:#064e3b; background:#6ee7b7; font-size:10px; font-weight:800; }
    .lx-live i { width:6px; height:6px; border-radius:50%; background:#047857; animation:lgPulse 2s ease-in-out infinite; }
    .lx-metric { display:flex; align-items:baseline; gap:8px; margin-top:10px; color:#fff; }
    .lx-metric strong { font-size:30px; font-weight:800; letter-spacing:-.04em; line-height:1; }
    .lx-metric span { color:#86efac; font-size:12px; font-weight:700; }
    .lx-chart { position:relative; flex:1; min-height:120px; margin-top:12px; }
    .lx-chart svg { width:100%; height:100%; overflow:visible; }
    .lx-chart .grid line { stroke:#ffffff12; stroke-dasharray:3 5; }
    .lx-chart .area { fill:url(#lxArea); animation:lxFade 1.2s .5s ease both; }
    .lx-chart .line { fill:none; stroke:url(#lxLine); stroke-width:3; stroke-linecap:round; stroke-dasharray:1; stroke-dashoffset:1; animation:lxDraw 1.6s .3s cubic-bezier(.4,0,.2,1) forwards; }
    .lx-chart .dot { fill:#fff; stroke:#10b981; stroke-width:3; animation:lxPop .4s 1.8s cubic-bezier(.2,.8,.2,1.4) both; transform-box:fill-box; transform-origin:center; }
    .lx-days { display:flex; justify-content:space-between; margin-top:8px; color:#7fb5a4; font-size:10.5px; font-weight:700; }
    .lx-days b { color:#fff; }
    .lx-gauge { position:relative; width:100%; max-width:150px; margin:8px auto 0; }
    .lx-gauge svg { width:100%; display:block; }
    .lx-gauge path { fill:none; stroke-width:11; stroke-linecap:round; }
    .lx-gauge .track { stroke:#ffffff17; }
    .lx-gauge .value { stroke:url(#lxGauge); stroke-dasharray:100; stroke-dashoffset:100; animation:lxGauge 1.4s .5s cubic-bezier(.2,.8,.2,1) forwards; }
    .lx-gauge-copy { position:absolute; left:0; right:0; bottom:0; text-align:center; }
    .lx-gauge-copy strong { display:block; color:#fff; font-size:22px; font-weight:800; line-height:1; }
    .lx-gauge-copy span { color:#86efac; font-size:10.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
    .lx-trigger { display:flex; align-items:center; gap:11px; margin-top:10px; }
    .lx-trigger-icon { width:38px; height:38px; flex:none; display:grid; place-items:center; border-radius:12px; color:#fdba74; background:#f9731626; box-shadow:inset 0 0 0 1px #fb923c40; }
    .lx-trigger-icon svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .lx-trigger strong { display:block; color:#fff; font-size:14px; font-weight:750; }
    .lx-trigger small { display:block; color:#9cc5b8; font-size:11.5px; }
    .lx-meter { height:6px; margin-top:12px; overflow:hidden; border-radius:99px; background:#ffffff14; }
    .lx-meter span { display:block; width:72%; height:100%; border-radius:inherit; background:linear-gradient(90deg,#fbbf24,#f97316); transform-origin:left; animation:lgGrowX 1.2s .6s cubic-bezier(.2,.8,.2,1) both; }
    @keyframes lgGrowX { from { transform:scaleX(0); } }

    /* Floating AI insight */
    .lx-insight { position:absolute; z-index:3; right:-8px; top:-84px; display:flex; align-items:center; gap:11px; max-width:310px; padding:11px 14px 11px 11px; border:1px solid #ffffffe6; border-radius:16px; background:#ffffffef; box-shadow:0 22px 44px -14px #000000a6; backdrop-filter:blur(14px); animation:lgBob 6s ease-in-out infinite; }
    .lx-insight-icon { width:34px; height:34px; flex:none; display:grid; place-items:center; border-radius:11px; color:#fff; background:conic-gradient(from 200deg,#10b981,#14b8a6,#34d399,#10b981); }
    .lx-insight-icon svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .lx-insight small { display:block; color:#059669; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .lx-insight strong { display:block; min-height:17px; color:#0b1b24; font-size:12.5px; font-weight:700; }
    .lx-caret { display:inline-block; width:2px; height:13px; margin-left:2px; vertical-align:-2px; background:#10b981; animation:lxBlink 1s steps(2) infinite; }

    /* ---------- Form side ---------- */
    .lx .lg-panel { background:radial-gradient(70% 50% at 100% 0%,#ecfdf5 0%,transparent 70%); border-radius:24px; }
    .lx .lg-form { width:min(100%,410px); }
    .lg-alert.is-success { border-color:#a7f3d0; color:#065f46; background:#ecfdf5; animation:lgRise .4s ease both; }
    .lx-greet { display:inline-flex; align-items:center; gap:8px; margin-bottom:16px; padding:5px 12px 5px 6px; border:1px solid #d1fae5; border-radius:999px; color:#065f46; background:#f0fdf4; font-size:12px; font-weight:700; }
    .lx-greet i { width:22px; height:22px; display:grid; place-items:center; border-radius:50%; color:#fff; background:linear-gradient(145deg,#34d399,#059669); font-style:normal; font-size:12px; }
    .lx .lg-form h2 { font-size:clamp(30px,2.6vw,38px); letter-spacing:-.045em; }
    .lx .lg-form h2 span { color:transparent; background:linear-gradient(95deg,#0f7a56,#10b981); -webkit-background-clip:text; background-clip:text; }
    .lx .lg-intro { margin-bottom:26px; }
    .lx .lg-google { height:52px; border-radius:15px; }
    .lx .lg-input { border-radius:15px; }
    .lx .lg-submit { height:56px; border-radius:15px; }
    .lx-kbd { display:inline-flex; align-items:center; gap:4px; margin-left:4px; padding:2px 6px; border:1px solid #ffffff4d; border-radius:6px; background:#ffffff1f; font-size:11px; font-weight:700; }
    .lx-secure { display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:6px 16px; margin-top:22px; padding:14px; border:1px dashed #d4e4dd; border-radius:16px; background:#fbfdfc; }
    .lx-secure span { display:inline-flex; align-items:center; gap:6px; color:#475569; font-size:12px; font-weight:600; }
    .lx-secure svg { width:15px; height:15px; fill:none; stroke:#059669; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .lx-legal { margin-top:18px; color:#94a3b8; font-size:11.5px; line-height:1.6; text-align:center; }
    .lx-legal a { color:#475569; font-weight:600; text-decoration:underline; text-decoration-color:#cbd5e1; text-underline-offset:2px; }
    .lx-legal a:hover { color:var(--brand); }

    @keyframes lxDraw { to { stroke-dashoffset:0; } }
    @keyframes lxGauge { to { stroke-dashoffset:76; } }
    @keyframes lxFade { from { opacity:0; } }
    @keyframes lxPop { from { transform:scale(0); } }
    @keyframes lxBlink { 50% { opacity:0; } }
    @keyframes lxBeam { 0%,100% { opacity:.2; transform:translateX(-80px) rotate(28deg); } 50% { opacity:.9; transform:translateX(120px) rotate(28deg); } }
    @media (prefers-reduced-motion:reduce) { .lx .line { stroke-dashoffset:0; } .lx .lx-gauge .value { stroke-dashoffset:76; } }
    @media (max-width:1024px) { .lx .lg-shell { grid-template-columns:minmax(0,1fr); } .lx-bento { margin-bottom:30px; } }
    @media (max-width:560px) { .lx-bento { grid-template-columns:minmax(0,1fr); } .lx-card { min-width:0; } .lx-card.is-chart { grid-row:auto; } .lx-insight { position:relative; inset:auto; margin-top:4px; max-width:none; } .lx-secure { justify-content:flex-start; } }
</style>

<main class="lg lx">
    <div class="lg-shell">
        <section class="lg-brand" aria-label="About Acid Reflux Tracker">
            <div class="lg-aurora" aria-hidden="true"><i></i><i></i><i></i></div>
            <span class="lx-noise" aria-hidden="true"></span>
            <span class="lx-beam" aria-hidden="true"></span>

            <div class="lg-brand-top">
                <a class="lg-logo" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo1.png') }}" alt="">
                    <span><strong>Acid Reflux Tracker</strong><small>PERSONAL HEALTH WORKSPACE</small></span>
                </a>
                <span class="lg-badge"><i></i> Private &amp; secure</span>
            </div>

            <div class="lx-hero">
                <h1>Understand your body, <em>manage your health.</em></h1>
                <p>Track food, symptoms, sleep and stress in one place. Smart pattern analysis shows what triggers your reflux and what helps.</p>
                <div class="lx-points">
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>Trigger detection</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>Personal recommendations</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>Exportable history</span>
                </div>
            </div>

            <div class="lx-bento" id="lx-bento" aria-hidden="true">
                <div class="lx-card is-chart" data-tilt>
                    <div class="lx-card-head"><span class="lx-label">Symptom trend</span><span class="lx-live"><i></i>7 days</span></div>
                    <div class="lx-metric"><strong>5/7</strong><span>calm days this week</span></div>
                    <div class="lx-chart">
                        <svg viewBox="0 0 280 110" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="lxArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#34d399" stop-opacity=".38"/><stop offset="1" stop-color="#34d399" stop-opacity="0"/></linearGradient>
                                <linearGradient id="lxLine" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fbbf24"/><stop offset=".45" stop-color="#34d399"/><stop offset="1" stop-color="#5eead4"/></linearGradient>
                            </defs>
                            <g class="grid"><line x1="0" y1="25" x2="280" y2="25"/><line x1="0" y1="55" x2="280" y2="55"/><line x1="0" y1="85" x2="280" y2="85"/></g>
                            <path class="area" d="M0,28 C20,28 26,58 46,58 S72,42 93,42 S120,70 140,70 S166,60 186,60 S212,84 233,84 S260,90 280,88 L280,110 L0,110 Z"/>
                            <path class="line" pathLength="1" d="M0,28 C20,28 26,58 46,58 S72,42 93,42 S120,70 140,70 S166,60 186,60 S212,84 233,84 S260,90 280,88"/>
                            <circle class="dot" cx="280" cy="88" r="5"/>
                        </svg>
                    </div>
                    <div class="lx-days"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><b>Today</b></div>
                </div>

                <div class="lx-card" data-tilt>
                    <div class="lx-card-head"><span class="lx-label">Reflux risk</span></div>
                    <div class="lx-gauge">
                        <svg viewBox="0 0 120 68">
                            <defs><linearGradient id="lxGauge" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#34d399"/><stop offset="1" stop-color="#5eead4"/></linearGradient></defs>
                            <path class="track" d="M10 60 A50 50 0 0 1 110 60" pathLength="100"/>
                            <path class="value" d="M10 60 A50 50 0 0 1 110 60" pathLength="100"/>
                        </svg>
                        <div class="lx-gauge-copy"><strong>Low</strong><span>Today</span></div>
                    </div>
                </div>

                <div class="lx-card" data-tilt>
                    <div class="lx-card-head"><span class="lx-label">Top trigger</span></div>
                    <div class="lx-trigger">
                        <span class="lx-trigger-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <div><strong>Late meals</strong><small>Linked to night symptoms</small></div>
                    </div>
                    <div class="lx-meter"><span></span></div>
                </div>

                <div class="lx-insight">
                    <span class="lx-insight-icon"><svg viewBox="0 0 24 24"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/></svg></span>
                    <span><small>Pattern insight</small><strong><span id="lx-typed" data-lines="Late meals → night symptoms|7+ hours of sleep → calmer mornings|Coffee on an empty stomach → heartburn|Low stress days → fewer flare-ups">Late meals → night symptoms</span><span class="lx-caret"></span></strong></span>
                </div>
            </div>

            <div class="lg-brand-foot">
                <span><svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> Your data stays private</span>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('terms') }}">Terms of Service</a>
            </div>
        </section>

        <section class="lg-panel">
            <div class="lg-panel-top">
                <span>New here?</span>
                <a class="lg-ghost" href="{{ route('register') }}">Create account <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>

            <div class="lg-form">
                <span class="lx-greet"><i aria-hidden="true">✦</i><span id="lx-greeting">Welcome to your health workspace</span></span>
                <h2>Welcome back<span>.</span></h2>
                <p class="lg-intro">Sign in to continue tracking your health and reviewing your patterns.</p>

                @if (session('status'))
                    <div class="lg-alert is-success" role="status">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="lg-alert" role="alert">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <a class="lg-google" href="{{ route('google.redirect') }}">
                    <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                    Continue with Google
                </a>

                <div class="lg-divider">or sign in with email</div>

                <form method="POST" action="{{ route('login.submit') }}" id="login-form" novalidate>
                    @csrf
                    <div class="lg-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input class="lg-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder=" " autocomplete="email" required autofocus @error('email') aria-invalid="true" @enderror>
                        <label for="email">Email address</label>
                    </div>

                    <div class="lg-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <input class="lg-input has-toggle" id="password" name="password" type="password" placeholder=" " autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
                        <label for="password">Password</label>
                        <button class="password-toggle" id="toggle-password" type="button" aria-label="Show password" title="Show password" aria-pressed="false" aria-controls="password"><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18.8 18.8 0 0 1-3.1 3.6"></path><path d="M6.1 6.1C3.5 7.8 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.1-.8"></path></svg><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button>
                        <p class="lg-caps" id="caps-warning" role="status">⇪ Caps Lock is on</p>
                    </div>

                    <div class="lg-options">
                        <label class="lg-check"><input type="checkbox" name="remember" @checked(old('remember'))><span class="lg-check-box"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span> Keep me signed in</label>
                        <a class="lg-link" href="{{ route('password.request') }}">Forgot password?</a>
                    </div>

                    <button class="lg-submit" id="login-submit" type="submit">
                        <span>Sign in securely</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        <span class="lg-spinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="lx-secure">
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>Two-factor ready</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Encrypted session</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Auto sign-out</span>
                </div>

                <p class="lx-legal">By signing in you agree to our <a href="{{ route('terms') }}">Terms of Service</a> and <a href="{{ route('privacy') }}">Privacy Policy</a>.</p>
            </div>
        </section>
    </div>
</main>
<script>
    (() => {
        const password = document.getElementById('password');
        const toggle = document.getElementById('toggle-password');
        const capsWarning = document.getElementById('caps-warning');
        const form = document.getElementById('login-form');
        const submit = document.getElementById('login-submit');
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        toggle?.addEventListener('click', function () {
            const showing = password.type === 'text';
            password.type = showing ? 'password' : 'text';
            this.classList.toggle('is-visible', !showing);
            this.setAttribute('aria-pressed', String(!showing));
            this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            this.setAttribute('title', showing ? 'Show password' : 'Hide password');
            password.focus();
        });

        ['keydown', 'keyup'].forEach((eventName) => password?.addEventListener(eventName, (event) => {
            capsWarning.classList.toggle('is-on', event.getModifierState && event.getModifierState('CapsLock'));
        }));
        password?.addEventListener('blur', () => capsWarning.classList.remove('is-on'));

        form?.addEventListener('submit', (event) => {
            if (! form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }
            submit.classList.add('is-loading');
            submit.setAttribute('aria-busy', 'true');
        });

        const hour = new Date().getHours();
        document.getElementById('lx-greeting').textContent = hour < 12 ? 'Good morning' : (hour < 18 ? 'Good afternoon' : 'Good evening');

        const typed = document.getElementById('lx-typed');
        if (typed && ! prefersReducedMotion) {
            const lines = typed.dataset.lines.split('|');
            let lineIndex = 0;
            const type = (text, position = 0) => {
                typed.textContent = text.slice(0, position);
                if (position < text.length) { setTimeout(() => type(text, position + 1), 38); return; }
                setTimeout(() => { lineIndex = (lineIndex + 1) % lines.length; type(lines[lineIndex]); }, 2600);
            };
            setTimeout(() => type(lines[0]), 600);
        }

        const brand = document.querySelector('.lg-brand');
        const cards = document.querySelectorAll('[data-tilt]');
        if (brand && ! prefersReducedMotion && window.matchMedia('(pointer: fine)').matches) {
            brand.addEventListener('pointermove', (event) => {
                const bounds = brand.getBoundingClientRect();
                const x = (event.clientX - bounds.left) / bounds.width - .5;
                const y = (event.clientY - bounds.top) / bounds.height - .5;
                cards.forEach((card, index) => {
                    const depth = 6 + index * 2;
                    card.style.transform = `rotateY(${x * depth}deg) rotateX(${y * -depth}deg) translateZ(0)`;
                });
            });
            brand.addEventListener('pointerleave', () => cards.forEach((card) => { card.style.transform = ''; }));
        }
    })();
</script>
@endsection
