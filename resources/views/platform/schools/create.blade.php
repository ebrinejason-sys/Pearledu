@extends('layouts.app')
@section('title','Onboard a school')
@section('content')
  <h2>Onboard a school</h2>
  <div class="card">
    <form method="post" action="{{ route('platform.schools.store') }}">
      @csrf
      <div class="grid g2">
        <div><label>School name</label><input name="name" value="{{ old('name') }}" required></div>
        <div>
          @include('platform.partials.district-picker', ['selected' => old('district')])
          @error('district')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div><label>EMIS number (optional)</label><input name="emis_number" value="{{ old('emis_number') }}"></div>
        <div><label>Theme</label>
          <select name="theme" id="school-theme">
            @foreach($themes as $key => $t)
              <option value="{{ $key }}" @selected(old('theme', 'pearledu') === $key)>{{ $t['label'] }}</option>
            @endforeach
          </select>
          <div class="theme-swatches" style="display:grid;gap:10px;margin-top:12px">
            @foreach($themes as $key => $t)
              @php($tok = $t['tokens'] ?? [])
              <label class="theme-swatch" style="display:flex;gap:12px;align-items:center;padding:10px 12px;border:1px solid var(--line);border-radius:var(--radius);cursor:pointer;background:var(--surface)">
                <input type="radio" name="_theme_preview" value="{{ $key }}" style="width:auto;margin:0" @checked(old('theme', 'pearledu') === $key) onclick="document.getElementById('school-theme').value=this.value">
                <span style="display:flex;gap:4px;flex-shrink:0" aria-hidden="true">
                  <i style="width:18px;height:18px;border-radius:4px;background:{{ $tok['brand'] ?? '#ccc' }}"></i>
                  <i style="width:18px;height:18px;border-radius:4px;background:{{ $tok['accent'] ?? '#ccc' }}"></i>
                  <i style="width:18px;height:18px;border-radius:4px;background:{{ $tok['sidebar'] ?? '#ccc' }}"></i>
                  <i style="width:18px;height:18px;border-radius:4px;background:{{ $tok['bg'] ?? '#eee' }};border:1px solid {{ $tok['line'] ?? '#ddd' }}"></i>
                </span>
                <span style="min-width:0">
                  <strong style="display:block;font-size:13px;color:var(--ink)">{{ $t['label'] }}</strong>
                  <span style="font-size:12px;color:var(--muted)">{{ $t['description'] ?? '' }}</span>
                </span>
              </label>
            @endforeach
          </div>
        </div>
      </div>
      <label>Levels offered</label>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        @foreach(['preprimary'=>'Pre-primary','primary'=>'Primary','lower_secondary'=>'O-Level','upper_secondary'=>'A-Level'] as $v=>$lbl)
          <label style="display:flex;gap:6px;align-items:center;width:auto"><input type="checkbox" name="levels[]" value="{{ $v }}" style="width:auto"> {{ $lbl }}</label>
        @endforeach
      </div>
      <h3 style="margin-top:18px">Contact person (becomes School Admin)</h3>
      <div class="grid g2">
        <div><label>Full name</label><input name="admin[full_name]" required></div>
        <div><label>Email</label><input name="admin[email]" type="email"></div>
        <div><label>Phone</label><input name="admin[phone]"></div>
      </div>
      <p style="color:var(--muted);font-size:13px">
        Creates a <strong>tenant id</strong> for this school. Staff and parents sign in at
        <code>{{ config('tenancy.pearledu_landing_host') }}/login</code> — only users linked to that tenant see its data.
        PearlEdu operators manage schools at <code>/admin</code>.
      </p>
      @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
      <p><button class="btn" type="submit">Onboard school</button></p>
    </form>
  </div>
@endsection
