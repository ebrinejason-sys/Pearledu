@extends('layouts.app')
@section('title','PearlEdu admin')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">PearlEdu · Admin</p>
      <h2 class="page-header__title">Operations dashboard</h2>
      <p style="margin:8px 0 0;max-width:72ch;color:var(--muted);font-size:14px;line-height:1.55">
        Maintain tenants, PearlEdu staff, support, SMS credits, and school data entry — all schools share
        <code>/login</code>; this console is <code>/admin</code> only.
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn accent" href="{{ route('platform.schools.create') }}">Onboard school</a>
      <a class="btn ghost" href="{{ route('platform.support.index') }}">Support inbox</a>
    </div>
  </div>

  @if($enteredSchool)
    <div class="status" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
      <div>
        <strong>Working in {{ $enteredSchool->name }}</strong>
        — {{ number_format($workspaceStats['students']) }} students ·
        {{ number_format($workspaceStats['classes']) }} classes ·
        {{ number_format($workspaceStats['open_invites']) }} open invites
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn" href="{{ route('platform.workspace') }}">Open workspace</a>
        <a class="btn ghost" href="{{ route('platform.students.create') }}">Add student</a>
        <form method="post" action="{{ route('platform.schools.leave') }}">@csrf<button class="btn ghost" type="submit">Exit</button></form>
      </div>
    </div>
  @endif

  <div class="grid g4">
    <div class="card stat"><div class="l">Schools</div><div class="v">{{ $stats['schools'] }}</div><div class="s">{{ $stats['active'] }} active · {{ $stats['suspended'] }} suspended/archived</div></div>
    <div class="card stat"><div class="l">Learners</div><div class="v">{{ number_format($stats['learners']) }}</div></div>
    <div class="card stat"><div class="l">Open tickets</div><div class="v">{{ number_format($stats['tickets_open']) }}</div><div class="s">{{ $stats['tickets_unassigned'] }} unassigned@if($stats['tickets_urgent']) · {{ $stats['tickets_urgent'] }} urgent@endif</div></div>
    <div class="card stat"><div class="l">PearlEdu staff</div><div class="v">{{ number_format($stats['operators']) }}</div><div class="s">{{ number_format($stats['pending_invites']) }} school invites open</div></div>
  </div>

  <h3 style="margin:8px 0 12px;font-size:15px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">Core operations</h3>
  <div class="grid g2" style="margin-bottom:8px">
    <a class="card dash-action" href="{{ route('platform.schools.create') }}">
      <h3>Onboard a school</h3>
      <p>Create a tenant id, pick district, invite the first school admin. Users then sign in at shared /login.</p>
    </a>
    <a class="card dash-action" href="{{ route('platform.schools.index') }}">
      <h3>Schools directory</h3>
      <p>Edit details, enter workspace for EMIS/data entry, imitate a member, or permanently delete a tenant.</p>
    </a>
    <a class="card dash-action" href="{{ route('platform.operators.index') }}">
      <h3>PearlEdu staff</h3>
      <p>Create admins, EMIS data entrants, ops, and support agents who work in this console.</p>
      <span class="dash-action__meta">{{ $stats['operators'] }} active staff</span>
    </a>
    <a class="card dash-action" href="{{ route('platform.support.index') }}">
      <h3>Support inbox</h3>
      <p>Assign and resolve helpdesk tickets raised by schools across all tenants.</p>
      <span class="dash-action__meta">{{ $stats['tickets_open'] }} open · {{ $stats['tickets_unassigned'] }} unassigned</span>
    </a>
  </div>

  <h3 style="margin:20px 0 12px;font-size:15px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">Growth &amp; delivery</h3>
  <div class="grid g2" style="margin-bottom:16px">
    <a class="card dash-action" href="{{ route('platform.invitations.index') }}">
      <h3>Invitations desk</h3>
      <p>Track and resend school activation invites.</p>
      <span class="dash-action__meta">{{ number_format($stats['staff_invites']) }} staff invites open</span>
    </a>
    @if($enteredSchool)
      <a class="card dash-action" href="{{ route('platform.workspace') }}">
        <h3>School workspace · {{ $enteredSchool->name }}</h3>
        <p>Students, classes, staff invites, and guardian linking for the school you entered.</p>
      </a>
    @else
      <a class="card dash-action" href="{{ route('platform.schools.index') }}">
        <h3>Enter a school workspace</h3>
        <p>EMIS / data entrants: pick a school → Enter workspace to create students and classes.</p>
      </a>
    @endif
    <a class="card dash-action" href="{{ route('platform.sms.index') }}">
      <h3>SMS &amp; credits</h3>
      <p>Provider settings and per-school credit top-ups.</p>
      <span class="dash-action__meta">{{ number_format($stats['sms_sent']) }} messages sent</span>
    </a>
    <a class="card dash-action" href="{{ route('platform.pricing.index') }}">
      <h3>Pricing plans</h3>
      <p>Public plans on the VoxSign marketing site.</p>
    </a>
  </div>

  <div class="grid g2">
    <div class="card">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px">
        <h3 style="margin:0">Open support tickets</h3>
        <a href="{{ route('platform.support.index') }}" style="font-size:13px;font-weight:600">Inbox</a>
      </div>
      <table>
        <thead><tr><th>Subject</th><th>School</th><th>Priority</th></tr></thead>
        <tbody>
        @forelse($recentTickets as $t)
          <tr>
            <td><a href="{{ route('platform.support.show', $t) }}"><strong>{{ $t->subject }}</strong></a></td>
            <td>{{ $t->school?->name ?: '—' }}</td>
            <td><span class="pill">{{ $t->priority }}</span></td>
          </tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">No open tickets.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="card">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px">
        <h3 style="margin:0">Recent schools</h3>
        <a href="{{ route('platform.schools.index') }}" style="font-size:13px;font-weight:600">View all</a>
      </div>
      <table>
        <thead>
          <tr>
            <th>School</th>
            <th>Tenant</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        @forelse($schools as $s)
          <tr>
            <td>
              <strong>{{ $s->name }}</strong><br>
              <span style="color:var(--muted);font-size:12px">{{ $s->district ?: '—' }} · {{ number_format($s->students_count) }} learners</span>
            </td>
            <td><code>{{ $s->tenantId() }}</code></td>
            <td><span class="pill">{{ $s->status }}</span></td>
            <td style="white-space:nowrap">
              <a href="{{ route('platform.schools.show', $s) }}">Open</a>
              ·
              <form method="post" action="{{ route('platform.schools.enter', $s) }}" style="display:inline">
                @csrf
                <button type="submit" style="background:none;border:0;padding:0;color:var(--brand);font:inherit;font-weight:600;cursor:pointer">Enter</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" style="color:var(--muted)">
              No schools yet. <a href="{{ route('platform.schools.create') }}">Onboard your first school</a>.
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection

@section('head')
<style>
  .card.stat .s{margin-top:6px;font-size:12px;color:var(--muted)}
  a.dash-action{display:block;color:inherit;transition:border-color .15s ease,box-shadow .15s ease}
  a.dash-action:hover{border-color:var(--brand);box-shadow:0 8px 24px rgba(5,63,92,.08)}
  a.dash-action h3{margin:0 0 8px;font-size:16px;color:var(--brand)}
  a.dash-action p{margin:0;font-size:13px;line-height:1.55;color:var(--muted)}
  a.dash-action .dash-action__meta{display:block;margin-top:12px;font-size:12px;font-weight:600;color:var(--ink)}
</style>
@endsection
