@extends('layouts.app')
@section('title', 'School identity · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Setup</p>
      <h1 class="page-header__title">School identity</h1>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Badge, logo, theme, and SchoolPay credentials for fee collection.</p>
    </div>
  </div>

  @if(session('status'))
    <div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>
  @endif

  <form method="post" action="{{ route('app.settings.school.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="grid g2">
      <div class="card">
        <h3 style="margin-top:0">Identity</h3>
        <label>School name</label>
        <input name="name" value="{{ old('name', $school->name) }}" required>
        <label>Motto</label>
        <input name="motto" value="{{ old('motto', $school->motto) }}" placeholder="Excellence through knowledge">
        <label>Badge text (short, e.g. crest letters)</label>
        <input name="badge_text" value="{{ old('badge_text', $school->badge_text) }}" maxlength="12" placeholder="{{ strtoupper(substr($school->name, 0, 3)) }}">
        <label>Address</label>
        <input name="address" value="{{ old('address', $school->address) }}">
        <label>District</label>
        <input name="district" value="{{ old('district', $school->district) }}">
        <label>EMIS number</label>
        <input name="emis_number" value="{{ old('emis_number', $school->emis_number) }}">
        <label>School theme</label>
        <select name="theme" required>
          @foreach($themes as $key => $theme)
            <option value="{{ $key }}" @selected(old('theme', $school->theme) === $key)>{{ $theme['label'] ?? $key }}</option>
          @endforeach
        </select>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 4px">
          @foreach($themes as $key => $theme)
            @php($tok = $theme['tokens'] ?? [])
            <span title="{{ $theme['description'] ?? $key }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--muted)">
              <i style="width:14px;height:14px;border-radius:3px;background:{{ $tok['brand'] ?? '#ccc' }};display:inline-block"></i>
              <i style="width:14px;height:14px;border-radius:3px;background:{{ $tok['accent'] ?? '#ccc' }};display:inline-block"></i>
              {{ $theme['label'] ?? $key }}
            </span>
          @endforeach
        </div>
        <p style="margin:0 0 12px;font-size:12px;color:var(--muted)">Applies to everyone at this school (unless a user set a personal override).</p>
        <label>School logo / crest</label>
        <input type="file" name="logo" accept="image/*">
        @if($school->logo_path)
          <label style="display:flex;align-items:center;gap:8px;margin-top:8px">
            <input type="checkbox" name="remove_logo" value="1"> Remove current logo
          </label>
        @endif
      </div>

      <div class="card">
        <h3 style="margin-top:0">Card preview</h3>
        @include('partials.school-badge', ['school' => $school, 'size' => 'lg'])
        <p style="margin:14px 0 0;color:var(--muted);font-size:13px">{{ $school->motto ?: 'Add a motto to show under the name.' }}</p>
        <p style="margin:6px 0 0;color:var(--muted);font-size:12px">{{ $school->address ?: $school->district }}</p>

        <hr style="margin:18px 0;border:0;border-top:1px solid var(--border, #e5e7eb)">

        <h3 style="margin-top:0">SchoolPay fees</h3>
        <p style="margin:0 0 12px;color:var(--muted);font-size:13px">
          SchoolPay is a Bank of Uganda–licensed payment service (Fincom / Service Cops).
          Production school codes and API passwords are issued by Service Cops — they are not self-service.
          Contact <a href="mailto:support@schoolpay.co.ug">support@schoolpay.co.ug</a> / +256 200 502 140, then enter the credentials below.
        </p>
        <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
          <input type="checkbox" name="schoolpay_enabled" value="1" @checked(old('schoolpay_enabled', $school->schoolpay_enabled))>
          Enable SchoolPay for this school
        </label>
        <label>SchoolPay school code</label>
        <input name="schoolpay_school_code" value="{{ old('schoolpay_school_code', $school->schoolpay_school_code) }}" placeholder="Unique school identifier from Service Cops">
        <label>SchoolPay API password</label>
        <input type="password" name="schoolpay_api_password" value="" placeholder="{{ $school->schoolpay_api_password ? '•••••••• (leave blank to keep)' : 'API password from Service Cops' }}" autocomplete="new-password">
        <p style="margin:10px 0 4px;font-size:12px;color:var(--muted)">Every API call authenticates with <code>strtoupper(md5(schoolCode + date|ref + password))</code>. Register these HTTPS URLs in the SchoolPay portal:</p>
        <p style="margin:0;font-size:12px;word-break:break-all"><strong>Adhoc callback:</strong> {{ $schoolPayCallbackUrl }}</p>
        <p style="margin:4px 0 0;font-size:12px;word-break:break-all"><strong>Webhook notify:</strong> {{ $schoolPayNotifyUrl }}</p>
        <p style="margin:10px 0 0;font-size:12px;color:var(--muted)">Also set each learner’s 10-digit SchoolPay payment code so Sync/webhook receipts can be matched.</p>
      </div>
    </div>

    @foreach($errors->all() as $e)<div class="err" style="margin-top:12px">{{ $e }}</div>@endforeach
    <p style="margin-top:14px"><button class="btn" type="submit">Save settings</button></p>
  </form>
@endsection
