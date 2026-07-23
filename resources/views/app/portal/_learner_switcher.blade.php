@php
  $days = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
@endphp
@if($learners->count() > 1)
  <form method="get" class="card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-bottom:16px">
    <div style="min-width:220px">
      <label>Learner</label>
      <select name="student_id" onchange="this.form.submit()">
        @foreach($learners as $learner)
          <option value="{{ $learner->id }}" @selected((int)$student?->id === (int)$learner->id)>{{ $learner->full_name }}@if($learner->schoolClass) — {{ $learner->schoolClass->name }}@endif</option>
        @endforeach
      </select>
    </div>
  </form>
@elseif($student)
  <p class="page-header__eyebrow" style="margin-bottom:12px">{{ $student->full_name }}@if($student->schoolClass) · {{ $student->schoolClass->name }}@endif</p>
@endif
