@extends('layouts.app')
@section('title','Staff · '.$school->name)
@php
  $roleHints = [
    'subject_teacher' => 'What they teach, and to which class. Used to build the timetable.',
    'class_teacher' => 'Homeroom for one class — attendance, parents, pastoral.',
    'director_of_studies' => 'Years, subjects, timetable, and marksheets.',
    'head_teacher' => 'School operations. No fee or mark writes.',
    'deputy_head_teacher' => 'Operations alongside the Head Teacher.',
    'director' => 'Census and oversight. No transactional writes.',
    'bursar' => 'Fees, receipts, and payroll amounts.',
    'secretary' => 'Staff IDs, clock, and front office.',
    'school_admin' => 'Full school operations.',
    'parent' => 'Linked children only.',
    'student' => 'Own learner portal.',
  ];
@endphp
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">People</p>
      <h1 class="page-header__title">Staff members</h1>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Add people and choose their responsibilities. A Teacher can cover many subjects and classes — classify the load so the timetable does not collide.</p>
    </div>
    <div class="page-header__actions">
      @if($roles->isNotEmpty())
        <button type="button" class="btn accent" data-open-modal="staff-invite-modal">Add staff member</button>
      @endif
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
    @if($roles->isNotEmpty())
  <dialog class="pe-modal pe-modal--wide" id="staff-invite-modal">
    <div class="pe-modal__card">
      <h2 style="margin-top:0;font-size:18px">Add staff member</h2>
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
          <p style="margin:4px 0 10px;font-size:13px;color:var(--muted)">Tick every role this person should hold. Teaching staff must include Teacher and/or Class Teacher. One person may be a teacher and a class teacher at the same time.</p>
          <div class="role-picks">
            @foreach($roles as $role)
              <label class="role-pick js-role-wrap{{ in_array($role->key, ['subject_teacher', 'class_teacher'], true) ? ' js-teaching-role' : '' }}">
                <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" class="js-role-key" @checked(collect(old('role_keys', []))->contains($role->key))>
                <strong>{{ $role->label }}</strong>
                <span>{{ $roleHints[$role->key] ?? 'School responsibility' }}</span>
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
          <h3 style="font-size:15px;margin:16px 0 0">Teaching load</h3>
          <p style="color:var(--muted);font-size:13px;margin:0 0 8px">Required when Teacher is selected. One person can teach different subjects to different classes so the timetable does not collide.</p>
          @include('app.teaching._load-builder', [
            'builderId' => 'invite-load',
            'subjects' => $subjects ?? collect(),
            'classes' => $classes,
          ])
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
        <p style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
          <button class="btn ghost" type="button" data-close-modal>Cancel</button>
          <button class="btn" type="submit">Send invitation</button>
        </p>
      </form>
    </div>
  </dialog>
    @endif

    <div class="card staff-invites">
      <h2 style="margin-top:0;font-size:18px">Open invitations</h2>
      <p style="color:var(--muted);font-size:13px;margin:0 0 12px">Invited people cannot sign in until they open the email link and choose a password. Adding a role to the catalog is <code>php artisan db:seed --class=RoleSeeder</code>. If mail never arrived, activate with <code>php artisan invite:activate email@school.test --password='…'</code>.</p>
      @if($openInvites->isEmpty())
        <p style="color:var(--muted);margin:0">No open staff invites.</p>
      @else
        <div class="staff-grid staff-grid--invites">
          @foreach($openInvites as $invite)
            <div class="staff-card staff-card--invite staff-card--{{ $invite->role_key }}">
              <div class="staff-card__hero staff-card__hero--compact">
                <span class="staff-card__photo-lg staff-card__photo-lg--initial" aria-hidden="true">{{ $invite->user?->avatarInitial() ?? '?' }}</span>
                <div class="staff-card__identity">
                  <span class="staff-card__name">{{ $invite->user?->full_name ?? '—' }}</span>
                  <span class="staff-card__meta staff-card__meta--on-hero">{{ $invite->email ?: $invite->phone }}</span>
                </div>
              </div>
              <div class="staff-card__body">
                <span class="pill">{{ $invite->role_key }}</span>
                <p class="staff-card__meta" style="margin:8px 0 0">Expires {{ $invite->expires_at->diffForHumans() }}. They cannot log in yet.</p>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  @php
    $bands = [
      'leadership' => ['title' => 'Leadership', 'members' => collect()],
      'teaching' => ['title' => 'Teaching staff', 'members' => collect()],
      'office' => ['title' => 'Office and support', 'members' => collect()],
    ];
    foreach ($members as $member) {
      $band = $member['band'] ?? 'office';
      if (! isset($bands[$band])) {
        $band = 'office';
      }
      $bands[$band]['members']->push($member);
    }
  @endphp

  @foreach($bands as $bandKey => $band)
    @continue($band['members']->isEmpty())
    <section class="staff-band staff-band--{{ $bandKey }}" aria-labelledby="staff-band-{{ $bandKey }}">
      <h2 id="staff-band-{{ $bandKey }}" class="staff-band__title">
        {{ $band['title'] }}
        <span class="staff-band__count">{{ $band['members']->count() }}</span>
      </h2>
      <div class="staff-grid">
        @foreach($band['members'] as $member)
          @include('app.staff._card', ['member' => $member])
        @endforeach
      </div>
    </section>
  @endforeach
  @if($members->isEmpty())
    <div class="card" style="margin:0"><p style="color:var(--muted);margin:0">No staff members yet.</p></div>
  @endif
@endsection

@section('head')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    function checkedRoles(selector) {
      return Array.from(document.querySelectorAll(selector)).filter(function (el) { return el.checked; }).map(function (el) { return el.value; });
    }
    function staffKind() {
      var el = document.querySelector('#invite-staff-form input[name="staff_kind"]:checked');
      return el ? el.value : '';
    }
    function syncInvite() {
      var kind = staffKind();
      document.querySelectorAll('#invite-staff-form .js-teaching-role').forEach(function (wrap) {
        wrap.hidden = kind === 'non_teaching';
        if (kind === 'non_teaching') {
          wrap.querySelectorAll('input').forEach(function (cb) { cb.checked = false; });
        }
      });
      var keys = checkedRoles('#invite-staff-form .js-role-key');
      var homeroom = document.getElementById('homeroom-field');
      var teach = document.getElementById('teaching-field');
      if (homeroom) homeroom.hidden = keys.indexOf('class_teacher') === -1;
      if (teach) teach.hidden = kind !== 'teaching' || keys.indexOf('subject_teacher') === -1;
    }
    document.querySelectorAll('#invite-staff-form .js-role-key, #invite-staff-form .js-staff-kind').forEach(function (el) {
      el.addEventListener('change', syncInvite);
    });
    syncInvite();
    @if($errors->any())
    var inviteModal = document.getElementById('staff-invite-modal');
    if (inviteModal && inviteModal.showModal && !inviteModal.open) inviteModal.showModal();
    @endif

    document.querySelectorAll('.js-member-role').forEach(function (el) {
      el.addEventListener('change', function () {
        var uid = el.getAttribute('data-user');
        var keys = checkedRoles('.js-member-role[data-user="' + uid + '"]');
        var box = document.querySelector('.js-member-homeroom[data-user="' + uid + '"]');
        var teach = document.querySelector('.js-member-teach[data-user="' + uid + '"]');
        if (box) box.hidden = keys.indexOf('class_teacher') === -1;
        if (teach) teach.hidden = keys.indexOf('subject_teacher') === -1;
      });
    });
  });
</script>
@endsection
