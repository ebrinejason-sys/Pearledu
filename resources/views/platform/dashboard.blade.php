@extends('layouts.app')
@section('title','Platform dashboard')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">VoxSign · PearlEdu console</p>
      <h2 class="page-header__title">Platform overview</h2>
      <p style="margin:8px 0 0;max-width:62ch;color:var(--muted);font-size:14px;line-height:1.55">
        Provision schools, invite school admins, manage SMS credits, and support tenants.
        Day-to-day academics (students, classes, guardians) live inside each school’s own workspace.
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn accent" href="{{ route('platform.schools.create') }}">Onboard school</a>
      <a class="btn ghost" href="{{ route('platform.schools.index') }}">All schools</a>
    </div>
  </div>

  <div class="grid g4">
    <div class="card stat"><div class="l">Schools</div><div class="v">{{ $stats['schools'] }}</div></div>
    <div class="card stat"><div class="l">Active</div><div class="v">{{ $stats['active'] }}</div></div>
    <div class="card stat"><div class="l">Learners (all schools)</div><div class="v">{{ number_format($stats['learners']) }}</div></div>
    <div class="card stat"><div class="l">Pending admin invites</div><div class="v">{{ number_format($stats['pending_invites']) }}</div></div>
  </div>

  <div class="grid g2" style="margin-bottom:16px">
    <a class="card dash-action" href="{{ route('platform.schools.create') }}">
      <h3>Onboard a school</h3>
      <p>Create the tenant, set levels and theme, then email the school admin invitation so they can accept and activate.</p>
    </a>
    <a class="card dash-action" href="{{ route('platform.schools.index') }}">
      <h3>Manage schools</h3>
      <p>Review provisioning status, open a school’s detail page, enter school scope, or imitate a member for support.</p>
    </a>
    <a class="card dash-action" href="{{ route('platform.sms.index') }}">
      <h3>SMS &amp; credits</h3>
      <p>Configure the SMS provider and top up school credit balances used for parent/guardian notifications.</p>
      <span class="dash-action__meta">{{ number_format($stats['sms_sent']) }} messages sent · {{ $stats['operators'] }} platform {{ Str::plural('operator', $stats['operators']) }}</span>
    </a>
    <a class="card dash-action" href="{{ route('platform.pricing.index') }}">
      <h3>Pricing plans</h3>
      <p>Edit the public pricing cards shown on the VoxSign marketing site for schools evaluating PearlEdu.</p>
    </a>
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
          <th>Subdomain</th>
          <th>Learners</th>
          <th>Provisioning</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($schools as $s)
        @php
          $provisioning = $s->provisioningState();
          $provisioningLabel = [
            'pending_invite' => 'Pending invite',
            'invite_accepted' => 'Invite accepted',
            'ready' => 'Ready',
          ][$provisioning] ?? $provisioning;
        @endphp
        <tr>
          <td>
            <strong>{{ $s->name }}</strong><br>
            <span style="color:var(--muted);font-size:12px">{{ $s->district ?: '—' }}</span>
          </td>
          <td>{{ $s->slug }}.{{ config('tenancy.base_domain') }}</td>
          <td>{{ number_format($s->students_count) }}</td>
          <td>
            <span class="pill {{ $provisioning === 'ready' ? '' : 'pill--muted' }}">{{ $provisioningLabel }}</span>
            @if($s->pending_invites_count > 0)
              <span style="display:block;margin-top:4px;font-size:12px;color:var(--muted)">{{ $s->pending_invites_count }} open invite{{ $s->pending_invites_count === 1 ? '' : 's' }}</span>
            @endif
          </td>
          <td><span class="pill">{{ $s->status }}</span></td>
          <td><a href="{{ route('platform.schools.show', $s) }}">Open</a></td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="color:var(--muted)">
            No schools yet.
            <a href="{{ route('platform.schools.create') }}">Onboard your first school</a>
            to provision a tenant and invite its admin.
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection

@section('head')
<style>
  a.dash-action{display:block;color:inherit;transition:border-color .15s ease,box-shadow .15s ease}
  a.dash-action:hover{border-color:var(--brand);box-shadow:0 8px 24px rgba(5,63,92,.08)}
  a.dash-action h3{margin:0 0 8px;font-size:16px;color:var(--brand)}
  a.dash-action p{margin:0;font-size:13px;line-height:1.55;color:var(--muted)}
  a.dash-action .dash-action__meta{display:block;margin-top:12px;font-size:12px;font-weight:600;color:var(--ink)}
</style>
@endsection
