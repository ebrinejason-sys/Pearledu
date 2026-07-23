@extends('layouts.app')
@section('title','Schools')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Organisation</p>
      <h2 class="page-header__title">Schools</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">
        Each school is a tenant. Open to edit/delete, or <strong>Enter workspace</strong> to manage its data.
        School users always sign in at <code>/login</code>.
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
          <th>Tenant ID</th>
          <th>District</th>
          <th>Learners</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($schools as $s)
        <tr>
          <td><strong>{{ $s->name }}</strong></td>
          <td><code>{{ $s->tenantId() }}</code></td>
          <td>{{ $s->district ?: '—' }}</td>
          <td>{{ number_format($s->students_count) }}</td>
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
