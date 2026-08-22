@extends('layouts.app')
@section('title', 'My portal')
@section('content')
  <div class="page-header">
    <div class="ws-greet">
      @if($student?->photoUrl())
        @include('app.partials.person-face', ['url' => $student->photoUrl(), 'initial' => $student->photoInitial(), 'name' => $student->full_name, 'size' => 'lg'])
      @elseif($student)
        @include('app.partials.person-face', ['url' => null, 'initial' => $student->photoInitial(), 'name' => $student->full_name, 'size' => 'lg'])
      @endif
      <div>
        <p class="page-header__eyebrow">{{ $school?->name }}</p>
        <h1 class="page-header__title">{{ $student ? 'Good day, '.$student->firstName() : 'My portal' }}</h1>
        <p class="ws-mantra">{{ !empty($isStudent) ? 'Your timetable, results, and statement.' : 'Your child’s attendance, results, fees, and timetable.' }}</p>
      </div>
    </div>
  </div>

  @if($learners->count() > 1)
    <h2 class="ws-sub">My children</h2>
    <div class="child-cards">
      @foreach($learners as $learner)
        @php
          $dot = $latestAttendance[$learner->id] ?? null;
        @endphp
        <a class="child-card" href="{{ route('app.portal.home', ['student_id' => $learner->id]) }}" @if($student && (int) $student->id === (int) $learner->id) aria-current="true" @endif>
          @include('app.partials.person-face', ['url' => $learner->photoUrl(), 'initial' => $learner->photoInitial(), 'name' => $learner->full_name, 'size' => 'lg'])
          <strong>{{ $learner->full_name }}</strong>
          <span>{{ $learner->schoolClass?->displayName() ?? 'Class not set' }}</span>
          @if($dot)
            <span class="att-dot att-dot--{{ $dot->status }}">{{ $dot->status }}</span>
          @endif
        </a>
      @endforeach
    </div>
  @elseif($student)
    @php
      $dot = $latestAttendance[$student->id] ?? null;
    @endphp
    <p class="page-header__eyebrow" style="margin-bottom:12px">
      {{ $student->full_name }}@if($student->schoolClass) · {{ $student->schoolClass->displayName() }}@endif
      @if($dot)
        <span class="att-dot att-dot--{{ $dot->status }}">{{ $dot->status }}</span>
      @endif
    </p>
  @endif

  @if(! $student)
    <div class="card"><p>No linked learner yet. Ask the school to link your account to a student.</p></div>
  @else
    <div class="portal-tiles">
      <a class="portal-tile" href="{{ route('app.portal.results', ['student_id' => $student->id]) }}">
        <strong>Results</strong>
        <span>Own scores only — no ranks, no classmates.</span>
      </a>
      @if(!empty($canViewAttendance))
        <a class="portal-tile" href="{{ route('app.portal.attendance', ['student_id' => $student->id]) }}">
          <strong>Attendance</strong>
          <span>Present, late, and absent days.</span>
        </a>
      @endif
      @if(!empty($canViewFees))
        <a class="portal-tile" href="{{ route('app.portal.fees', ['student_id' => $student->id]) }}">
          <strong>Fees</strong>
          <span>{{ !empty($canPay) ? 'View statement and pay' : 'Your statement (read only)' }}</span>
        </a>
      @endif
      <a class="portal-tile" href="{{ route('app.portal.timetable', ['student_id' => $student->id]) }}">
        <strong>Timetable</strong>
        <span>Today first on a phone.</span>
      </a>
    </div>

    @if(!empty($canViewFees))
      @php $paidPct = $feeExpected > 0 ? (int) round((($feeExpected - $feeBalance) / $feeExpected) * 100) : 0; @endphp
      <div class="card">
        <h2 style="margin-top:0;font-size:18px">Fees</h2>
        <div class="dash-bar" role="img" aria-label="Fees {{ $paidPct }} percent cleared">
          <div class="dash-bar__meta"><span>Cleared</span><strong>{{ $paidPct }}%</strong></div>
          <div class="dash-bar__track"><span style="width:{{ $paidPct }}%"></span></div>
        </div>
        @if(!empty($canPay))
          <p style="margin:12px 0 0"><a class="btn accent ws-cta" href="{{ route('app.portal.fees', ['student_id' => $student->id]) }}">Pay</a></p>
        @endif
      </div>
    @endif

    <div class="grid g2" style="margin-top:8px">
      <div class="card">
        <h2 style="margin-top:0;font-size:18px">Recent results</h2>
        @forelse($resultsPreview as $mark)
          <p style="margin:0 0 8px">{{ $mark->subject?->name ?? 'Subject' }} — <strong>{{ $mark->score }}</strong> @if($mark->grade)({{ $mark->grade }})@endif</p>
        @empty
          <p class="ws-hint">No marks published yet.</p>
        @endforelse
      </div>
      <div class="card">
        <h2 style="margin-top:0;font-size:18px">Today on the timetable</h2>
        @forelse($todaySlots ?? [] as $slot)
          <p style="margin:0 0 8px">{{ $slot->period?->name ?? 'Period' }} · {{ $slot->subject?->name ?? '—' }}</p>
        @empty
          <p class="ws-hint">Nothing listed for today.</p>
        @endforelse
        <p style="margin:12px 0 0"><a href="{{ route('app.portal.timetable', ['student_id' => $student->id]) }}">Full timetable</a></p>
      </div>
    </div>

    @if(!empty($isParent) && !empty($classTeacher))
      <div class="card" style="margin-top:8px">
        <h2 style="margin-top:0;font-size:18px">Message class teacher</h2>
        <div class="face-chip" style="margin-bottom:10px">
          @include('app.partials.person-face', ['url' => $classTeacher->avatarUrl(), 'initial' => $classTeacher->avatarInitial(), 'name' => $classTeacher->full_name])
          <span>{{ $classTeacher->full_name }}</span>
        </div>
        <form method="post" action="{{ route('app.helpdesk.store') }}">
          @csrf
          <input type="hidden" name="category" value="class_teacher">
          <input type="hidden" name="student_id" value="{{ $student->id }}">
          <label>Subject <input name="subject" required value="Message for {{ $student->full_name }}"></label>
          <label>Note <textarea name="body" rows="3" required placeholder="The class teacher is the school’s point of contact."></textarea></label>
          <p style="margin-top:12px"><button class="btn accent ws-cta" type="submit">Send to class teacher</button></p>
        </form>
      </div>
    @elseif(!empty($isParent))
      <div class="card" style="margin-top:8px">
        <p class="ws-hint">No class teacher is linked to this class yet. Open Helpdesk if you need the office.</p>
        <a class="btn ghost ws-cta" href="{{ route('app.helpdesk.index') }}">Helpdesk</a>
      </div>
    @endif
  @endif
@endsection
