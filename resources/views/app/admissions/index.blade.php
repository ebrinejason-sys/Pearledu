@extends('layouts.app')
@section('title','Admissions · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Learners</p><h2 class="page-header__title">Admissions</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="card">
    <h3 style="margin-top:0">New application</h3>
    <form method="post" action="{{ route('app.admissions.store') }}">@csrf
      <label>Applicant</label><input name="applicant_name" required>
      <label>Guardian</label><input name="guardian_name">
      <label>Guardian phone</label><input name="guardian_phone">
      <label>Guardian email</label><input type="email" name="guardian_email">
      <label>Requested class</label>
      <select name="requested_class_id"><option value="">—</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
      <label>Notes</label><textarea name="notes" rows="3"></textarea>
      <p style="margin-top:14px"><button class="btn" type="submit">Save</button></p>
    </form>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Applications</h3>
    <table>
      <thead><tr><th>Applicant</th><th>Class</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($applications as $app)
        <tr>
          <td>{{ $app->applicant_name }}</td>
          <td>{{ $app->requestedClass?->name ?: '—' }}</td>
          <td><span class="pill">{{ $app->status }}</span></td>
          <td style="display:flex;gap:6px">
            @foreach(['accepted','rejected','enrolled'] as $st)
              <form method="post" action="{{ route('app.admissions.decide', $app) }}">@csrf
                <input type="hidden" name="status" value="{{ $st }}">
                <button class="btn" type="submit">{{ ucfirst($st) }}</button>
              </form>
            @endforeach
          </td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No applications.</td></tr>
      @endforelse
      </tbody>
    </table>
    {{ $applications->links() }}
  </div>
@endsection
