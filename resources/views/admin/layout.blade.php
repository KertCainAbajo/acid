@extends('layouts.app')

@section('content')
<div class="shell">
    @include('admin.partials.header')

    <div class="layout">
        @include('admin.partials.sidebar')

        <main class="main">
            @yield('admin-content')
        </main>
    </div>
</div>

<style>
    .admin-table { width:100%; border-collapse:collapse; text-align:left; }
    .admin-table th { padding:12px 10px; color:#748a8d; font-size:10px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; white-space:nowrap; }
    .admin-table td { padding:13px 10px; border-top:1px solid #edf2f0; color:#587175; font-size:12px; white-space:nowrap; }
    .admin-table td strong { color:var(--ink); }
    .admin-status { display:inline-flex; padding:5px 8px; border-radius:999px; font-size:10px; font-weight:750; }
    .admin-status.active { color:#17684f; background:#e6f8ef; }
    .admin-status.inactive { color:#a64640; background:#fff0ee; }
    .admin-activity-action { color:var(--ink); font-weight:750; text-transform:capitalize; }
    .admin-activity-meta { color:var(--muted); font-size:12px; }
</style>
@endsection
