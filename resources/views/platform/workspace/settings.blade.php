@extends('layouts.app')
@section('title', 'EMIS & SchoolPay · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.workspace') }}">{{ $school->name }}</a></p>
      <h2 class="page-header__title">EMIS &amp; SchoolPay</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Edit tenant integrations for the school you entered. School users still manage identity under School settings.</p>
    </div>
  </div>
  @if(session('status'))<div class="status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <form method="post" action="{{ route('platform.workspace.settings.update') }}" class="card">
    @csrf @method('PUT')
    <h3 style="margin-top:0">EMIS</h3>
    <label>EMIS number</label>
    <input name="emis_number" value="{{ old('emis_number', $school->emis_number) }}">
    @error('emis_number')<div class="err">{{ $message }}</div>@enderror
    <label style="display:flex;gap:8px;align-items:flex-start;margin:12px 0">
      <input type="checkbox" name="emis_enabled" value="1" @checked(old('emis_enabled', $school->emis_enabled))>
      <span>Enable EMIS export for this school</span>
    </label>
    <h3>SchoolPay</h3>
    <label style="display:flex;gap:8px;align-items:flex-start;margin:12px 0">
      <input type="checkbox" name="schoolpay_enabled" value="1" @checked(old('schoolpay_enabled', $school->schoolpay_enabled))>
      <span>Enable SchoolPay collections</span>
    </label>
    <label>School code</label>
    <input name="schoolpay_school_code" value="{{ old('schoolpay_school_code', $school->schoolpay_school_code) }}">
    <label>API password</label>
    <input type="password" name="schoolpay_api_password" value="" autocomplete="new-password" placeholder="{{ $school->schoolpay_api_password ? 'Leave blank to keep the current password' : 'From Service Cops' }}">
    <p style="color:var(--muted);font-size:13px">Callback: <code>{{ $schoolPayCallbackUrl }}</code><br>Notify: <code>{{ $schoolPayNotifyUrl }}</code></p>
    <p style="margin-top:14px"><button class="btn accent" type="submit">Save integrations</button></p>
  </form>
@endsection
