@extends('layouts.app')

@section('content')
<style>
    .lgl { min-height:100vh; color:var(--ink); }
    .lgl-nav { position:sticky; top:0; z-index:20; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px clamp(16px,4vw,48px); background:#ffffffd9; border-bottom:1px solid #e5ece980; backdrop-filter:saturate(1.6) blur(16px); }
    .lgl-logo { display:inline-flex; align-items:center; gap:10px; }
    .lgl-logo img { width:38px; height:38px; padding:4px; object-fit:contain; border-radius:12px; background:#fff; box-shadow:0 0 0 1px #d1fae5,0 6px 14px -6px #0a5a4059; }
    .lgl-logo strong { display:block; font-size:15px; font-weight:800; letter-spacing:-.02em; }
    .lgl-logo small { display:block; color:var(--green); font-size:9.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
    .lgl-tabs { display:flex; gap:4px; padding:4px; border:1px solid var(--line); border-radius:12px; background:#f8fafa; }
    .lgl-tabs a { padding:7px 14px; border-radius:9px; color:#475569; font-size:13px; font-weight:600; transition:background .2s ease,color .2s ease; }
    .lgl-tabs a:hover { color:var(--ink); background:#fff; }
    .lgl-tabs a.is-active { color:#fff; background:var(--green); box-shadow:0 6px 14px -6px #0a5a40aa; }
    .lgl-back { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border:1px solid var(--line); border-radius:999px; background:#fff; font-size:13px; font-weight:600; transition:border-color .2s ease,box-shadow .2s ease; }
    .lgl-back:hover { border-color:#a7f3d0; box-shadow:0 6px 14px -8px #0a5a4059; }
    .lgl-back svg, .lgl-btn svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .lgl-wrap { width:min(1180px,100%); margin:0 auto; padding:28px clamp(16px,4vw,40px) 72px; }
    .lgl-hero { position:relative; overflow:hidden; isolation:isolate; padding:clamp(28px,4vw,44px); border-radius:26px; color:#ecfdf5; background:linear-gradient(150deg,#0d4637 0%,#0a3229 55%,#071f1a 100%); box-shadow:0 30px 60px -30px #0a2e26cc; }
    .lgl-hero:before { content:""; position:absolute; inset:0; z-index:-1; background-image:linear-gradient(#ffffff0d 1px,transparent 1px),linear-gradient(90deg,#ffffff0d 1px,transparent 1px); background-size:36px 36px; mask-image:radial-gradient(ellipse at 25% 40%,#000 0,transparent 70%); }
    .lgl-hero:after { content:""; position:absolute; z-index:-1; top:-120px; right:10%; width:320px; height:320px; border-radius:50%; background:#10b98159; filter:blur(50px); }
    .lgl-kicker { display:inline-flex; align-items:center; gap:8px; padding:5px 11px 5px 9px; border:1px solid #6ee7b733; border-radius:999px; color:#a7f3d0; background:#ffffff0f; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .lgl-kicker i { width:7px; height:7px; border-radius:50%; background:#34d399; }
    .lgl-hero h1 { margin:16px 0 10px; color:#fff; font-size:clamp(30px,3.6vw,46px); font-weight:800; line-height:1.05; letter-spacing:-.04em; }
    .lgl-hero p { max-width:62ch; margin:0; color:#b9d6cc; font-size:15px; line-height:1.65; }
    .lgl-meta { display:flex; flex-wrap:wrap; gap:8px; margin-top:20px; }
    .lgl-meta span { display:inline-flex; align-items:center; gap:7px; padding:6px 12px; border:1px solid #ffffff1f; border-radius:999px; color:#d1fae5; background:#ffffff12; font-size:12.5px; }
    .lgl-meta svg { width:14px; height:14px; fill:none; stroke:#6ee7b7; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .lgl-glance { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-top:20px; }
    .lgl-glance div { padding:16px; border:1px solid #fff; border-radius:18px; background:#ffffffd9; box-shadow:var(--shadow-soft); }
    .lgl-glance span { width:36px; height:36px; display:grid; place-items:center; margin-bottom:10px; border-radius:11px; color:var(--green); background:#ecfdf5; }
    .lgl-glance svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .lgl-glance strong { display:block; font-size:14px; }
    .lgl-glance small { display:block; margin-top:3px; color:var(--muted); font-size:12.5px; line-height:1.5; }
    .lgl-body { display:grid; grid-template-columns:260px minmax(0,1fr); gap:28px; align-items:start; margin-top:24px; }
    .lgl-toc { position:sticky; top:92px; padding:16px; border:1px solid #fff; border-radius:18px; background:#ffffffd9; box-shadow:var(--shadow-soft); }
    .lgl-toc h2 { margin:0 0 10px 8px; color:#94a3b8; font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
    .lgl-toc ol { display:grid; gap:2px; margin:0; padding:0; list-style:none; counter-reset:toc; }
    .lgl-toc ol a { position:relative; display:flex; gap:10px; padding:8px 10px; border-radius:10px; color:#475569; font-size:13px; line-height:1.35; transition:background .2s ease,color .2s ease; }
    .lgl-toc ol a:before { counter-increment:toc; content:counter(toc); min-width:20px; height:20px; display:grid; place-items:center; border-radius:6px; color:#64748b; background:#f1f5f9; font-size:11px; font-weight:700; }
    .lgl-toc ol a:hover { color:var(--ink); background:#f8fafa; }
    .lgl-toc ol a.is-active { color:var(--green-deep); background:#ecfdf5; font-weight:600; }
    .lgl-toc ol a.is-active:before { color:#fff; background:var(--green); }
    .lgl-actions { display:flex; gap:8px; margin-top:14px; padding-top:14px; border-top:1px solid #eef2f1; }
    .lgl-btn { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:8px; border:1px solid var(--line); border-radius:10px; color:#334155; background:#fff; font-size:12.5px; font-weight:700; cursor:pointer; }
    .lgl-btn:hover { border-color:#a7f3d0; background:#f0fdf4; }
    .lgl-content { display:grid; gap:16px; counter-reset:section; }
    .lgl-section { scroll-margin-top:92px; padding:clamp(20px,3vw,30px); border:1px solid #fff; border-radius:20px; background:#ffffffe6; box-shadow:var(--shadow-soft); }
    .lgl-section h2 { display:flex; align-items:center; gap:12px; margin:0 0 12px; font-size:19px; font-weight:800; letter-spacing:-.02em; }
    .lgl-section h2:before { counter-increment:section; content:counter(section); width:30px; height:30px; flex:none; display:grid; place-items:center; border-radius:10px; color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); font-size:13px; }
    .lgl-section h3 { margin:18px 0 6px; font-size:14.5px; }
    .lgl-section p, .lgl-section li { color:#475569; font-size:14.5px; line-height:1.75; }
    .lgl-section p { margin:0 0 10px; }
    .lgl-section ul { display:grid; gap:6px; margin:6px 0 10px; padding-left:20px; }
    .lgl-section strong { color:var(--ink); }
    .lgl-section a { color:var(--green); font-weight:600; text-decoration:underline; text-underline-offset:3px; }
    .lgl-table { width:100%; margin:8px 0 6px; border-collapse:collapse; font-size:13.5px; }
    .lgl-table th { padding:10px 12px; color:#64748b; background:#f8fafa; font-size:11.5px; letter-spacing:.06em; text-align:left; text-transform:uppercase; }
    .lgl-table td { padding:11px 12px; border-top:1px solid #eef2f1; color:#475569; vertical-align:top; line-height:1.55; }
    .lgl-table td:first-child { color:var(--ink); font-weight:600; white-space:nowrap; }
    .lgl-note { display:flex; gap:12px; margin:10px 0; padding:14px 16px; border-radius:14px; color:#334155; background:var(--note-bg,#f0fdf4); box-shadow:inset 0 0 0 1px var(--note-line,#bbf7d0); font-size:14px; line-height:1.6; }
    .lgl-note svg { flex:none; width:20px; height:20px; margin-top:1px; fill:none; stroke:var(--note-icon,#059669); stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .lgl-note.is-warning { --note-bg:#fffbeb; --note-line:#fde68a; --note-icon:#b45309; }
    .lgl-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-top:8px; padding:18px 4px 0; color:#94a3b8; font-size:12.5px; }
    .lgl-foot a { color:var(--green); font-weight:600; }
    .lgl-top { position:fixed; right:20px; bottom:20px; z-index:20; width:44px; height:44px; display:grid; place-items:center; border:0; border-radius:14px; color:#fff; background:var(--green); box-shadow:0 12px 24px -10px #0a5a40cc; cursor:pointer; opacity:0; pointer-events:none; transform:translateY(10px); transition:opacity .2s ease,transform .2s ease; }
    .lgl-top.is-on { opacity:1; pointer-events:auto; transform:none; }
    .lgl-top svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:2.4; stroke-linecap:round; stroke-linejoin:round; }
    @media (max-width:1000px) { .lgl-body { grid-template-columns:1fr; } .lgl-toc { position:static; } .lgl-glance { grid-template-columns:1fr 1fr; } }
    @media (max-width:640px) { .lgl-nav { flex-wrap:wrap; } .lgl-logo small { display:none; } .lgl-glance { grid-template-columns:1fr; } .lgl-table td:first-child { white-space:normal; } }
    @media print { .lgl-nav, .lgl-toc, .lgl-top, .lgl-foot { display:none !important; } .lgl-body { display:block; } .lgl-hero { color:#000; background:none; box-shadow:none; padding:0; } .lgl-hero h1 { color:#000; } .lgl-hero p, .lgl-meta span { color:#333; } .lgl-section { box-shadow:none; border:1px solid #ddd; break-inside:avoid; } }
</style>

<div class="lgl">
    <header class="lgl-nav">
        <a class="lgl-logo" href="{{ route('home') }}">
            <img src="{{ asset('images/logo1.png') }}" alt="">
            <span><strong>RefluxCare</strong><small>Personal health workspace</small></span>
        </a>
        <nav class="lgl-tabs" aria-label="Legal documents">
            <a href="{{ route('privacy') }}" class="{{ request()->routeIs('privacy') ? 'is-active' : '' }}" @if (request()->routeIs('privacy')) aria-current="page" @endif>Privacy Policy</a>
            <a href="{{ route('terms') }}" class="{{ request()->routeIs('terms') ? 'is-active' : '' }}" @if (request()->routeIs('terms')) aria-current="page" @endif>Terms of Service</a>
        </nav>
        <a class="lgl-back" href="{{ auth()->check() ? route('dashboard') : route('login') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg> {{ auth()->check() ? 'Back to dashboard' : 'Back to sign in' }}</a>
    </header>

    <div class="lgl-wrap">
        <section class="lgl-hero">
            <span class="lgl-kicker"><i></i> @yield('legal-kicker')</span>
            <h1>@yield('legal-title')</h1>
            <p>@yield('legal-intro')</p>
            <div class="lgl-meta">
                <span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/></svg> Last updated @yield('legal-updated')</span>
                <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> <span id="reading-time" style="padding:0;border:0;background:none">About 5 min read</span></span>
                <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/></svg> Applies to the RefluxCare web app and API</span>
            </div>
        </section>

        <div class="lgl-glance">@yield('legal-glance')</div>

        <div class="lgl-body">
            <aside class="lgl-toc" aria-label="On this page">
                <h2>On this page</h2>
                <ol id="legal-toc"></ol>
                <div class="lgl-actions">
                    <button class="lgl-btn" type="button" onclick="window.print()"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg> Print</button>
                    <a class="lgl-btn" href="{{ request()->routeIs('privacy') ? route('terms') : route('privacy') }}">{{ request()->routeIs('privacy') ? 'Terms' : 'Privacy' }} →</a>
                </div>
            </aside>

            <article class="lgl-content" id="legal-content">
                @yield('legal-body')
                <div class="lgl-foot">
                    <span>© {{ now()->year }} RefluxCare. This document is provided for transparency and is not legal or medical advice.</span>
                    <a href="#top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' }); return false;">Back to top ↑</a>
                </div>
            </article>
        </div>
    </div>

    <button class="lgl-top" id="back-to-top" type="button" aria-label="Back to top"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>
</div>

<script>
    (() => {
        const sections = [...document.querySelectorAll('.lgl-section[id]')];
        const toc = document.getElementById('legal-toc');
        sections.forEach((section) => {
            const item = document.createElement('li');
            const link = document.createElement('a');
            link.href = `#${section.id}`;
            link.textContent = section.querySelector('h2').textContent;
            item.appendChild(link);
            toc.appendChild(item);
        });

        const words = document.getElementById('legal-content').innerText.split(/\s+/).length;
        document.getElementById('reading-time').textContent = `About ${Math.max(1, Math.round(words / 220))} min read`;

        const links = [...toc.querySelectorAll('a')];
        const setActive = (id) => links.forEach((link) => link.classList.toggle('is-active', link.getAttribute('href') === `#${id}`));
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
                if (visible) { setActive(visible.target.id); }
            }, { rootMargin: '-90px 0px -60% 0px' });
            sections.forEach((section) => observer.observe(section));
        }
        if (sections[0]) { setActive(location.hash ? location.hash.slice(1) : sections[0].id); }

        const topButton = document.getElementById('back-to-top');
        window.addEventListener('scroll', () => topButton.classList.toggle('is-on', window.scrollY > 600), { passive: true });
        topButton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    })();
</script>
@endsection
