@extends('layouts.app')
@section('title','Admissions · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Learners</p>
      <h2 class="page-header__title">Admissions</h2>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Admit Student creates the learner, sets class and residence, and bills the bursar’s matching fee types.</p>
    </div>
    <div class="page-header__actions">
      <button type="button" class="btn accent" data-open-modal="admit-new-modal">New application</button>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <dialog class="pe-modal pe-modal--form" id="admit-new-modal">
    <form method="post" action="{{ route('app.admissions.store') }}" class="pe-modal__card">
      @csrf
      <h3 style="margin-top:0">New application</h3>
      <label>Applicant</label><input name="applicant_name" required>
      <label>Guardian</label><input name="guardian_name">
      <label>Guardian phone</label><input name="guardian_phone">
      <label>Guardian email</label><input type="email" name="guardian_email">
      <label>Requested class</label>
      <select name="requested_class_id"><option value="">—</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
      <label>Notes</label><textarea name="notes" rows="3"></textarea>
      <p style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
        <button class="btn ghost" type="button" data-close-modal>Cancel</button>
        <button class="btn" type="submit">Save</button>
      </p>
    </form>
  </dialog>

  <div class="card">
    <h3 style="margin-top:0">Applications</h3>
    <table>
      <thead><tr><th>Applicant</th><th>Class</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($applications as $app)
        <tr>
          <td>
            {{ $app->applicant_name }}
            @if($app->student)
              <div style="font-size:12px;color:var(--muted)">Learner #{{ $app->student->id }} · <a href="{{ route('app.students.show', $app->student) }}">Open record</a></div>
            @endif
          </td>
          <td>{{ $app->requestedClass?->name ?: '—' }}</td>
          <td><span class="pill">{{ $app->status }}</span></td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            @if($app->status !== 'enrolled')
              @foreach(['accepted','rejected'] as $st)
                <form method="post" action="{{ route('app.admissions.decide', $app) }}">@csrf
                  <input type="hidden" name="decision" value="{{ $st }}">
                  <button class="btn ghost" type="submit">{{ ucfirst($st) }}</button>
                </form>
              @endforeach
              <button type="button" class="btn accent" data-open-modal="admit-{{ $app->id }}">Admit Student</button>
              <dialog class="pe-modal pe-modal--form" id="admit-{{ $app->id }}">
                <form method="post" action="{{ route('app.admissions.decide', $app) }}" class="pe-modal__card" enctype="multipart/form-data">
                  @csrf
                  <h3 style="margin-top:0">Admit {{ $app->applicant_name }}</h3>
                  <input type="hidden" name="decision" value="enrolled">
                  <label>Class</label>
                  <select name="class_id">
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" @selected((int) $c->id === (int) $app->requested_class_id)>{{ $c->displayName() }}</option>
                    @endforeach
                  </select>
                  <label>Residence</label>
                  <select name="residency" required>
                    <option value="day">Day</option>
                    <option value="boarding">Boarding</option>
                  </select>
                  <label>Profile photo</label>
                  <input type="file" name="photo" accept="image/*" capture="user">
                  <p style="color:var(--muted);font-size:13px;margin:8px 0 0">Matching day or boarding class fees (and other class-wide types) are billed on admit.</p>
                  <p style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
                    <button class="btn ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn accent" type="submit">Admit and bill</button>
                  </p>
                </form>
              </dialog>
            @else
              <span style="color:var(--muted);font-size:13px">Already admitted</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No applications.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
