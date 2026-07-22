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
      <h3 style="margin-top:0">Routes</h3>
      <table>
        <thead><tr><th>Name</th><th>Vehicle</th><th>Fee</th></tr></thead>
        <tbody>
        @forelse($routes as $route)
          <tr><td>{{ $route->name }}</td><td>{{ $route->vehicle ?: '—' }}</td><td>{{ $route->fee ?? '—' }}</td></tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">No routes.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
