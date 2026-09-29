@extends('admin.layout')

@section('admin-content')
<section class="welcome">
    <div><div class="eyebrow"><span class="dot"></span> AUDIT TRAIL</div><h1>System activity</h1><p class="subtle" style="margin:8px 0 0">Latest 100 administrative actions recorded by RefluxCare.</p></div>
    <span class="status">{{ $activities->count() }} shown</span>
</section>

<section class="card">
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead><tr><th>Action</th><th>Admin</th><th>Target</th><th>Time</th></tr></thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td><strong class="admin-activity-action">{{ str($activity->action)->replace('_', ' ') }}</strong></td>
                        <td>{{ $activity->actor?->name ?? 'System' }}</td>
                        <td>{{ $activity->target_type ? class_basename($activity->target_type) : '—' }}{{ $activity->target_id ? ' #'.$activity->target_id : '' }}</td>
                        <td>{{ $activity->created_at->format('M d, Y · g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="subtle">No administrative activity has been recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
