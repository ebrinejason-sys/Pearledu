@extends('layouts.app')
@section('title','Staff · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">People</p>
      <h1 class="page-header__title">Staff members</h1>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Add people and choose their responsibilities. One person may be a teacher and a class teacher at the same time.</p>
    </div>
  </div>

  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="grid g2">
    <div class="card">
      <h2 style="margin-top:0;font-size:18px">Add staff member</h2>
      @if($roles->isEmpty())
        <p style="color:var(--muted)">You cannot invite anyone with your current responsibilities.</p>
      @else
      <form method="post" action="{{ route('app.staff.store') }}" id="invite-staff-form">
        @csrf
        <fieldset style="border:0;padding:0;margin:0">
          <legend style="font-size:13px;color:var(--muted);padding:0">Personal information</legend>
          <label for="invite-name">Full name <span aria-hidden="true">*</span></label>
          <input id="invite-name" name="full_name" value="{{ old('full_name') }}" required autocomplete="name">
          @error('full_name')<div class="err" role="alert">{{ $message }}</div>@enderror
          <label for="invite-email">Email</label>
          <input id="invite-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email">
          @error('email')<div class="err" role="alert">{{ $message }}</div>@enderror
          <label for="invite-phone">Phone</label>
          <input id="invite-phone" name="phone" value="{{ old('phone') }}" placeholder="07XXXXXXXX" autocomplete="tel">
          @error('phone')<div class="err" role="alert">{{ $message }}</div>@enderror
        </fieldset>

        <fieldset style="border:0;padding:0;margin:16px 0 0">
          <legend style="font-size:13px;color:var(--muted);padding:0">Responsibilities</legend>
          <p style="margin:4px 0 10px;font-size:13px;color:var(--muted)">Tick every role this person should hold in this school.</p>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 12px">
            @foreach($roles as $role)
              <label class="check" style="margin:0;padding:8px 12px;border:1px solid var(--line);border-radius:var(--radius-sm)">
                <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" class="js-role-key" @checked(collect(old('role_keys', []))->contains($role->key))>
                <span>{{ $role->label }}</span>
              </label>
            @endforeach
          </div>
          @error('role_keys')<div class="err" role="alert">{{ $message }}</div>@enderror
        </fieldset>

        <div id="homeroom-field" hidden>
          <label for="invite-class">Homeroom class <span aria-hidden="true">*</span></label>
          <select id="invite-class" name="class_id">
            <option value="">Select class</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}" @selected((string) old('class_id') === (string) $c->id)>{{ $c->displayName() }}</option>
            @endforeach
          </select>
          <p style="color:var(--muted);font-size:13px;margin:6px 0 0">Required when Class Teacher is selected. Teaching subjects are assigned separately under Teaching assignments.</p>
          @error('class_id')<div class="err" role="alert">{{ $message }}</div>@enderror
        </div>
        <p style="margin-top:14px"><button class="btn" type="submit">Send invitation</button></p>
      </form>
      @endif
    </div>

    <div class="card">
      <h2 style="margin-top:0;font-size:18px">Open invitations</h2>
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
    <h2 style="margin-top:0;font-size:18px">Active members</h2>
    <table>
      <thead><tr><th>Name</th><th>Contact</th><th>Responsibilities</th><th>Status</th><th></th></tr></thead>
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
                @foreach($member['role_keys'] as $existingKey)
                  @unless($roles->contains('key', $existingKey))
                    <input type="hidden" name="role_keys[]" value="{{ $existingKey }}">
                  @endunless
                @endforeach
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                  @foreach($member['roles'] as $role)
                    @unless($roles->contains('key', $role['key']))
                      <span class="pill">{{ $role['label'] }}@if(!empty($role['class'])) · {{ $role['class'] }}@endif</span>
                    @endunless
                  @endforeach
                  @foreach($roles as $role)
                    <label class="check" style="margin:0;font-size:13px">
                      <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" class="js-member-role" data-user="{{ $user->id }}" @checked(collect($member['role_keys'])->contains($role->key))>
                      <span>{{ $role->label }}</span>
                    </label>
                  @endforeach
                </div>
                <div class="js-member-homeroom" data-user="{{ $user->id }}" @if(!collect($member['role_keys'])->contains('class_teacher')) hidden @endif>
                  <label>Homeroom class</label>
                  <select name="class_id">
                    <option value="">Select class</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" @selected((string) $member['homeroom_class_id'] === (string) $c->id)>{{ $c->displayName() }}</option>
                    @endforeach
                  </select>
                </div>
                <button type="submit" class="btn ghost">Save responsibilities</button>
              </form>
            @endif
          </td>
          <td>{{ $user->status }}</td>
          <td>
            @if(!empty($canManageStaff) && (int) $user->id !== (int) auth()->id())
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

@section('head')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    function syncInviteHomeroom() {
      var field = document.getElementById('homeroom-field');
      if (!field) return;
      var checked = Array.from(document.querySelectorAll('#invite-staff-form .js-role-key:checked')).some(function (el) {
        return el.value === 'class_teacher';
      });
      field.hidden = !checked;
    }
    document.querySelectorAll('#invite-staff-form .js-role-key').forEach(function (el) {
      el.addEventListener('change', syncInviteHomeroom);
    });
    syncInviteHomeroom();

    document.querySelectorAll('.js-member-role').forEach(function (el) {
      el.addEventListener('change', function () {
        var box = document.querySelector('.js-member-homeroom[data-user="' + el.getAttribute('data-user') + '"]');
        if (!box) return;
        var any = Array.from(document.querySelectorAll('.js-member-role[data-user="' + el.getAttribute('data-user') + '"]')).some(function (cb) {
          return cb.value === 'class_teacher' && cb.checked;
        });
        box.hidden = !any;
      });
    });
  });
</script>
@endsection
