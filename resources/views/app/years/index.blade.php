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
        <label style="display:flex;align-items:center;gap:8px;margin-top:10px">
          <input type="checkbox" name="with_terms" value="1" @checked(old('with_terms'))> Also create Term I–III
        </label>
        <p style="margin-top:14px"><button class="btn" type="submit">Save year</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Years</h3>
      @forelse($years as $year)
        <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border, #e5e7eb)">
          <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between">
            <div>
              <strong>{{ $year->name }}</strong>
              @if($year->is_current)<span class="pill">current</span>@endif
              <div style="color:var(--muted);font-size:13px">{{ $year->starts_on?->format('Y-m-d') }} → {{ $year->ends_on?->format('Y-m-d') }}</div>
            </div>
            @unless($year->is_current)
              <form method="post" action="{{ route('app.years.current', $year) }}">
                @csrf
                <button class="btn" type="submit">Set current</button>
              </form>
            @endunless
          </div>

          <form method="post" action="{{ route('app.years.terms.store', $year) }}" style="display:flex;gap:6px;align-items:end;flex-wrap:wrap;margin-top:12px">
            @csrf
            <div>
              <label>Name</label>
              <input name="name" placeholder="Term name" required style="min-width:90px">
            </div>
            <div>
              <label>Seq</label>
              <input name="sequence" type="number" min="1" value="1" style="width:60px">
            </div>
            <div>
              <label>Starts</label>
              <input type="date" name="starts_on">
            </div>
            <div>
              <label>Ends</label>
              <input type="date" name="ends_on">
            </div>
            <button class="btn" type="submit">Add term</button>
          </form>

          @if($year->terms->isNotEmpty())
            <div style="margin-top:12px">
              @foreach($year->terms as $term)
                <form method="post" action="{{ route('app.years.terms.update', $term) }}" style="display:flex;gap:6px;align-items:end;flex-wrap:wrap;margin-bottom:8px">
                  @csrf
                  @method('PUT')
                  <div>
                    <label>Name</label>
                    <input name="name" value="{{ $term->name }}" required style="min-width:90px">
                  </div>
                  <div>
                    <label>Seq</label>
                    <input name="sequence" type="number" min="1" value="{{ $term->sequence }}" style="width:60px">
                  </div>
                  <div>
                    <label>Starts</label>
                    <input type="date" name="starts_on" value="{{ $term->starts_on?->format('Y-m-d') }}">
                  </div>
                  <div>
                    <label>Ends</label>
                    <input type="date" name="ends_on" value="{{ $term->ends_on?->format('Y-m-d') }}">
                  </div>
                  <button class="btn" type="submit">Save</button>
                </form>
              @endforeach
            </div>
          @else
            <p style="color:var(--muted);font-size:13px;margin:8px 0 0">No terms yet.</p>
          @endif
        </div>
      @empty
        <p style="color:var(--muted)">No years yet.</p>
      @endforelse
    </div>
  </div>
@endsection
