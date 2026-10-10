@extends('admin.layout')

@section('admin-content')
@include('admin.partials.ui')
@php
    $hasFilters = $filters['q'] !== '' || $filters['group'] !== 'all' || $filters['from'] || $filters['to'];
@endphp
<style>
    .ac-filters { display:grid; grid-template-columns:minmax(0,1fr) auto auto auto auto; gap:10px; align-items:center; }
    .ac-date { display:flex; align-items:center; gap:6px; color:var(--muted); font-size:12px; font-weight:600; }
    .ac-date .au-input { width:150px; }
    .ac-event { display:flex; align-items:center; gap:10px; }
    .ac-event .lp-badge-icon { width:34px; height:34px; border-radius:10px; }
    .ac-event strong { font-size:13.5px; }
    @media (max-width:1200px) { .ac-filters { grid-template-columns:1fr 1fr; } }
    @media (max-width:640px) { .ac-filters { grid-template-columns:1fr; } .ac-date .au-input { width:100%; } }
</style>

<div class="lp">
    <section class="lp-hero">
        <div>
            <span class="lp-crumb"><a href="{{ route('admin.dashboard') }}">Admin</a> / System activity</span>
            <h1>System activity</h1>
            <p>An audit trail of sign-ins, new accounts, patient entries and admin changes. Filter it to investigate an issue, or export it for your records.</p>
        </div>
        <div class="lp-hero-stats">
            <div class="lp-hero-stat"><span>Today</span><strong>{{ $todayCount }}</strong><small>events</small></div>
            <div class="lp-hero-stat"><span>Last 7 days</span><strong>{{ $weekCount }}</strong><small>events</small></div>
            <div class="lp-hero-stat"><span>Matching</span><strong>{{ $activities->total() }}</strong><small>{{ $hasFilters ? 'with your filters' : 'all time' }}</small></div>
        </div>
    </section>

    <section class="lp-panel">
        <nav class="au-tabs" aria-label="Filter by type" style="margin-bottom:12px;width:max-content;max-width:100%">
            <a href="{{ route('admin.activity', [...$filters, 'group' => 'all', 'page' => null]) }}" class="{{ $filters['group'] === 'all' ? 'is-active' : '' }}">All</a>
            @foreach ($groups as $key => $group)
                <a href="{{ route('admin.activity', [...$filters, 'group' => $key, 'page' => null]) }}" class="{{ $filters['group'] === $key ? 'is-active' : '' }}">{{ $group['label'] }}</a>
            @endforeach
        </nav>

        <form class="ac-filters" method="GET" action="{{ route('admin.activity') }}" style="margin-bottom:16px">
            <input type="hidden" name="group" value="{{ $filters['group'] }}">
            <label class="au-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input class="au-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search a person, email or action…" aria-label="Search activity"></label>
            <label class="ac-date">From <input class="au-input" type="date" name="from" value="{{ $filters['from'] }}" max="{{ now()->toDateString() }}"></label>
            <label class="ac-date">To <input class="au-input" type="date" name="to" value="{{ $filters['to'] }}" max="{{ now()->toDateString() }}"></label>
            <button class="au-btn is-primary" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18l-7 8v6l-4 2v-8Z"/></svg> Apply</button>
            <div style="display:flex;gap:8px">
                @if ($hasFilters)<a class="au-btn" href="{{ route('admin.activity') }}">Clear</a>@endif
                <a class="au-btn" href="{{ route('admin.activity.export', $filters) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg> Export CSV</a>
            </div>
        </form>
        @if ($errors->any())<p class="au-error" style="margin:-6px 0 14px">{{ $errors->first() }}</p>@endif

        @if ($activities->isEmpty())
            <div class="lp-empty"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg><strong>No matching activity</strong>{{ $hasFilters ? 'Try different filters.' : 'Events will appear here as they happen.' }}</div>
        @else
            <div style="overflow-x:auto">
                <table class="au-table">
                    <thead><tr><th>Event</th><th>Person</th><th class="au-hide-sm">Target</th><th class="au-hide-sm">IP address</th><th>When</th></tr></thead>
                    <tbody>
                        @foreach ($activities as $activity)
                            @php(['label' => $label, 'tone' => $tone, 'icon' => $icon] = \App\Support\ActivityPresenter::describe($activity->action))
                            <tr>
                                <td><div class="ac-event lp-tone-{{ $tone }}"><span class="lp-badge-icon"><svg class="lp-svg" style="width:15px;height:15px" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span><strong>{{ $label }}</strong></div></td>
                                <td>
                                    @if ($activity->actor)
                                        <div class="au-person"><div><strong>{{ $activity->actor->name }}@if ($activity->actor->role === 'admin') <span class="au-pill lp-tone-violet" style="margin-left:4px">Admin</span>@endif</strong><small>{{ $activity->actor->email }}</small></div></div>
                                    @else
                                        <span class="au-muted">System or deleted account</span>
                                    @endif
                                </td>
                                <td class="au-hide-sm"><span class="au-muted">{{ $activity->target_type ? class_basename($activity->target_type).' #'.$activity->target_id : '—' }}</span></td>
                                <td class="au-hide-sm"><span class="au-muted">{{ $activity->ip_address ?? '—' }}</span></td>
                                <td><span class="au-muted" title="{{ $activity->created_at->format('M j, Y g:i:s A') }}">{{ $activity->created_at->format('M j, g:i A') }} · {{ $activity->created_at->diffForHumans(null, true) }} ago</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="au-pagination">
                <span>Showing {{ $activities->firstItem() }}–{{ $activities->lastItem() }} of {{ $activities->total() }}</span>
                <nav aria-label="Pages">
                    @if ($activities->previousPageUrl())<a class="au-btn is-sm" href="{{ $activities->previousPageUrl() }}">← Newer</a>@endif
                    @if ($activities->nextPageUrl())<a class="au-btn is-sm" href="{{ $activities->nextPageUrl() }}">Older →</a>@endif
                </nav>
            </div>
        @endif
    </section>
</div>
@endsection
