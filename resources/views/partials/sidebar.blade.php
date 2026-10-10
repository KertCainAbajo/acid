@php($activeSection = $activeSection ?? 'dashboard')

<aside class="sidebar">
    <div class="sidebar-heading">
        <span class="sidebar-heading-icon"><img src="{{ asset('images/logo1.png') }}" alt=""></span>
        <span class="sidebar-heading-copy"><strong>Patient workspace</strong><small>YOUR HEALTH, ORGANIZED</small></span>
    </div>

    <div class="side-label">Track your health</div>
    <nav class="sidebar-nav" aria-label="Patient tools">
        <a class="side-link {{ $activeSection === 'dashboard' ? 'current' : '' }}" href="{{ route('dashboard') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-12h6V4h-6v4Z"></path></svg></span>
            <span class="side-copy"><strong>Dashboard</strong><small>Overview &amp; daily status</small></span><span class="side-arrow">→</span>
        </a>
        <a class="side-link {{ $activeSection === 'food' ? 'current' : '' }}" href="{{ route('food.log') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v7M4.5 3v7a2.5 2.5 0 0 0 5 0V3M7 10v11M16 3v18M16 3c2.3 1.5 3.5 3.8 3.5 6.5S18.3 14 16 14"></path></svg></span>
            <span class="side-copy"><strong>Food Intake</strong><small>Log meals &amp; drinks</small></span><span class="side-arrow">→</span>
        </a>
        <a class="side-link {{ $activeSection === 'symptom' ? 'current' : '' }}" href="{{ route('symptom.log') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"></path></svg></span>
            <span class="side-copy"><strong>Symptom Tracker</strong><small>Record how you feel</small></span><span class="side-arrow">→</span>
        </a>
        <a class="side-link {{ $activeSection === 'sleep' ? 'current' : '' }}" href="{{ route('sleep.log') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 15.5A7.5 7.5 0 0 1 8.5 5 7.5 7.5 0 1 0 19 15.5Z"></path><path d="M17 4v3M15.5 5.5h3"></path></svg></span>
            <span class="side-copy"><strong>Sleep &amp; Stress</strong><small>Track rest &amp; pressure</small></span><span class="side-arrow">→</span>
        </a>
    </nav>

    <div class="side-label">Understand patterns</div>
    <nav class="sidebar-nav" aria-label="Insights and history">
        <a class="side-link {{ $activeSection === 'insights' ? 'current' : '' }}" href="{{ route('insights') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17h4v3H4v-3Zm6-6h4v9h-4v-9Zm6-7h4v16h-4V4Z"></path></svg></span>
            <span class="side-copy"><strong>AI Pattern Insights</strong><small>See your personal trends</small></span><span class="side-arrow">→</span>
        </a>
        <a class="side-link {{ $activeSection === 'recommendations' ? 'current' : '' }}" href="{{ route('recommendations') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c1.6 2.2 5 4.5 5 8.4A5 5 0 0 1 7 11.4C7 8.1 9.6 6 12 3Z"></path><path d="M12 21a5 5 0 0 0 4.8-6.2c-1.4 1.2-2.7 1.8-4 1.8-1.5 0-2.6-.6-3.6-1.5A5 5 0 0 0 12 21Z"></path></svg></span>
            <span class="side-copy"><strong>Food Recommendations</strong><small>Explore safer choices</small></span><span class="side-arrow">→</span>
        </a>
        <a class="side-link {{ $activeSection === 'history' ? 'current' : '' }}" href="{{ route('history') }}">
            <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5z"></path><path d="M8 8h8M8 12h8M8 16h5"></path></svg></span>
            <span class="side-copy"><strong>Health History</strong><small>Review your timeline</small></span><span class="side-arrow">→</span>
        </a>
    </nav>

    @if (auth()->user()->isPatient())
        <div class="side-label">Your account</div>
        <nav class="sidebar-nav" aria-label="Account">
            <a class="side-link {{ $activeSection === 'security' ? 'current' : '' }}" href="{{ route('security.show') }}">
                <span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"></path><path d="m9 12 2 2 4-4"></path></svg></span>
                <span class="side-copy"><strong>Account security</strong><small>{{ auth()->user()->hasTwoFactorEnabled() ? 'Two-factor is on' : 'Turn on two-factor' }}</small></span><span class="side-arrow">→</span>
            </a>
        </nav>
    @endif

    @if(auth()->user()->isAdmin())
        <div class="side-label">Admin module</div>
        <nav class="sidebar-nav" aria-label="Administration">
            <a class="side-link" href="#"><span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V9M10 19V5M16 19v-7M22 19V3"></path></svg></span><span class="side-copy"><strong>Analytics Overview</strong><small>System-wide activity</small></span><span class="side-arrow">→</span></a>
            <a class="side-link" href="#"><span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 4 6 6M4 20l4.5-1 10-10-3.5-3.5-10 10L4 20ZM12 6l6 6"></path></svg></span><span class="side-copy"><strong>Category Rules</strong><small>Manage trigger groups</small></span><span class="side-arrow">→</span></a>
            <a class="side-link" href="#"><span class="side-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><circle cx="17" cy="9" r="2.5"></circle><path d="M3 20a6 6 0 0 1 12 0M14 20a4 4 0 0 1 7 0"></path></svg></span><span class="side-copy"><strong>Cohort Management</strong><small>Manage patient groups</small></span><span class="side-arrow">→</span></a>
        </nav>
    @endif

    <div class="sidebar-footer"><span class="sidebar-footer-dot"></span><div><strong>Private tracking</strong><small>Your health logs are patient-scoped.</small></div></div>
</aside>
