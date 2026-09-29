@extends('admin.layout')

@section('admin-content')
<section class="welcome">
    <div><div class="eyebrow"><span class="dot"></span> PATIENT ACCOUNTS</div><h1>Recent patients</h1><p class="subtle" style="margin:8px 0 0">Latest 100 registered patient accounts.</p></div>
    <span class="status">{{ $patients->count() }} shown</span>
</section>

<section class="card">
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead><tr><th>Patient</th><th>Email</th><th>Joined</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($patients as $patient)
                    <tr><td><strong>{{ $patient->name }}</strong></td><td>{{ $patient->email }}</td><td>{{ $patient->created_at->format('M d, Y') }}</td><td><span class="admin-status {{ $patient->is_active ? 'active' : 'inactive' }}">{{ $patient->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
                @empty
                    <tr><td colspan="4" class="subtle">No patient accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
