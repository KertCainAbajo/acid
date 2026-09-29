@extends('admin.layout')

@section('admin-content')
<section class="welcome" id="overview">
    <div>
        <div class="eyebrow"><span class="dot"></span> SYSTEM OVERVIEW</div>
        <h1>Admin dashboard</h1>
        <p class="subtle" style="margin:8px 0 0">Monitor patient accounts and activity across RefluxCare.</p>
    </div>
    <span class="status">{{ $activePatientCount }} active of {{ $patientCount }} patients</span>
</section>

<section class="cards" aria-label="System totals">
    <article class="card"><div class="card-head"><span>Patient accounts</span><span>Users</span></div><div class="metric">{{ number_format($patientCount) }}</div><span class="tag">{{ number_format($activePatientCount) }} active</span></article>
    <article class="card"><div class="card-head"><span>Food entries</span><span>Logs</span></div><div class="metric">{{ number_format($foodLogCount) }}</div><p class="subtle" style="font-size:12px;margin:0">Recorded across all patients</p></article>
    <article class="card"><div class="card-head"><span>Symptom entries</span><span>Logs</span></div><div class="metric">{{ number_format($symptomLogCount) }}</div><p class="subtle" style="font-size:12px;margin:0">Recorded across all patients</p></article>
    <article class="card"><div class="card-head"><span>Sleep entries</span><span>Categories</span></div><div class="metric">{{ number_format($sleepLogCount) }}</div><span class="tag">{{ number_format($categoryCount) }} food categories</span></article>
</section>

<div class="two-col">
    <section class="card">
        <div class="section-title"><div><h2>Recent patients</h2><div class="subtle">The latest patient accounts</div></div><a class="btn secondary" href="{{ route('admin.patients') }}">View all</a></div>
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead><tr><th>Patient</th><th>Email</th><th>Joined</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentPatients as $patient)
                        <tr><td><strong>{{ $patient->name }}</strong></td><td>{{ $patient->email }}</td><td>{{ $patient->created_at->format('M d, Y') }}</td><td><span class="admin-status {{ $patient->is_active ? 'active' : 'inactive' }}">{{ $patient->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="subtle">No patient accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="section-title"><div><h2>System activity</h2><div class="subtle">Latest administrative actions</div></div><a class="btn secondary" href="{{ route('admin.activity') }}">View all</a></div>
        <div class="timeline">
            @forelse($recentActivity as $activity)
                <div class="timeline-row"><span class="timeline-line"></span><div class="timeline-copy"><strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong><span class="subtle" style="font-size:12px">{{ $activity->actor?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}</span></div></div>
            @empty
                <div class="subtle">Admin actions will appear here as they happen.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
