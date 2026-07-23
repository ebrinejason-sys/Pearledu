@extends('layouts.app')
@section('title','Transport · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Operations</p><h2 class="page-header__title">Transport</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Add route</h3>
      <form method="post" action="{{ route('app.transport.routes.store') }}">@csrf
        <label>Name</label><input name="name" required>
        <label>Vehicle</label><input name="vehicle">
        <label>Fee</label><input type="number" step="0.01" name="fee">
        <p style="margin-top:14px"><button class="btn" type="submit">Save</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Assign student</h3>
      <form method="post" action="{{ route('app.transport.allocations.store') }}">@csrf
        <label>Route</label>
        <select name="route_id" required>
          @foreach($routes as $route)
            <option value="{{ $route->id }}">{{ $route->name }}</option>
          @endforeach
        </select>
        <label>Student</label>
        <select name="student_id" required>
          @foreach($students as $s)
            <option value="{{ $s->id }}">{{ $s->full_name }}</option>
          @endforeach
        </select>
        <label>Starts</label>
        <input type="date" name="starts_on" value="{{ old('starts_on', now()->toDateString()) }}">
        <p style="margin-top:14px"><button class="btn" type="submit">Assign</button></p>
      </form>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Routes</h3>
    <table>
      <thead><tr><th>Name</th><th>Vehicle</th><th>Fee</th><th>Occupied</th></tr></thead>
      <tbody>
      @forelse($routes as $route)
        <tr>
          <td>{{ $route->name }}</td>
          <td>{{ $route->vehicle ?: '—' }}</td>
          <td>{{ $route->fee ?? '—' }}</td>
          <td>{{ $route->allocations_count }}</td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No routes.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Allocations</h3>
    <table>
      <thead><tr><th>Student</th><th>Route</th><th>Starts</th><th>Ends</th><th></th></tr></thead>
      <tbody>
      @forelse($allocations as $a)
        <tr>
          <td>{{ $a->student?->full_name }}</td>
          <td>{{ $a->route?->name }}</td>
          <td>{{ $a->starts_on?->format('Y-m-d') ?? $a->starts_on }}</td>
          <td>{{ $a->ends_on?->format('Y-m-d') ?? ($a->ends_on ?: 'current') }}</td>
          <td>
            @unless($a->ends_on)
              <form method="post" action="{{ route('app.transport.allocations.end', $a) }}">@csrf
                <button class="btn ghost" type="submit">End</button>
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
