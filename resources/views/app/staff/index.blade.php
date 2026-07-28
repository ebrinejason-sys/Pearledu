@extends('layouts.app')
@section('title','Staff · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">People</p>
      <h2 class="page-header__title">Staff &amp; invites</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Invite by email or phone. Recipients set a password via the link, then reach their dashboard. One person may hold multiple roles.</p>
    </div>
  </div>

  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Invite</h3>
      @if($roles->isEmpty())
        <p style="color:var(--muted)">You cannot invite anyone with your current roles.</p>
      @else
      <form method="post" action="{{ route('app.staff.store') }}">
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
        <label>Roles (select one or more)</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 12px">
          @foreach($roles as $role)
            <label style="display:flex;align-items:center;gap:6px;font-weight:500">
              <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" @checked(collect(old('role_keys', []))->contains($role->key))>
              {{ $role->label }}
            </label>
          @endforeach
        </div>
        @error('role_keys')<div class="err">{{ $message }}</div>@enderror
        <label>Class</label>
        <select name="class_id">
          <option value="">—</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected((string) old('class_id') === (string) $c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
        <p style="color:var(--muted);font-size:13px;margin:6px 0 0">Required when Class teacher is checked.</p>
        @error('class_id')<div class="err">{{ $message }}</div>@enderror
        <p style="margin-top:14px"><button class="btn" type="submit">Send invitation</button></p>
      </form>
      @endif
    </div>

    <div class="card">
      <h3 style="margin-top:0">Open invitations</h3>
      @if($openInvites->isEmpty())
        <p style="color:var(--muted);margin:0">No open invites.</p>
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
      @endif
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Active members</h3>
    <table>
      <thead><tr><th>Name</th><th>Contact</th><th>Roles / permissions</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($members as $member)
        @php($user = $member['user'])
        <tr>
          <td><strong>{{ $user->full_name }}</strong></td>
          <td>{{ $user->email ?? $user->phone ?? '—' }}</td>
          <td>
            @if($roles->isEmpty())
              @foreach($member['roles'] as $role)
                <span class="pill">{{ $role['label'] }}@if(!empty($role['class'])) · {{ $role['class'] }}@endif</span>
              @endforeach
            @else
              <form method="post" action="{{ route('app.staff.roles', $user) }}" style="display:grid;gap:8px">
                @csrf @method('PUT')
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                  @foreach($roles as $role)
                    <label style="display:flex;align-items:center;gap:6px;font-weight:500;font-size:13px">
                      <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" @checked(collect($member['role_keys'])->contains($role->key))>
                      {{ $role->label }}
                    </label>
                  @endforeach
                </div>
                <button type="submit" class="btn ghost">Save roles</button>
              </form>
            @endif
          </td>
          <td>{{ $user->status }}</td>
          <td>
            @if((int) $user->id !== (int) auth()->id())
              <form method="post" action="{{ route('app.staff.revoke', $user) }}" onsubmit="return confirm('Revoke school access for {{ $user->full_name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn ghost" style="color:var(--danger,#b42318)">Revoke</button>
              </form>
            @else
              —
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No members yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
