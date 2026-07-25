@extends('layouts.app')
@section('title', $school->name.' workspace')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">School workspace</p>
      <h2 class="page-header__title">{{ $school->name }}</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px;max-width:62ch">
        Enter students, classes, and staff for this school without imitating a school user.
        Changes are audited as platform actions.
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn" href="{{ route('platform.students.create') }}">Add student</a>
      <a class="btn ghost" href="{{ route('platform.schools.show', $school) }}">School details</a>
      <form method="post" action="{{ route('platform.schools.leave') }}">
        @csrf
        <button class="btn ghost" type="submit">Exit workspace</button>
      </form>
    </div>
  </div>

  @if(session('status'))<div class="status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @error('school')<div class="err" style="margin-bottom:16px">{{ $message }}</div>@enderror

  <div class="grid g4">
    <div class="card stat"><div class="l">Students</div><div class="v">{{ number_format($stats['students']) }}</div></div>
    <div class="card stat"><div class="l">Classes</div><div class="v">{{ number_format($stats['classes']) }}</div></div>
    <div class="card stat"><div class="l">Staff accounts</div><div class="v">{{ number_format($stats['staff']) }}</div></div>
    <div class="card stat"><div class="l">Open invites</div><div class="v">{{ number_format($stats['open_invites']) }}</div></div>
  </div>

  @if($stats['students'] === 0 || $stats['classes'] === 0 || $stats['staff'] === 0)
    <div class="card" style="margin-bottom:16px">
      <h3 style="margin:0 0 8px">Finish setting up this workspace</h3>
      <p style="margin:0;color:var(--muted);font-size:13px;line-height:1.55">
        @if($stats['classes'] === 0)
          <a href="{{ route('platform.classes.index') }}">Create the first class</a> so learners can be placed correctly.
        @elseif($stats['students'] === 0)
          <a href="{{ route('platform.students.create') }}">Add the first student</a> to begin the school register.
        @elseif($stats['staff'] === 0)
          <a href="{{ route('platform.staff.index') }}">Invite the first staff member</a> to give the school access.
        @endif
      </p>
    </div>
  @endif

  <div class="grid g2">
    <a class="card dash-action" href="{{ route('platform.students.index') }}">
      <h3>Students</h3>
      <p>Create and edit learner records, assign classes, and link or invite guardians.</p>
    </a>
    <a class="card dash-action" href="{{ route('platform.classes.index') }}">
      <h3>Classes</h3>
      <p>Add class streams under the school’s offered levels for student placement.</p>
    </a>
    <a class="card dash-action" href="{{ route('platform.staff.index') }}">
      <h3>Staff &amp; invites</h3>
      <p>Invite school admins, teachers, and bursars. Resend open invitations from here or Operations.</p>
    </a>
    <a class="card dash-action" href="{{ route('platform.sms.index') }}">
      <h3>SMS credits</h3>
      <p>Current school balance: <strong>{{ number_format($stats['sms_balance']) }}</strong> credits. Top up from SMS &amp; credits.</p>
    </a>
  </div>
@endsection

@section('head')
<style>
  a.dash-action{display:block;color:inherit;transition:border-color .15s ease,box-shadow .15s ease}
  a.dash-action:hover{border-color:var(--brand);box-shadow:0 8px 24px rgba(5,63,92,.08)}
  a.dash-action h3{margin:0 0 8px;font-size:16px;color:var(--brand)}
  a.dash-action p{margin:0;font-size:13px;line-height:1.55;color:var(--muted)}
</style>
@endsection
