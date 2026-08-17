@extends('layouts.app')
@section('title','My Class · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Homeroom</p>
      <h1 class="page-header__title">{{ $homeroom['class_name'] ?? 'My Class' }}</h1>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Pastoral overview for your assigned class. Mark entry still requires a teaching assignment.</p>
    </div>
    @if(!empty($homeroom))
    <div class="page-header__actions">
      <a class="btn" href="{{ route('app.attendance.index', ['class_id' => $homeroom['class_id']]) }}">Daily attendance</a>
      <a class="btn ghost" href="{{ route('app.students.index', ['class_id' => $homeroom['class_id']]) }}">Class roster</a>
    </div>
    @endif
  </div>

  @if(empty($homeroom))
    <div class="card"><p style="margin:0;color:var(--muted)">No homeroom class is linked to your Class Teacher role. Ask an administrator to set it under Staff.</p></div>
  @else
    <div class="workspace-kpis">
      <div class="dash-stat"><div class="dash-stat__value">{{ $homeroom['students'] }}</div><div class="dash-stat__label">Students</div></div>
      <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">{{ $homeroom['present'] }}</div><div class="dash-stat__label">Present today</div></div>
      <div class="dash-stat dash-stat--warning"><div class="dash-stat__value">{{ $homeroom['absent'] }}</div><div class="dash-stat__label">Absent</div></div>
      <div class="dash-stat"><div class="dash-stat__value">{{ $homeroom['late'] }}</div><div class="dash-stat__label">Late</div></div>
      <div class="dash-stat"><div class="dash-stat__value">{{ $homeroom['fees_total'] ? round(($homeroom['fees_cleared'] / max(1, $homeroom['fees_total'])) * 100) : 0 }}%</div><div class="dash-stat__label">Fees cleared</div></div>
    </div>

    <div class="card">
      <h2 style="margin-top:0;font-size:18px">Roster</h2>
      <table>
        <thead><tr><th>Student</th><th>Guardian</th><th></th></tr></thead>
        <tbody>
        @forelse($homeroom['roster'] as $student)
          <tr>
            <td><a href="{{ route('app.students.show', $student) }}">{{ $student->full_name }}</a></td>
            <td>
              @php($g = $student->guardianships->first()?->guardian)
              {{ $g?->full_name ?? '—' }}
              @if($g?->phone)<span style="color:var(--muted);font-size:12px"> · {{ $g->phone }}</span>@endif
            </td>
            <td><a class="btn ghost" href="{{ route('app.students.show', $student) }}">Profile</a></td>
          </tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">No learners in this class yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  @endif
@endsection

@section('head')
<style>
  .dash-stat{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:14px 16px;position:relative;overflow:hidden}
  .dash-stat::before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--brand)}
  .dash-stat--accent::before{background:var(--accent)}
  .dash-stat--warning::before{background:var(--warning)}
  .dash-stat__value{font-size:22px;font-weight:800;font-family:var(--font-display)}
  .dash-stat__label{margin-top:6px;font-size:13px;font-weight:700;color:var(--brand)}
</style>
@endsection
