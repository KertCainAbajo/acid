<header class="topbar">
    <a class="brand" href="{{ route('admin.dashboard') }}"><img class="brand-logo" src="{{ asset('images/logo1.png') }}" alt="RefluxCare logo"><span>RefluxCare</span></a>
    <div class="top-actions">
        <span class="pill active">Admin Portal</span>
        <span class="avatar" aria-label="{{ auth()->user()->name }}">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="pill" type="submit">Log out</button></form>
    </div>
</header>
