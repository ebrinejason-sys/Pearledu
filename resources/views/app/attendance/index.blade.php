@extends('layouts.app')
@section('title','Attendance · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Attendance</h2>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="card">
    <form method="get" action="{{ route('app.attendance.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
      <div>
        <label>Class</label>
        <select name="class_id" onchange="this.form.submit()">
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected($classId === $c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Date</label>
        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()">
      </div>
    </form>
  </div>

  @if($students->isNotEmpty() && !empty($canMark))
  <div class="card">
    <form method="post" action="{{ route('app.attendance.store') }}" data-offline-queue="attendance">
      @csrf
      <input type="hidden" name="class_id" value="{{ $classId }}">
      <input type="hidden" name="attended_on" value="{{ $date }}">
      <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
        <input type="checkbox" name="notify_absent" value="1" checked> SMS guardians on absent
      </label>
      <table class="reg-table">
        <thead><tr><th>Student</th><th>Status</th><th>Reason</th></tr></thead>
        <tbody>
        @foreach($students as $i => $student)
          @php($rec = $existing->get($student->id))
          <tr>
            <td class="reg-sticky">
              <div class="reg-person">
                @include('app.partials.person-face', ['url' => $student->photoUrl(), 'initial' => $student->photoInitial(), 'name' => $student->full_name])
                <span>{{ $student->full_name }}</span>
              </div>
              <input type="hidden" name="records[{{ $i }}][student_id]" value="{{ $student->id }}">
            </td>
            <td>
              <div class="att-btns" role="group" aria-label="Attendance for {{ $student->full_name }}">
                @foreach(['present','absent','late','excused'] as $st)
                  <label class="att-btn att-btn--{{ $st }}">
                    <input type="radio" name="records[{{ $i }}][status]" value="{{ $st }}" @checked(($rec?->status ?? 'present') === $st)>
                    <span>{{ ucfirst($st) }}</span>
                  </label>
                @endforeach
              </div>
            </td>
            <td><input name="records[{{ $i }}][reason]" value="{{ $rec?->reason }}"></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <p style="margin-top:14px"><button class="btn accent ws-cta" type="submit">Save attendance</button></p>
    </form>
  </div>
  @elseif($students->isNotEmpty())
  <div class="card">
    <p class="ws-hint">Read-only register for this class.</p>
    <table class="reg-table">
      <thead><tr><th>Student</th><th>Status</th><th>Reason</th></tr></thead>
      <tbody>
      @foreach($students as $student)
        @php($rec = $existing->get($student->id))
        <tr>
          <td class="reg-sticky">
            <div class="reg-person">
              @include('app.partials.person-face', ['url' => $student->photoUrl(), 'initial' => $student->photoInitial(), 'name' => $student->full_name])
              <span>{{ $student->full_name }}</span>
            </div>
          </td>
          <td><span class="pill pill--{{ $rec?->status === 'present' ? 'success' : ($rec?->status === 'absent' ? 'danger' : 'warning') }}">{{ ucfirst($rec?->status ?? '—') }}</span></td>
          <td>{{ $rec?->reason ?: '—' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
@endsection
