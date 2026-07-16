@extends('layouts.app')
@section('title','Schools')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Organisation</p>
      <h2 class="page-header__title">Schools</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">
        Open a school to edit details, or <strong>Enter workspace</strong> to create students, classes, and staff.
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn accent" href="{{ route('platform.schools.create') }}">Onboard school</a>
    </div>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>School</th>
          <th>Subdomain</th>
          <th>Learners</th>
          <th>Theme</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($schools as $s)
        <tr>
          <td><strong>{{ $s->name }}</strong></td>
          <td>{{ $s->slug }}.{{ config('tenancy.base_domain') }}</td>
          <td>{{ number_format($s->students_count) }}</td>
          <td><span class="pill">{{ $s->theme }}</span></td>
          <td><span class="pill">{{ $s->status }}</span></td>
          <td style="white-space:nowrap">
            <a href="{{ route('platform.schools.show', $s) }}">Open</a>
            ·
            <form method="post" action="{{ route('platform.schools.enter', $s) }}" style="display:inline">
              @csrf
              <button type="submit" style="background:none;border:0;padding:0;color:var(--brand);font:inherit;font-weight:600;cursor:pointer">Enter workspace</button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="color:var(--muted)">
            No schools yet.
            <a href="{{ route('platform.schools.create') }}">Onboard the first school</a>.
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
