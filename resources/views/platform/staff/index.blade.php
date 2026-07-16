@extends('layouts.app')
@section('title','Staff · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.workspace') }}">{{ $school->name }}</a> · People</p>
      <h2 class="page-header__title">Staff &amp; accounts</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Invite school operators. They set a password via the email link.</p>
    </div>
  </div>

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Invite staff</h3>
      <form method="post" action="{{ route('platform.staff.store') }}">
        @csrf
        <label>Full name</label>
        <input name="full_name" value="{{ old('full_name') }}" required>
        @error('full_name')<div class="err">{{ $message }}</div>@enderror
        <label>Email</label>
        <input name="email" type="email" value="{{ old('email') }}" required>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <label>Phone (optional)</label>
        <input name="phone" value="{{ old('phone') }}">
        <label>Role</label>
        <select name="role_key" required>
          @foreach($roles as $role)
            <option value="{{ $role->key }}" @selected(old('role_key', 'school_admin') === $role->key)>{{ $role->label }}</option>
          @endforeach
        </select>
        @error('role_key')<div class="err">{{ $message }}</div>@enderror
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
                <span style="color:var(--muted);font-size:12px">{{ $invite->email }}</span>
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
              <form method="post" action="{{ route('platform.schools.imitate', [$school, $user]) }}">
                @csrf
                <button type="submit" class="btn ghost">Imitate</button>
              </form>
            @else
              —
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No active accounts yet — invite a school admin above.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
