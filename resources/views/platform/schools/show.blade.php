@extends('layouts.app')
@section('title',$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.schools.index') }}">Schools</a></p>
      <h2 class="page-header__title">{{ $school->name }}</h2>
    </div>
    <div class="page-header__actions">
      <form method="post" action="{{ route('platform.schools.enter', $school) }}">
        @csrf
        <button type="submit" class="btn accent">Enter workspace</button>
      </form>
      @if(session('platform.entered_school_id') == $school->id)
        <a class="btn" href="{{ route('platform.workspace') }}">Open workspace</a>
      @endif
    </div>
  </div>

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Edit school</h3>
      <form method="post" action="{{ route('platform.schools.update', $school) }}">
        @csrf
        @method('PUT')
        <label>Name</label>
        <input name="name" value="{{ old('name', $school->name) }}" required>
        @error('name')<div class="err">{{ $message }}</div>@enderror
        @include('platform.partials.district-picker', ['selected' => old('district', $school->district)])
        @error('district')<div class="err">{{ $message }}</div>@enderror
        <label>EMIS number</label>
        <input name="emis_number" value="{{ old('emis_number', $school->emis_number) }}">
        <label>Theme</label>
        <select name="theme" required>
          @foreach($themes as $key => $theme)
            <option value="{{ $key }}" @selected(old('theme', $school->theme) === $key)>{{ $theme['label'] ?? $key }}</option>
          @endforeach
        </select>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
          @foreach($themes as $key => $theme)
            @php($tok = $theme['tokens'] ?? [])
            <span title="{{ $theme['description'] ?? $key }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--muted)">
              <i style="width:14px;height:14px;border-radius:3px;background:{{ $tok['brand'] ?? '#ccc' }};display:inline-block"></i>
              <i style="width:14px;height:14px;border-radius:3px;background:{{ $tok['accent'] ?? '#ccc' }};display:inline-block"></i>
              {{ $theme['label'] ?? $key }}
            </span>
          @endforeach
        </div>
        <label>Status</label>
        <select name="status" required>
          @foreach(['pending','active','suspended','archived'] as $status)
            <option value="{{ $status }}" @selected(old('status', $school->status) === $status)>{{ $status }}</option>
          @endforeach
        </select>
        <p style="margin-top:14px"><button class="btn" type="submit">Save details</button></p>
      </form>
      <p style="margin:16px 0 0;color:var(--muted);font-size:13px">
        <strong>Tenant ID:</strong> {{ $school->tenantId() }}<br>
        Portal login: <a href="{{ $school->portalUrl() }}/login">{{ $school->portalUrl() }}/login</a>
        <span class="muted" style="display:block;margin-top:4px;font-size:13px">Optional legacy subdomain: <a href="{{ $school->subdomainUrl() }}">{{ $school->subdomainUrl() }}</a></span>
      </p>
      @php($provisioning = $school->provisioningState())
      <p style="margin:8px 0 0">
        <strong>Provisioning:</strong>
        <span class="pill @if($provisioning !== 'ready') pill--muted @endif">
          {{ ['pending_invite' => 'Pending invite', 'invite_accepted' => 'Invite accepted', 'ready' => 'Ready'][$provisioning] }}
        </span>
      </p>

      <div style="margin-top:28px;padding-top:18px;border-top:1px solid var(--line)">
        @if($school->status === 'deletion_scheduled')
          <h3 style="margin:0 0 8px;color:var(--warning, #b54708)">Deletion scheduled</h3>
          <p style="margin:0 0 12px;font-size:13px;color:var(--muted)">
            Scheduled {{ optional($school->deletion_scheduled_at)->toDayDateTimeString() }}.
            Purge eligible after {{ optional($school->purgeEligibleAt())->toDayDateTimeString() }}.
            @if($school->deletion_reason)
              <br>Reason: {{ $school->deletion_reason }}
            @endif
          </p>
          <form method="post" action="{{ route('platform.schools.restore', $school) }}">
            @csrf
            <button type="submit" class="btn">Restore school</button>
          </form>
        @else
          <h3 style="margin:0 0 8px;color:var(--danger, #b42318)">Delete school</h3>
          <p style="margin:0 0 12px;font-size:13px;color:var(--muted)">
            Schedules deletion ({{ \App\Services\Provisioning\SchoolDeletionService::RETENTION_DAYS }}-day retention).
            Tenant data is kept until purge. Type the school name to confirm.
          </p>
          <form method="post" action="{{ route('platform.schools.destroy', $school) }}"
                onsubmit="return confirm('Schedule deletion of this school? Data is retained for a grace period.')">
            @csrf
            @method('DELETE')
            <label>Reason (optional)</label>
            <input type="text" name="deletion_reason" maxlength="500" placeholder="Ticket or reason">
            <label style="margin-top:8px">Confirm school name</label>
            <input name="confirm_name" placeholder="{{ $school->name }}" required autocomplete="off">
            @error('confirm_name')<div class="err">{{ $message }}</div>@enderror
            <p style="margin-top:12px"><button class="btn" type="submit" style="background:var(--danger, #b42318);border-color:var(--danger, #b42318)">Schedule deletion</button></p>
          </form>
        @endif
      </div>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Levels offered</h3>
      @forelse($school->offerings as $o)
        <span class="pill">{{ $o->level }}</span>
      @empty
        <p style="color:var(--muted)">No levels recorded.</p>
      @endforelse

      <h3 style="margin:22px 0 8px">Open invitations</h3>
      @if($openInvites->isEmpty())
        <p style="color:var(--muted);margin:0">None open.</p>
      @else
        <ul style="margin:0;padding-left:18px;font-size:14px">
          @foreach($openInvites as $invite)
            <li style="margin-bottom:6px">
              {{ $invite->email ?: $invite->user?->full_name }} · {{ $invite->role_key }}
              @if($invite->isExpired())
                <span class="pill pill--muted">expired</span>
              @endif
            </li>
          @endforeach
        </ul>
        <p style="margin:10px 0 0"><a href="{{ route('platform.invitations.index') }}">Resend from Invitations →</a></p>
      @endif
    </div>
  </div>

  <div class="card">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:baseline;flex-wrap:wrap">
      <h3 style="margin:0">Staff &amp; accounts</h3>
      <form method="post" action="{{ route('platform.schools.enter', $school) }}">
        @csrf
        <button type="submit" class="btn ghost">Manage in workspace</button>
      </form>
    </div>
    <p style="color:var(--muted);font-size:14px">
      Imitate to see the school app as that user. For data entry, use <strong>Enter workspace</strong> instead.
    </p>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Roles</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($members as $member)
        @php($user = $member['user'])
        <tr>
          <td><strong>{{ $user->full_name }}</strong></td>
          <td>{{ $user->email ?? $user->phone ?? '—' }}</td>
          <td>
            @foreach($member['roles'] as $role)
              <span class="pill">{{ $role }}</span>
            @endforeach
          </td>
          <td>{{ $user->status }}</td>
          <td>
            @if($user->status === 'active' && ! $user->is_platform)
              <form method="post" action="{{ route('platform.schools.imitate', [$school, $user]) }}" style="display:grid;gap:6px;min-width:220px">
                @csrf
                <input type="text" name="reason" required minlength="8" maxlength="500" placeholder="Support reason (required)" style="font-size:12px;padding:6px 8px">
                <input type="text" name="ticket_id" maxlength="64" placeholder="Ticket # (optional)" style="font-size:12px;padding:6px 8px">
                <button type="submit" class="btn ghost">Imitate (read-only)</button>
              </form>
            @else
              <span style="color:var(--muted);font-size:13px">—</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No active accounts at this school yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
