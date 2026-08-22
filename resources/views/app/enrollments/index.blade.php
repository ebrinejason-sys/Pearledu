@extends('layouts.app')
@section('title','Enrollments · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Learners</p>
      <h2 class="page-header__title">Enrollments</h2>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Choose the class and residence. The bursar’s saved fee types for that class and residence (plus any class-wide extras) are billed automatically.</p>
    </div>
    <div class="page-header__actions">
      <button type="button" class="btn accent" data-open-modal="enroll-modal">Enroll student</button>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <dialog class="pe-modal pe-modal--form" id="enroll-modal" @if($errors->any()) open @endif>
    <form method="post" action="{{ route('app.enrollments.store') }}" class="pe-modal__card" id="enroll-form">
      @csrf
      <h3 style="margin-top:0">Enroll student</h3>
      <label>Student</label>
      <select name="student_id" id="enroll-student" required>
        @foreach($students as $s)
          <option value="{{ $s->id }}" data-residency="{{ $s->residency ?: 'day' }}" data-class="{{ $s->class_id }}" @selected((string) old('student_id') === (string) $s->id)>{{ $s->full_name }}</option>
        @endforeach
      </select>
      <label>Class</label>
      <select name="class_id" id="enroll-class" required>
        @foreach($classes as $c)<option value="{{ $c->id }}" @selected((string) old('class_id') === (string) $c->id)>{{ $c->displayName() }}</option>@endforeach
      </select>
      <label>Residence</label>
      <select name="residency" id="enroll-residency" required>
        <option value="day" @selected(old('residency', 'day') === 'day')>Day</option>
        <option value="boarding" @selected(old('residency') === 'boarding')>Boarding</option>
      </select>
      @error('residency')<div class="err">{{ $message }}</div>@enderror
      <label>Academic year</label>
      <select name="academic_year_id" required>
        @foreach($years as $y)<option value="{{ $y->id }}" @selected((string) old('academic_year_id') === (string) $y->id)>{{ $y->name }}</option>@endforeach
      </select>
      <div class="card" style="margin:12px 0 0;padding:12px">
        <strong>Will be billed</strong>
        <ul id="enroll-preview" style="margin:8px 0 0;padding-left:18px;color:var(--muted);font-size:14px"></ul>
      </div>
      <p style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
        <button class="btn ghost" type="button" data-close-modal>Cancel</button>
        <button class="btn accent" type="submit">Enroll and bill</button>
      </p>
    </form>
  </dialog>

  <div class="card">
    <h3 style="margin-top:0">Records</h3>
    <table>
      <thead><tr><th>Student</th><th>Class</th><th>Year</th><th>Status</th></tr></thead>
      <tbody>
      @forelse($enrollments as $e)
        <tr>
          <td>
            <a class="learner-name" href="{{ $e->student ? route('app.students.show', $e->student) : '#' }}">
              @if($e->student?->photoUrl())
                <img src="{{ $e->student->photoUrl() }}" alt="" class="learner-avatar">
              @else
                <span class="learner-avatar learner-avatar--empty" aria-hidden="true"></span>
              @endif
              {{ $e->student?->full_name }}
            </a>
          </td>
          <td>{{ $e->schoolClass?->name }}</td>
          <td>{{ $e->academicYear?->name }}</td>
          <td><span class="pill">{{ $e->status }}</span></td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No enrollments.</td></tr>
      @endforelse
      </tbody>
    </table>
    {{ $enrollments->links() }}
  </div>
@endsection
@section('head')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var catalogue = @json($feeCatalogue);
    var student = document.getElementById('enroll-student');
    var klass = document.getElementById('enroll-class');
    var residency = document.getElementById('enroll-residency');
    var preview = document.getElementById('enroll-preview');
    function render() {
      if (!preview) return;
      var classId = klass ? parseInt(klass.value, 10) : 0;
      var res = residency ? residency.value : 'day';
      var rows = catalogue.filter(function (row) {
        if (row.class_id && row.class_id !== classId) return false;
        return row.residency === 'any' || row.residency === res;
      });
      preview.innerHTML = rows.length
        ? rows.map(function (row) {
            return '<li>' + row.name + ' · ' + row.kind + ' · UGX ' + Number(row.amount).toLocaleString() + '</li>';
          }).join('')
        : '<li>No matching class fee types yet. The bursar should save day/boarding (or other) types for this class.</li>';
    }
    if (student) student.addEventListener('change', function () {
      var opt = student.options[student.selectedIndex];
      if (opt && residency && opt.getAttribute('data-residency')) residency.value = opt.getAttribute('data-residency');
      if (opt && klass && opt.getAttribute('data-class')) klass.value = opt.getAttribute('data-class');
      render();
    });
    if (klass) klass.addEventListener('change', render);
    if (residency) residency.addEventListener('change', render);
    render();
    var modal = document.getElementById('enroll-modal');
    @if($errors->any())
    if (modal && modal.showModal && !modal.open) modal.showModal();
    @endif
  });
</script>
@endsection
