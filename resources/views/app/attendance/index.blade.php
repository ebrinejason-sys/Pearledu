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
      <table>
        <thead><tr><th>Student</th><th>Status</th><th>Reason</th></tr></thead>
        <tbody>
        @foreach($students as $i => $student)
          @php($rec = $existing->get($student->id))
          <tr>
            <td>
              {{ $student->full_name }}
              <input type="hidden" name="records[{{ $i }}][student_id]" value="{{ $student->id }}">
            </td>
            <td>
              <select name="records[{{ $i }}][status]">
                @foreach(['present','absent','late','excused'] as $st)
                  <option value="{{ $st }}" @selected(($rec?->status ?? 'present') === $st)>{{ ucfirst($st) }}</option>
                @endforeach
              </select>
            </td>
            <td><input name="records[{{ $i }}][reason]" value="{{ $rec?->reason }}"></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <p style="margin-top:14px"><button class="btn" type="submit">Save attendance</button></p>
    </form>
  </div>
  @elseif($students->isNotEmpty())
  <div class="card">
    <p style="margin:0 0 12px;color:var(--muted)">Read-only register for this class.</p>
    <table>
      <thead><tr><th>Student</th><th>Status</th><th>Reason</th></tr></thead>
      <tbody>
      @foreach($students as $student)
        @php($rec = $existing->get($student->id))
        <tr>
          <td>{{ $student->full_name }}</td>
          <td>{{ ucfirst($rec?->status ?? '—') }}</td>
          <td>{{ $rec?->reason ?: '—' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
@endsection
