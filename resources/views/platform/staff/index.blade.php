@extends('layouts.app')
@section('title','Staff · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.workspace') }}">{{ $school->name }}</a> · People</p>
      <h2 class="page-header__title">Staff &amp; accounts</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Invite by email or phone. Use <strong>Read-only</strong> or <strong>Full write</strong> to imitate that staff member in this school.</p>
    </div>
  </div>

  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @error('school')<div class="err" style="margin-bottom:16px">{{ $message }}</div>@enderror
  @error('role_keys')<div class="err" style="margin-bottom:16px">{{ $message }}</div>@enderror
  @error('user')<div class="err" style="margin-bottom:16px">{{ $message }}</div>@enderror

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Invite staff</h3>
      <form method="post" action="{{ route('platform.staff.store') }}" id="platform-invite-form">
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
        <label>Gender</label>
        <select name="gender" required>
          <option value="">— Select —</option>
          <option value="male" @selected(old('gender') === 'male')>Male</option>
          <option value="female" @selected(old('gender') === 'female')>Female</option>
        </select>
        @error('gender')<div class="err">{{ $message }}</div>@enderror
        <label>National ID (NIN)</label>
        <input name="nin" value="{{ old('nin') }}" required autocomplete="off" minlength="10" maxlength="20">
        @error('nin')<div class="err">{{ $message }}</div>@enderror
        <label>Roles</label>
        <div class="role-picks">
          @foreach($roles as $role)
            <label class="role-pick">
              <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" class="js-platform-role" @checked(collect(old('role_keys', ['school_admin']))->contains($role->key))>
              <strong>{{ $role->label }}</strong>
            </label>
          @endforeach
        </div>
        @error('role_keys')<div class="err">{{ $message }}</div>@enderror
        <div id="platform-teaching-field" hidden>
          <h3 style="font-size:15px;margin:8px 0 0">Teaching load</h3>
          @include('app.teaching._load-builder', [
            'builderId' => 'platform-invite-load',
            'subjects' => $subjects ?? collect(),
            'classes' => $classes ?? collect(),
          ])
        </div>
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
                  <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" class="js-platform-member-role" data-user="{{ $user->id }}" @checked(collect($member['role_keys'])->contains($role->key))>
                  {{ $role->label }}
                </label>
                @endforeach
              </div>
              <div class="js-platform-member-teach" data-user="{{ $user->id }}" @if(!collect($member['role_keys'])->contains('subject_teacher')) hidden @endif>
                @include('app.teaching._load-builder', [
                  'builderId' => 'platform-member-'.$user->id,
                  'subjects' => $subjects ?? collect(),
                  'classes' => $classes ?? collect(),
                  'hint' => 'Required when granting Teacher if this person has no current-year load.',
                ])
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
                <input type="number" min="1" name="ticket_id" placeholder="Ticket # (required for write)" style="font-size:12px;padding:6px 8px">
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                  <button type="submit" class="btn ghost">Read-only</button>
                  @if(auth()->user()->hasPlatformPermission('platform.users.impersonate_write'))
                    <button type="submit" class="btn" name="elevated_write" value="1" onclick="return confirm('Use full school write access? A matching support ticket is required and every write will be audited.')">Full write access</button>
                  @endif
                </div>
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
<script>
  document.addEventListener('DOMContentLoaded', function () {
    function syncPlatformInvite() {
      var field = document.getElementById('platform-teaching-field');
      if (!field) return;
      var on = Array.from(document.querySelectorAll('#platform-invite-form .js-platform-role:checked')).some(function (el) {
        return el.value === 'subject_teacher';
      });
      field.hidden = !on;
    }
    document.querySelectorAll('#platform-invite-form .js-platform-role').forEach(function (el) {
      el.addEventListener('change', syncPlatformInvite);
    });
    syncPlatformInvite();
    document.querySelectorAll('.js-platform-member-role').forEach(function (el) {
      el.addEventListener('change', function () {
        var box = document.querySelector('.js-platform-member-teach[data-user="' + el.getAttribute('data-user') + '"]');
        if (!box) return;
        var any = Array.from(document.querySelectorAll('.js-platform-member-role[data-user="' + el.getAttribute('data-user') + '"]')).some(function (cb) {
          return cb.value === 'subject_teacher' && cb.checked;
        });
        box.hidden = !any;
      });
    });
  });
</script>
@endsection
