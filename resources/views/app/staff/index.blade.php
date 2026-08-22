@extends('layouts.app')
@section('title','Staff · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">People</p>
      <h1 class="page-header__title">Staff members</h1>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Add people and choose their responsibilities. One person may be a teacher and a class teacher at the same time.</p>
    </div>
    <div class="page-header__actions">
      @if(!empty($canViewClock))
        <a class="btn" href="{{ route('app.staff.clock') }}">Staff clock</a>
      @endif
      @if(in_array('staff.messages', auth()->user()->permissionsForSchool($school->id), true))
        <a class="btn ghost" href="{{ route('app.staff.messages.index') }}">Messages</a>
      @endif
    </div>
  </div>

  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="grid g2">
    <div class="card">
      <h2 style="margin-top:0;font-size:18px">Add staff member</h2>
      @if($roles->isEmpty())
        <p style="color:var(--muted)">You cannot invite anyone with your current responsibilities.</p>
      @else
      <form method="post" action="{{ route('app.staff.store') }}" id="invite-staff-form" enctype="multipart/form-data">
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
          <label for="invite-gender">Gender <span aria-hidden="true">*</span></label>
          <select id="invite-gender" name="gender" required>
            <option value="">— Select —</option>
            <option value="male" @selected(old('gender') === 'male')>Male</option>
            <option value="female" @selected(old('gender') === 'female')>Female</option>
          </select>
          @error('gender')<div class="err" role="alert">{{ $message }}</div>@enderror
          <label for="invite-nin">National ID (NIN) <span aria-hidden="true">*</span></label>
          <input id="invite-nin" name="nin" value="{{ old('nin') }}" required autocomplete="off" minlength="10" maxlength="20">
          @error('nin')<div class="err" role="alert">{{ $message }}</div>@enderror
          <label for="invite-dob">Date of birth</label>
          <input id="invite-dob" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
          @error('date_of_birth')<div class="err" role="alert">{{ $message }}</div>@enderror
          <label for="invite-nationality">Nationality</label>
          <input id="invite-nationality" name="nationality" value="{{ old('nationality', 'Uganda') }}">
          <label for="invite-address">Home address</label>
          <input id="invite-address" name="home_address" value="{{ old('home_address') }}">
          <label for="invite-photo">Photo</label>
          <input id="invite-photo" type="file" name="photo" accept="image/*" capture="user">
          @error('photo')<div class="err" role="alert">{{ $message }}</div>@enderror
        </fieldset>

        <fieldset style="border:0;padding:0;margin:16px 0 0">
          <legend style="font-size:13px;color:var(--muted);padding:0">Teaching or non-teaching</legend>
          <p style="margin:4px 0 10px;font-size:13px;color:var(--muted)">Teaching staff need a subject and class load. Non-teaching staff keep biodata, ID, salary, and other duties.</p>
          <label class="check" style="margin:0 12px 8px 0">
            <input type="radio" name="staff_kind" value="teaching" class="js-staff-kind" @checked(old('staff_kind', 'teaching') === 'teaching')>
            <span>Teaching staff</span>
          </label>
          <label class="check" style="margin:0 0 8px">
            <input type="radio" name="staff_kind" value="non_teaching" class="js-staff-kind" @checked(old('staff_kind') === 'non_teaching')>
            <span>Non-teaching staff</span>
          </label>
          @error('staff_kind')<div class="err" role="alert">{{ $message }}</div>@enderror
        </fieldset>

        <fieldset style="border:0;padding:0;margin:16px 0 0">
          <legend style="font-size:13px;color:var(--muted);padding:0">Responsibilities</legend>
          <p style="margin:4px 0 10px;font-size:13px;color:var(--muted)">Tick every role this person should hold in this school. Teaching staff must include Teacher and/or Class Teacher.</p>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 12px">
            @foreach($roles as $role)
              <label class="check js-role-wrap{{ in_array($role->key, ['subject_teacher', 'class_teacher'], true) ? ' js-teaching-role' : '' }}" style="margin:0;padding:8px 12px;border:1px solid var(--line);border-radius:var(--radius-sm)">
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
          <p style="color:var(--muted);font-size:13px;margin:6px 0 0">Required when Class Teacher is selected.</p>
          @error('class_id')<div class="err" role="alert">{{ $message }}</div>@enderror
        </div>
        <div id="teaching-field" hidden>
          <label>Teaching load <span aria-hidden="true">*</span></label>
          <p style="color:var(--muted);font-size:13px;margin:0 0 8px">Required when Teacher is selected. One person can teach different subjects to different classes so the timetable does not collide.</p>
          <div id="teaching-rows">
            <div class="js-teach-row" style="border:1px solid var(--line);border-radius:var(--radius-sm);padding:10px;margin-bottom:8px">
              <label>Subject</label>
              <select name="teaching_assignments[0][subject_id]">
                <option value="">Select subject</option>
                @foreach($subjects ?? [] as $subject)
                  <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
              </select>
              <fieldset style="border:0;padding:0;margin:8px 0 0">
                <legend style="font-size:13px;color:var(--muted)">Classes</legend>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                  @foreach($classes as $c)
                    <label class="check" style="margin:0">
                      <input type="checkbox" name="teaching_assignments[0][class_ids][]" value="{{ $c->id }}">
                      <span>{{ $c->displayName() }}</span>
                    </label>
                  @endforeach
                </div>
              </fieldset>
              <label>Periods per week</label>
              <input type="number" min="1" max="40" name="teaching_assignments[0][periods_per_week]" value="3">
            </div>
          </div>
          <button type="button" class="btn ghost" id="add-teach-row">Add another subject</button>
          @error('teaching_assignments')<div class="err" role="alert">{{ $message }}</div>@enderror
        </div>
        @if(!empty($canSetSalary))
        <fieldset style="border:0;padding:0;margin:16px 0 0">
          <legend style="font-size:13px;color:var(--muted);padding:0">Salary</legend>
          <label for="invite-salary">Monthly amount (UGX)</label>
          <input id="invite-salary" type="number" min="0" name="salary_amount" value="{{ old('salary_amount') }}">
          @error('salary_amount')<div class="err" role="alert">{{ $message }}</div>@enderror
          <label for="invite-salary-notes">Notes</label>
          <input id="invite-salary-notes" name="salary_notes" value="{{ old('salary_notes') }}">
        </fieldset>
        @endif
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
            @if(!empty($canManageStaff) && $roles->isNotEmpty())
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
            @else
              @foreach($member['roles'] as $role)
                <span class="pill">{{ $role['label'] }}@if(!empty($role['class'])) · {{ $role['class'] }}@endif</span>
              @endforeach
            @endif
          </td>
          <td>{{ $user->status }}</td>
          <td>
            <a href="{{ route('app.staff.show', $user) }}">Profile</a>
            @if(!empty($canPrintId))
              · <a href="{{ route('app.staff.id', $user) }}">ID card</a>
            @endif
            @if(!empty($canManageStaff) && (int) $user->id !== (int) auth()->id())
              <form method="post" action="{{ route('app.staff.revoke', $user) }}" onsubmit="return confirm('Revoke school access for {{ $user->full_name }}?')" style="margin-top:8px">
                @csrf @method('DELETE')
                <button type="submit" class="btn ghost" style="color:var(--danger,#b42318)">Revoke</button>
              </form>
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
    function staffKind() {
      var el = document.querySelector('#invite-staff-form input[name="staff_kind"]:checked');
      return el ? el.value : '';
    }
    function syncInviteHomeroom() {
      var kind = staffKind();
      document.querySelectorAll('#invite-staff-form .js-teaching-role').forEach(function (wrap) {
        wrap.hidden = kind === 'non_teaching';
        if (kind === 'non_teaching') {
          wrap.querySelectorAll('input').forEach(function (cb) { cb.checked = false; });
        }
      });
      var field = document.getElementById('homeroom-field');
      if (!field) return;
      var checked = Array.from(document.querySelectorAll('#invite-staff-form .js-role-key:checked')).some(function (el) {
        return el.value === 'class_teacher';
      });
      field.hidden = !checked;
      var teach = document.getElementById('teaching-field');
      if (teach) {
        teach.hidden = kind !== 'teaching' || !Array.from(document.querySelectorAll('#invite-staff-form .js-role-key:checked')).some(function (el) {
          return el.value === 'subject_teacher';
        });
      }
    }
    document.querySelectorAll('#invite-staff-form .js-role-key, #invite-staff-form .js-staff-kind').forEach(function (el) {
      el.addEventListener('change', syncInviteHomeroom);
    });
    syncInviteHomeroom();

    var addBtn = document.getElementById('add-teach-row');
    var rows = document.getElementById('teaching-rows');
    if (addBtn && rows) {
      addBtn.addEventListener('click', function () {
        var first = rows.querySelector('.js-teach-row');
        if (!first) return;
        var clone = first.cloneNode(true);
        var idx = rows.querySelectorAll('.js-teach-row').length;
        clone.querySelectorAll('[name]').forEach(function (el) {
          el.name = el.name.replace(/teaching_assignments\[\d+]/, 'teaching_assignments[' + idx + ']');
          if (el.type === 'checkbox') el.checked = false;
          if (el.tagName === 'SELECT') el.selectedIndex = 0;
        });
        rows.appendChild(clone);
      });
    }

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
