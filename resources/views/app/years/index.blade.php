@extends('layouts.app')
@section('title','Academic years · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Academic years</h2>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Add year</h3>
      <form method="post" action="{{ route('app.years.store') }}">
        @csrf
        <label>Name</label>
        <input name="name" value="{{ old('name') }}" required>
        @error('name')<div class="err">{{ $message }}</div>@enderror
        <label>Starts</label>
        <input type="date" name="starts_on" value="{{ old('starts_on') }}" required>
        <label>Ends</label>
        <input type="date" name="ends_on" value="{{ old('ends_on') }}" required>
        <label style="display:flex;align-items:center;gap:8px;margin-top:10px">
          <input type="checkbox" name="is_current" value="1" @checked(old('is_current'))> Current year
        </label>
        <p style="margin-top:14px"><button class="btn" type="submit">Save year</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Years</h3>
      <table>
        <thead><tr><th>Name</th><th>Dates</th><th>Terms</th><th></th></tr></thead>
        <tbody>
        @forelse($years as $year)
          <tr>
            <td><strong>{{ $year->name }}</strong> @if($year->is_current)<span class="pill">current</span>@endif</td>
            <td>{{ $year->starts_on?->format('Y-m-d') }} → {{ $year->ends_on?->format('Y-m-d') }}</td>
            <td>{{ $year->terms->pluck('name')->implode(', ') ?: '—' }}</td>
            <td>
              <form method="post" action="{{ route('app.years.terms.store', $year) }}" style="display:flex;gap:6px;align-items:end">
                @csrf
                <input name="name" placeholder="Term name" required style="min-width:90px">
                <input name="sequence" type="number" min="1" value="1" style="width:60px">
                <button class="btn" type="submit">Add term</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" style="color:var(--muted)">No years yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
