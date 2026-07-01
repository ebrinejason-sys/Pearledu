@extends('layouts.app')
@section('title','Onboard a school')
@section('content')
  <h2>Onboard a school</h2>
  <div class="card">
    <form method="post" action="{{ route('platform.schools.store') }}">
      @csrf
      <div class="grid g2">
        <div><label>School name</label><input name="name" value="{{ old('name') }}" required></div>
        <div><label>District</label><input name="district" value="{{ old('district') }}"></div>
        <div><label>EMIS number (optional)</label><input name="emis_number" value="{{ old('emis_number') }}"></div>
        <div><label>Theme</label>
          <select name="theme">
            @foreach($themes as $key => $t)<option value="{{ $key }}">{{ $t['label'] }}</option>@endforeach
          </select>
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
      <p style="color:var(--muted);font-size:13px">The subdomain is assigned automatically as <code>pearledu{N}.{{ config('tenancy.base_domain') }}</code>. The contact person receives an invitation to set their password.</p>
      @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
      <p><button class="btn" type="submit">Onboard school</button></p>
    </form>
  </div>
@endsection
