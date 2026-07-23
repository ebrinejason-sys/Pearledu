@extends('layouts.app')
@section('title','Hostel · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Operations</p><h2 class="page-header__title">Hostel</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Add room</h3>
      <form method="post" action="{{ route('app.hostel.rooms.store') }}">@csrf
        <label>Name</label><input name="name" required>
        <label>Capacity</label><input type="number" name="capacity" value="4" min="1">
        <p style="margin-top:14px"><button class="btn" type="submit">Save room</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Allocate</h3>
      <form method="post" action="{{ route('app.hostel.allocations.store') }}">@csrf
        <label>Room</label>
        <select name="room_id" required>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select>
        <label>Student</label>
        <select name="student_id" required>@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach</select>
        <label>Starts</label><input type="date" name="starts_on">
        <label>Ends</label><input type="date" name="ends_on">
        <p style="margin-top:14px"><button class="btn" type="submit">Allocate</button></p>
      </form>
    </div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Rooms</h3>
    <table>
      <thead><tr><th>Room</th><th>Capacity</th><th>Occupied</th></tr></thead>
      <tbody>
      @forelse($rooms as $r)
        <tr><td>{{ $r->name }}</td><td>{{ $r->capacity }}</td><td>{{ $r->allocations_count }}</td></tr>
      @empty
        <tr><td colspan="3" style="color:var(--muted)">No rooms.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Allocations</h3>
    <table>
      <thead><tr><th>Student</th><th>Room</th><th>Starts</th><th>Ends</th><th></th></tr></thead>
      <tbody>
      @forelse($allocations as $a)
        <tr>
          <td>{{ $a->student?->full_name }}</td>
          <td>{{ $a->room?->name }}</td>
          <td>{{ $a->starts_on?->format('Y-m-d') }}</td>
          <td>{{ $a->ends_on?->format('Y-m-d') ?? 'current' }}</td>
          <td>
            @unless($a->ends_on)
              <form method="post" action="{{ route('app.hostel.allocations.vacate', $a) }}">@csrf
                <button class="btn ghost" type="submit">Vacate</button>
              </form>
            @endunless
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No allocations.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
