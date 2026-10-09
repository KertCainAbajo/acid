@php($topbarUser = auth()->user())

<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}">
        <img class="brand-logo" src="{{ asset('images/logo1.png') }}" alt="RefluxCare logo">
        <span class="brand-copy"><span>RefluxCare</span><small>Patient Portal</small></span>
    </a>

    <a class="topbar-search" href="{{ route('logs.search') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
        <span>Search your food, symptom &amp; sleep logs…</span>
        <kbd>/</kbd>
    </a>

    <div class="top-actions">
        <span class="pill topbar-date">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"></rect><path d="M3 10h18M8 3v4M16 3v4"></path></svg>
            Today, {{ now()->format('M d') }}
        </span>
        <span class="topbar-user">
            <span class="avatar">{{ strtoupper(substr($topbarUser->name, 0, 1)) }}</span>
            <span class="topbar-user-copy"><strong>{{ $topbarUser->name }}</strong><small>Patient</small></span>
        </span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="pill topbar-logout" type="submit" title="Log out">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"></path></svg>
                <span>Log out</span>
            </button>
        </form>
    </div>
</header>

<script>
    document.addEventListener('keydown', (event) => {
        const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) || document.activeElement?.isContentEditable;
        if (event.key === '/' && ! typing && ! event.metaKey && ! event.ctrlKey) {
            event.preventDefault();
            window.location.href = @json(route('logs.search'));
        }
    });
</script>
