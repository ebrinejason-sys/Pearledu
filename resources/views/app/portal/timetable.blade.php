@extends('layouts.app')
@section('title', 'Timetable')
@section('content')
  @php
    $dayNames = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
    $periods = $slots->pluck('period')->filter()->unique('id')->sortBy('sequence')->values();
    $grid = [];
    foreach ($slots as $slot) {
      $grid[$slot->day_of_week][$slot->period_id] = $slot;
    }
    $usedDays = $slots->pluck('day_of_week')->unique()->sort()->values();
    $todayIso = (int) now(config('app.timezone'))->isoWeekday();
    $usedDays = $usedDays->sortBy(fn ($d) => ((int) $d - $todayIso + 7) % 7)->values();
  @endphp
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school?->name }}</p>
      <h1 class="page-header__title">Timetable</h1>
    </div>
    <div class="page-header__actions"><a class="btn ghost" href="{{ route('app.portal.home', array_filter(['student_id' => $student?->id])) }}">Portal home</a></div>
  </div>
  @include('app.portal._learner_switcher')
  @if(!$student)
    <div class="card"><p>No linked learner yet. Ask the school to link your account to a student.</p></div>
  @else
  <div class="card">
    @if($slots->isEmpty() || $periods->isEmpty())
      <p style="color:var(--muted);margin:0">No timetable slots for this class yet.</p>
    @else
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>Day</th>
              @foreach($periods as $period)
                <th>{{ $period->name }}<br><span style="font-weight:400;color:var(--muted);font-size:12px">{{ $period->starts_at }}–{{ $period->ends_at }}</span></th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($usedDays as $dayNum)
              <tr class="{{ (int) $dayNum === $todayIso ? 'is-today' : '' }}">
                <td><strong>{{ $dayNames[$dayNum] ?? $dayNum }}</strong></td>
                @foreach($periods as $period)
                  @php($slot = $grid[$dayNum][$period->id] ?? null)
                  <td>
                    @if($slot)
                      <div>{{ $slot->subject?->name ?? '—' }}</div>
                      <div style="color:var(--muted);font-size:12px">{{ $slot->teacher?->full_name ?? '—' }}</div>
                      @if($slot->room)
                        <div style="color:var(--muted);font-size:12px">{{ $slot->room->name }}</div>
                      @endif
                    @else
                      <span style="color:var(--muted)">—</span>
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
  @endif
@endsection
