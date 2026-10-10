<header class="topbar">
    <a class="brand" href="{{ route('admin.dashboard') }}">
        <img class="brand-logo" src="{{ asset('images/logo1.png') }}" alt="RefluxCare logo">
        <span class="brand-copy"><span>RefluxCare</span><small>Admin Portal</small></span>
    </a>
    <div class="top-actions">
        <a class="topbar-user" href="{{ route('admin.settings') }}" title="Account settings">
            <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="topbar-user-copy"><strong>{{ auth()->user()->name }}</strong><small>Administrator · Settings</small></span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="pill topbar-logout" type="submit" title="Log out">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"></path></svg>
                <span>Log out</span>
            </button>
        </form>
    </div>
</header>
