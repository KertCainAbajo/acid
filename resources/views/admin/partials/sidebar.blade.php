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

    <div class="side-label">Your account</div>
    <nav class="sidebar-nav" aria-label="Account">
        <a class="side-link {{ request()->routeIs('admin.settings') ? 'current' : '' }}" href="{{ route('admin.settings') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"></path></svg></span><span class="side-copy"><strong>Account settings</strong><small>Email &amp; password</small></span>
        </a>
    </nav>

    <div class="sidebar-footer"><span class="sidebar-footer-dot"></span><div><strong>Admin access</strong><small>Protected administration area.</small></div></div>
</aside>
