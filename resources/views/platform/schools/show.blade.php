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
        <label>District</label>
        <input name="district" value="{{ old('district', $school->district) }}">
        <label>EMIS number</label>
        <input name="emis_number" value="{{ old('emis_number', $school->emis_number) }}">
        <label>Theme</label>
        <select name="theme" required>
          @foreach($themes as $key => $theme)
            <option value="{{ $key }}" @selected(old('theme', $school->theme) === $key)>{{ $theme['label'] ?? $key }}</option>
          @endforeach
        </select>
        <label>Status</label>
        <select name="status" required>
          @foreach(['pending','active','suspended','archived'] as $status)
            <option value="{{ $status }}" @selected(old('status', $school->status) === $status)>{{ $status }}</option>
          @endforeach
        </select>
        <p style="margin-top:14px"><button class="btn" type="submit">Save details</button></p>
      </form>
      <p style="margin:16px 0 0;color:var(--muted);font-size:13px">
        Subdomain: <a href="{{ $school->subdomainUrl() }}">{{ $school->subdomainUrl() }}</a>
      </p>
      @php($provisioning = $school->provisioningState())
      <p style="margin:8px 0 0">
        <strong>Provisioning:</strong>
        <span class="pill @if($provisioning !== 'ready') pill--muted @endif">
          {{ ['pending_invite' => 'Pending invite', 'invite_accepted' => 'Invite accepted', 'ready' => 'Ready'][$provisioning] }}
        </span>
      </p>
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
              <form method="post" action="{{ route('platform.schools.imitate', [$school, $user]) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn ghost">Imitate</button>
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
