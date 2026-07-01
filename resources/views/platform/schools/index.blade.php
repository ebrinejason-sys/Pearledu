@extends('layouts.app')
@section('title','Schools')
@section('content')
  <div style="display:flex;align-items:center;margin-bottom:14px"><h2 style="margin:0">Schools</h2>
    <span class="spacer"></span><a class="btn" href="{{ route('platform.schools.create') }}">+ Onboard school</a></div>
  <div class="card">
    <table>
      <thead><tr><th>School</th><th>Subdomain</th><th>Learners</th><th>Theme</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @foreach($schools as $s)
        <tr>
          <td><strong>{{ $s->name }}</strong></td>
          <td>{{ $s->slug }}.{{ config('tenancy.base_domain') }}</td>
          <td>{{ $s->students_count }}</td>
          <td><span class="pill">{{ $s->theme }}</span></td>
          <td>{{ $s->status }}</td>
          <td>
            <a href="{{ route('platform.schools.show', $s) }}">View</a>
            <form method="post" action="{{ route('platform.schools.enter', $s) }}" style="display:inline">@csrf<button class="btn ghost">Enter</button></form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
@endsection
