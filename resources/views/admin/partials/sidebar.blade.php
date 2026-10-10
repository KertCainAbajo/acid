@php
    $admin = auth()->user();
    $groups = [
        'Monitor' => [
            ['route' => 'admin.dashboard', 'label' => 'Overview', 'hint' => 'Platform at a glance', 'keywords' => 'dashboard home stats charts', 'badge' => null, 'icon' => '<path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-12h6V4h-6v4Z"/>'],
            ['route' => 'admin.security', 'label' => 'Security center', 'hint' => 'Threats, locks & 2FA', 'keywords' => 'security failed login locked two-factor 2fa suspicious ip attack', 'badge' => $sidebar['locked'] ? ['value' => $sidebar['locked'].' locked', 'tone' => 'alert'] : ($sidebar['failedToday'] ? ['value' => $sidebar['failedToday'], 'tone' => 'warn', 'title' => $sidebar['failedToday'].' failed sign-ins in 24h'] : null), 'icon' => '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>'],
            ['route' => 'admin.activity', 'label' => 'System activity', 'hint' => 'Search the audit trail', 'keywords' => 'audit log events sign-ins history', 'badge' => $sidebar['eventsToday'] ? ['value' => $sidebar['eventsToday'].' today', 'tone' => 'live'] : null, 'icon' => '<path d="M3 12h4l2-5 4 10 2-5h6"/>'],
        ],
        'Manage' => [
            ['route' => 'admin.patients', 'label' => 'Patients', 'hint' => 'Search & manage accounts', 'keywords' => 'users accounts deactivate delete export', 'badge' => ['value' => $sidebar['patients'], 'tone' => $sidebar['deactivated'] ? 'warn' : 'plain', 'title' => $sidebar['deactivated'].' deactivated'], 'icon' => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20a6 6 0 0 1 12 0M14 20a4 4 0 0 1 7 0"/>'],
            ['route' => 'admin.food-guidance', 'label' => 'Food guidance', 'hint' => 'Gentle foods & triggers', 'keywords' => 'food categories safe avoid triggers recommendations', 'badge' => ['value' => $sidebar['foods'], 'tone' => 'plain'], 'icon' => '<path d="M12 3c1.6 2.2 5 4.5 5 8.4A5 5 0 0 1 7 11.4C7 8.1 9.6 6 12 3Z"/><path d="M12 21a5 5 0 0 0 4.8-6.2c-1.4 1.2-2.7 1.8-4 1.8-1.5 0-2.6-.6-3.6-1.5A5 5 0 0 0 12 21Z"/>'],
        ],
        'Account' => [
            ['route' => 'admin.settings', 'label' => 'Account settings', 'hint' => 'Email & password', 'keywords' => 'profile password email security', 'badge' => $sidebar['securityReminder'] ? ['value' => '!', 'tone' => 'alert', 'title' => $sidebar['securityReminder']] : null, 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>'],
        ],
    ];
@endphp
<script>try { if (localStorage.getItem('admin-sidebar') === 'collapsed') { document.documentElement.classList.add('as-collapsed'); } } catch (error) {}</script>
<style>
    aside.sidebar.as { width:272px; padding:16px 14px; color:#cfe9df; background:radial-gradient(120% 60% at 0% 0%,#10b98126 0,transparent 60%),linear-gradient(180deg,#0b2e25 0%,#082019 100%); border-right:1px solid #0f3a2f; backdrop-filter:none; scrollbar-color:#1f4a3e transparent; transition:width .25s cubic-bezier(.2,.8,.2,1); }
    .as-top { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:14px; }
    .as-brand { display:flex; align-items:center; gap:10px; min-width:0; }
    .as-brand img { width:36px; height:36px; flex:none; padding:4px; border-radius:11px; background:#fff; object-fit:contain; box-shadow:0 0 0 3px #ffffff14; }
    .as-brand strong { display:block; color:#fff; font-size:14px; letter-spacing:-.01em; white-space:nowrap; }
    .as-brand small { display:inline-flex; align-items:center; gap:5px; margin-top:2px; color:#86efac; font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; white-space:nowrap; }
    .as-brand small i { width:6px; height:6px; border-radius:50%; background:#34d399; box-shadow:0 0 0 3px #34d39933; }
    .as-collapse { width:32px; height:32px; flex:none; display:grid; place-items:center; padding:0; border:1px solid #ffffff1a; border-radius:9px; color:#9cc5b8; background:#ffffff0d; cursor:pointer; transition:background .2s ease,color .2s ease; }
    .as-collapse:hover { color:#fff; background:#ffffff1f; }
    .as-collapse svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; transition:transform .25s ease; }
    .as-search { position:relative; margin-bottom:6px; }
    .as-search svg { position:absolute; top:50%; left:11px; width:15px; height:15px; color:#6b968a; transform:translateY(-50%); fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; pointer-events:none; }
    .as-search input { width:100%; height:38px; padding:0 40px 0 33px; border:1px solid #ffffff17; border-radius:11px; color:#fff; background:#ffffff0d; outline:none; font:inherit; font-size:12.5px; transition:border-color .2s ease,background .2s ease; }
    .as-search input::placeholder { color:#6b968a; }
    .as-search input:focus { border-color:#34d39980; background:#ffffff14; }
    .as-search kbd { position:absolute; top:50%; right:8px; padding:2px 6px; border:1px solid #ffffff1f; border-radius:6px; color:#86b3a5; font:600 10.5px ui-monospace,monospace; transform:translateY(-50%); }
    .as-label { display:flex; align-items:center; gap:8px; margin:16px 10px 6px; color:#5f8f81; font-size:10.5px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; white-space:nowrap; }
    .as-label:after { content:""; flex:1; height:1px; background:#ffffff12; }
    .as-nav { display:grid; gap:3px; }
    .as-link { position:relative; display:flex; align-items:center; gap:11px; min-height:48px; padding:7px 10px; border-radius:13px; color:#b7d6cb; transition:background .2s ease,color .2s ease; }
    .as-link:hover { color:#fff; background:#ffffff0f; }
    .as-link:focus-visible { outline:2px solid #34d399; outline-offset:1px; }
    .as-link.current { color:#fff; background:linear-gradient(90deg,#10b98133,#10b98112); box-shadow:inset 0 0 0 1px #34d39933; }
    .as-link.current:before { content:""; position:absolute; left:-14px; top:50%; width:4px; height:26px; border-radius:0 4px 4px 0; background:#34d399; box-shadow:0 0 12px #34d399; transform:translateY(-50%); }
    .as-icon { width:34px; height:34px; flex:none; display:grid; place-items:center; border-radius:10px; color:#86b3a5; background:#ffffff0d; transition:background .2s ease,color .2s ease; }
    .as-icon svg { width:17px; height:17px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
    .as-link:hover .as-icon { color:#fff; background:#ffffff1a; }
    .as-link.current .as-icon { color:#062b20; background:linear-gradient(135deg,#6ee7b7,#34d399); box-shadow:0 6px 16px -6px #34d399; }
    .as-copy { display:flex; min-width:0; flex:1; flex-direction:column; gap:1px; }
    .as-copy strong { overflow:hidden; font-size:13px; font-weight:650; text-overflow:ellipsis; white-space:nowrap; }
    .as-copy small { overflow:hidden; color:#6b968a; font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
    .as-link.current .as-copy small { color:#a7f3d0; }
    .as-badge { flex:none; min-width:22px; padding:2px 7px; border-radius:999px; color:#cfe9df; background:#ffffff14; font-size:10.5px; font-weight:800; text-align:center; white-space:nowrap; }
    .as-badge.is-warn { color:#fde68a; background:#f59e0b26; }
    .as-badge.is-alert { color:#fff; background:#dc2626; box-shadow:0 0 0 3px #dc262633; }
    .as-badge.is-live { color:#062b20; background:#6ee7b7; }
    .as-empty { display:none; margin:10px; color:#6b968a; font-size:12px; }
    .as-reminder { display:flex; gap:10px; margin:16px 2px 0; padding:12px; border:1px solid #f59e0b40; border-radius:14px; color:#fde68a; background:#f59e0b14; font-size:12px; line-height:1.45; }
    .as-reminder svg { width:16px; height:16px; flex:none; margin-top:1px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .as-reminder a { display:inline-block; margin-top:4px; color:#fff; font-weight:700; text-decoration:underline; text-underline-offset:3px; }
    .as-footer { margin-top:auto; padding-top:16px; }
    .as-status { display:flex; align-items:center; gap:8px; margin:0 4px 10px; color:#6b968a; font-size:11.5px; white-space:nowrap; }
    .as-status i { width:7px; height:7px; flex:none; border-radius:50%; background:#34d399; animation:asPulse 2.4s ease-in-out infinite; }
    .as-profile { display:flex; align-items:center; gap:10px; padding:10px; border:1px solid #ffffff14; border-radius:15px; background:#ffffff0a; }
    .as-avatar { width:36px; height:36px; flex:none; display:grid; place-items:center; border-radius:11px; color:#062b20; background:linear-gradient(135deg,#a7f3d0,#34d399); font-size:14px; font-weight:800; }
    .as-who { min-width:0; flex:1; }
    .as-who strong { display:block; overflow:hidden; color:#fff; font-size:12.5px; text-overflow:ellipsis; white-space:nowrap; }
    .as-who small { display:block; overflow:hidden; color:#6b968a; font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
    .as-profile form { margin:0; }
    .as-out { width:32px; height:32px; display:grid; place-items:center; padding:0; border:0; border-radius:9px; color:#9cc5b8; background:transparent; cursor:pointer; }
    .as-out:hover { color:#fecaca; background:#dc262626; }
    .as-out svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    @keyframes asPulse { 0%,100% { box-shadow:0 0 0 3px #34d39940; } 50% { box-shadow:0 0 0 6px #34d39910; } }

    /* Collapsed (icon-only) mode */
    html.as-collapsed aside.sidebar.as { width:84px; }
    html.as-collapsed .as-brand > span, html.as-collapsed .as-search, html.as-collapsed .as-copy, html.as-collapsed .as-reminder, html.as-collapsed .as-status span, html.as-collapsed .as-who, html.as-collapsed .as-profile form, html.as-collapsed .as-label { display:none; }
    html.as-collapsed .as-top { flex-direction:column; }
    html.as-collapsed .as-collapse svg { transform:rotate(180deg); }
    html.as-collapsed .as-link { justify-content:center; padding:7px; }
    html.as-collapsed .as-nav + .as-nav, html.as-collapsed .as-group + .as-group { margin-top:10px; padding-top:10px; border-top:1px solid #ffffff12; }
    html.as-collapsed .as-badge { position:absolute; top:2px; right:2px; min-width:0; padding:1px 5px; font-size:9px; }
    html.as-collapsed .as-badge.is-live { width:9px; height:9px; padding:0; font-size:0; }
    html.as-collapsed .as-profile { justify-content:center; padding:6px; border:0; background:transparent; }
    html.as-collapsed .as-status { justify-content:center; }
    @media (max-width:1000px) { aside.sidebar.as { width:84px; } aside.sidebar.as .as-brand > span, aside.sidebar.as .as-search, aside.sidebar.as .as-copy, aside.sidebar.as .as-reminder, aside.sidebar.as .as-status span, aside.sidebar.as .as-who, aside.sidebar.as .as-profile form, aside.sidebar.as .as-label, aside.sidebar.as .as-collapse { display:none; } aside.sidebar.as .as-link { justify-content:center; } aside.sidebar.as .as-badge { position:absolute; top:2px; right:2px; padding:1px 5px; font-size:9px; } aside.sidebar.as .as-profile { justify-content:center; border:0; background:transparent; } }
</style>

<aside class="sidebar as" aria-label="Admin navigation">
    <div class="as-top">
        <a class="as-brand" href="{{ route('admin.dashboard') }}" title="RefluxCare admin">
            <img src="{{ asset('images/logo1.png') }}" alt="">
            <span><strong>Admin console</strong><small><i></i> {{ app()->environment('production') ? 'Production' : ucfirst(app()->environment()) }}</small></span>
        </a>
        <button class="as-collapse" type="button" id="as-collapse" aria-label="Collapse sidebar" title="Collapse sidebar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
    </div>

    <label class="as-search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" id="as-search" placeholder="Jump to…" aria-label="Jump to an admin page" autocomplete="off">
        <kbd>/</kbd>
    </label>

    @foreach ($groups as $groupLabel => $links)
        <div class="as-group">
            <div class="as-label">{{ $groupLabel }}</div>
            <nav class="as-nav" aria-label="{{ $groupLabel }}">
                @foreach ($links as $link)
                    @php($isCurrent = request()->routeIs($link['route'].'*'))
                    <a class="as-link {{ $isCurrent ? 'current' : '' }}" href="{{ route($link['route']) }}" title="{{ $link['label'] }}" data-keywords="{{ strtolower($link['label'].' '.$link['hint'].' '.$link['keywords']) }}" @if ($isCurrent) aria-current="page" @endif>
                        <span class="as-icon"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $link['icon'] !!}</svg></span>
                        <span class="as-copy"><strong>{{ $link['label'] }}</strong><small>{{ $link['hint'] }}</small></span>
                        @if ($link['badge'])
                            <span class="as-badge is-{{ $link['badge']['tone'] }}" @isset($link['badge']['title']) title="{{ $link['badge']['title'] }}" @endisset>{{ $link['badge']['value'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
    @endforeach
    <p class="as-empty" id="as-empty">No matching pages.</p>

    @if ($sidebar['securityReminder'])
        <div class="as-reminder" role="note">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="M12 9v4M12 16h.01"/></svg>
            <div><strong>Secure this account</strong><br>{{ $sidebar['securityReminder'] }}.<br><a href="{{ route('admin.settings') }}">Open settings →</a></div>
        </div>
    @endif

    <div class="as-footer">
        <div class="as-status" title="{{ $sidebar['eventsToday'] }} events recorded today"><i></i><span>All systems normal · {{ $sidebar['eventsToday'] }} events today</span></div>
        <div class="as-profile">
            <a class="as-avatar" href="{{ route('admin.settings') }}" title="{{ $admin->name }} · Account settings">{{ strtoupper(substr($admin->name, 0, 1)) }}</a>
            <div class="as-who"><strong>{{ $admin->name }}</strong><small>{{ $admin->email }}</small></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="as-out" type="submit" aria-label="Log out" title="Log out"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg></button>
            </form>
        </div>
    </div>
</aside>

<script>
    (() => {
        const root = document.documentElement;
        const collapse = document.getElementById('as-collapse');
        const search = document.getElementById('as-search');
        const links = [...document.querySelectorAll('.as-link')];
        const empty = document.getElementById('as-empty');

        const syncCollapseLabel = () => {
            const collapsed = root.classList.contains('as-collapsed');
            collapse.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            collapse.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        };
        collapse.addEventListener('click', () => {
            const collapsed = root.classList.toggle('as-collapsed');
            try { localStorage.setItem('admin-sidebar', collapsed ? 'collapsed' : 'expanded'); } catch (error) {}
            syncCollapseLabel();
        });
        syncCollapseLabel();

        const filter = () => {
            const term = search.value.trim().toLowerCase();
            let visible = 0;
            links.forEach((link) => {
                const match = term === '' || link.dataset.keywords.includes(term);
                link.hidden = ! match;
                visible += match ? 1 : 0;
            });
            document.querySelectorAll('.as-group').forEach((group) => { group.hidden = ! [...group.querySelectorAll('.as-link')].some((link) => ! link.hidden); });
            empty.style.display = visible === 0 ? 'block' : 'none';
        };
        search.addEventListener('input', filter);
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') { const first = links.find((link) => ! link.hidden); if (first) { window.location.href = first.href; } }
            if (event.key === 'Escape') { search.value = ''; filter(); search.blur(); }
        });
        document.addEventListener('keydown', (event) => {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
            if (event.key === '/' && ! typing && ! event.ctrlKey && ! event.metaKey) {
                event.preventDefault();
                if (root.classList.contains('as-collapsed')) { root.classList.remove('as-collapsed'); syncCollapseLabel(); }
                search.focus();
            }
        });
    })();
</script>
