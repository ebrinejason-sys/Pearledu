@extends('layouts.app')
@section('title','System overview')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Platform Admin</p>
      <h2 class="page-header__title">System &amp; security overview</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px;max-width:72ch">
        A single place to spot tenant, support, staff-access, queue, and accountability issues without exposing configuration secrets.
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn" href="{{ route('platform.audit.index') }}">Open audit trail</a>
      <a class="btn ghost" href="{{ route('platform.operators.index') }}">Manage staff</a>
    </div>
  </div>

  <div class="grid g4">
    <div class="card stat"><div class="l">Active schools</div><div class="v">{{ number_format($stats['schools_active']) }}</div><div class="s">{{ $stats['schools_attention'] }} need attention</div></div>
    <div class="card stat"><div class="l">Platform staff</div><div class="v">{{ number_format($stats['staff_active']) }}</div><div class="s">{{ $stats['staff_disabled'] }} disabled</div></div>
    <div class="card stat"><div class="l">2FA gaps</div><div class="v">{{ number_format($stats['staff_without_2fa']) }}</div><div class="s">{{ $stats['staff_misconfigured'] }} role misconfigurations</div></div>
    <div class="card stat"><div class="l">Open tickets</div><div class="v">{{ number_format($stats['open_tickets']) }}</div><div class="s">{{ $stats['urgent_tickets'] }} urgent</div></div>
  </div>

  <div class="grid g4">
    <div class="card stat"><div class="l">Signed-in sessions</div><div class="v">{{ number_format($stats['active_sessions']) }}</div></div>
    <div class="card stat"><div class="l">Queued jobs</div><div class="v">{{ number_format($stats['queued_jobs']) }}</div></div>
    <div class="card stat"><div class="l">Failed jobs</div><div class="v">{{ number_format($stats['failed_jobs']) }}</div></div>
    <div class="card stat"><div class="l">Audit events · 24h</div><div class="v">{{ number_format($stats['audit_24h']) }}</div></div>
  </div>

  @if($stats['staff_without_2fa'] || $stats['staff_misconfigured'] || $stats['failed_jobs'])
    <div class="err" style="margin-bottom:16px">
      <strong>Administrator attention required.</strong>
      @if($stats['staff_without_2fa']) {{ $stats['staff_without_2fa'] }} active platform staff account(s) do not have 2FA. @endif
      @if($stats['staff_misconfigured']) {{ $stats['staff_misconfigured'] }} staff account(s) have no platform role. @endif
      @if($stats['failed_jobs']) {{ $stats['failed_jobs'] }} background job(s) have failed. @endif
    </div>
  @endif

  <div class="grid g2">
    <div class="card">
      <div style="display:flex;justify-content:space-between;gap:12px;align-items:baseline">
        <h3 style="margin-top:0">Platform staff security</h3>
        <a href="{{ route('platform.operators.index') }}">Manage</a>
      </div>
      <table>
        <thead><tr><th>Staff member</th><th>Role</th><th>Status</th><th>2FA</th><th>Last login</th></tr></thead>
        <tbody>
        @foreach($platformStaff as $member)
          <tr>
            <td><strong>{{ $member->full_name }}</strong><br><span style="font-size:12px;color:var(--muted)">{{ $member->email }}</span></td>
            <td><span class="pill">{{ $member->platform_role ?: 'Misconfigured' }}</span></td>
            <td>{{ $member->status }}</td>
            <td>{{ $member->hasTwoFactorEnabled() ? 'Enabled' : 'Missing' }}</td>
            <td>{{ $member->last_login_at?->diffForHumans() ?: 'Never' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div class="card">
      <div style="display:flex;justify-content:space-between;gap:12px;align-items:baseline">
        <h3 style="margin-top:0">Recent sensitive activity</h3>
        <a href="{{ route('platform.audit.index') }}">View all</a>
      </div>
      <table>
        <thead><tr><th>Time</th><th>Action</th><th>Actor</th><th>School</th></tr></thead>
        <tbody>
        @forelse($recentSensitive as $event)
          <tr>
            <td style="white-space:nowrap">{{ $event->created_at?->diffForHumans() }}</td>
            <td><strong>{{ $event->action }}</strong></td>
            <td>{{ $event->actor?->full_name ?: 'System' }}</td>
            <td>{{ $event->school?->name ?: 'Platform' }}</td>
          </tr>
        @empty
          <tr><td colspan="4" style="color:var(--muted)">No sensitive activity recorded yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
