@extends('layouts.app')
@section('title','Staff · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.workspace') }}">{{ $school->name }}</a> · People</p>
      <h2 class="page-header__title">Staff &amp; accounts</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Invite by email or phone. Edit roles to change what each person can do. Permissions come from roles.</p>
    </div>
  </div>

  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @error('school')<div class="err" style="margin-bottom:16px">{{ $message }}</div>@enderror
  @error('role_keys')<div class="err" style="margin-bottom:16px">{{ $message }}</div>@enderror
  @error('user')<div class="err" style="margin-bottom:16px">{{ $message }}</div>@enderror

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Invite staff</h3>
      <form method="post" action="{{ route('platform.staff.store') }}">
        @csrf
        <label>Full name</label>
        <input name="full_name" value="{{ old('full_name') }}" required>
        @error('full_name')<div class="err">{{ $message }}</div>@enderror
        <label>Email</label>
        <input name="email" type="email" value="{{ old('email') }}">
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <label>Phone</label>
        <input name="phone" value="{{ old('phone') }}" placeholder="07XXXXXXXX">
        @error('phone')<div class="err">{{ $message }}</div>@enderror
        <label>Roles</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 12px">
          @foreach($roles as $role)
            <label style="display:flex;align-items:center;gap:6px;font-weight:500">
              <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" @checked(collect(old('role_keys', ['school_admin']))->contains($role->key))>
              {{ $role->label }}
            </label>
          @endforeach
        </div>
        @error('role_keys')<div class="err">{{ $message }}</div>@enderror
        <p style="margin-top:14px"><button class="btn" type="submit">Send invitation</button></p>
      </form>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Open invitations</h3>
      @if($openInvites->isEmpty())
        <p style="color:var(--muted);margin:0">No open invites for this school.</p>
      @else
        <table>
          <thead><tr><th>Person</th><th>Role</th><th>Expires</th></tr></thead>
          <tbody>
          @foreach($openInvites as $invite)
            <tr>
              <td>
                <strong>{{ $invite->user?->full_name ?? '—' }}</strong><br>
                <span style="color:var(--muted);font-size:12px">{{ $invite->email ?: $invite->phone }}</span>
              </td>
              <td><span class="pill">{{ $invite->role_key }}</span></td>
              <td>{{ $invite->expires_at->diffForHumans() }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
        <p style="margin:12px 0 0"><a href="{{ route('platform.invitations.index') }}">Manage all invitations →</a></p>
      @endif
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Active members</h3>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Contact</th>
          <th>Roles / permissions</th>
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
            <form method="post" action="{{ route('platform.staff.roles', $user) }}" style="display:grid;gap:8px">
              @csrf @method('PUT')
              <div style="display:flex;flex-wrap:wrap;gap:8px">
                @foreach($roles as $role)
                  <label style="display:flex;align-items:center;gap:6px;font-weight:500;font-size:13px">
                    <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" @checked(collect($member['role_keys'])->contains($role->key))>
                    {{ $role->label }}
                  </label>
                @endforeach
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="submit" class="btn ghost">Save roles</button>
              </div>
            </form>
          </td>
          <td>{{ $user->status }}</td>
          <td style="display:grid;gap:8px;min-width:220px">
            @if($canImitate && in_array($user->status, ['active', 'invited'], true) && ! $user->is_platform)
              <form method="post" action="{{ route('platform.schools.imitate', [$school, $user]) }}" style="display:grid;gap:6px">
                @csrf
                <input type="text" name="reason" required minlength="8" maxlength="500" placeholder="Support reason (required)" style="font-size:12px;padding:6px 8px">
                <input type="text" name="ticket_id" maxlength="64" placeholder="Ticket # (optional)" style="font-size:12px;padding:6px 8px">
                <button type="submit" class="btn ghost">Imitate (read-only)</button>
              </form>
            @endif
            <form method="post" action="{{ route('platform.staff.revoke', $user) }}" onsubmit="return confirm('Revoke all school roles for {{ $user->full_name }}?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn-link-action btn-link-danger">Revoke access</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No active accounts yet — invite a school admin above.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
@section('head')
<style>
  .btn-link-action{background:none;border:0;padding:0;color:var(--brand);font:inherit;font-weight:600;cursor:pointer}
  .btn-link-danger{color:var(--danger,#b42318)}
</style>
@endsection
