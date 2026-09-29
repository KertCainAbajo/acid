<aside class="sidebar">
    <div class="sidebar-heading">
        <span class="sidebar-heading-icon"><img src="{{ asset('images/logo1.png') }}" alt=""></span>
        <span class="sidebar-heading-copy"><strong>Admin console</strong><small>MANAGE REFLUXCARE</small></span>
    </div>

    <div class="side-label">Administration</div>
    <nav class="sidebar-nav" aria-label="Admin pages">
        <a class="side-link {{ request()->routeIs('admin.dashboard') ? 'current' : '' }}" href="{{ route('admin.dashboard') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-12h6V4h-6v4Z"></path></svg></span><span class="side-copy"><strong>Overview</strong><small>System activity at a glance</small></span>
        </a>
        <a class="side-link {{ request()->routeIs('admin.patients') ? 'current' : '' }}" href="{{ route('admin.patients') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><circle cx="17" cy="9" r="2.5"></circle><path d="M3 20a6 6 0 0 1 12 0M14 20a4 4 0 0 1 7 0"></path></svg></span><span class="side-copy"><strong>Recent patients</strong><small>Registered accounts</small></span>
        </a>
        <a class="side-link {{ request()->routeIs('admin.activity') ? 'current' : '' }}" href="{{ route('admin.activity') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17h4v3H4v-3Zm6-6h4v9h-4v-9Zm6-7h4v16h-4V4Z"></path></svg></span><span class="side-copy"><strong>System activity</strong><small>Administrative actions</small></span>
        </a>
    </nav>

    <div class="sidebar-footer"><span class="sidebar-footer-dot"></span><div><strong>Admin access</strong><small>Protected administration area.</small></div></div>
</aside>
