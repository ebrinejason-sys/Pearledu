@extends('layouts.app')
@section('title', 'School identity · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Setup</p>
      <h1 class="page-header__title">School identity</h1>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Badge, logo, theme, and optional add-ons (EMIS Support, SchoolPay).</p>
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
        <label>EMIS number (school registry id)</label>
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

        <hr style="margin:18px 0;border:0;border-top:1px solid var(--border, #e5e7eb)">
        <h3 style="margin-top:0">Card preview</h3>
        @include('partials.school-badge', ['school' => $school, 'size' => 'lg'])
        <p style="margin:14px 0 0;color:var(--muted);font-size:13px">{{ $school->motto ?: 'Add a motto to show under the name.' }}</p>
        <p style="margin:6px 0 0;color:var(--muted);font-size:12px">{{ $school->address ?: $school->district }}</p>
      </div>

      <div class="card">
        <h3 style="margin-top:0">Optional features</h3>
        <p style="margin:0 0 14px;color:var(--muted);font-size:13px">
          Schools opt in only to what they need. Turn a feature off anytime — menus and integrations hide until you enable it again.
        </p>

        <div style="padding:14px;border:1px solid var(--border,#e5e7eb);border-radius:10px;margin-bottom:14px;background:color-mix(in srgb, var(--surface, #fff) 92%, var(--brand, #053F5C))">
          <label style="display:flex;align-items:flex-start;gap:10px;margin:0;cursor:pointer">
            <input type="checkbox" name="emis_enabled" value="1" @checked(old('emis_enabled', $school->emis_enabled)) style="margin-top:3px">
            <span>
              <strong style="display:block">EMIS Support</strong>
              <span style="display:block;font-size:13px;color:var(--muted);margin-top:4px;font-weight:400">
                MoES EMIS student export and related tools. When off, EMIS export is hidden from the sidebar.
              </span>
            </span>
          </label>
        </div>

        <div style="padding:14px;border:1px solid var(--border,#e5e7eb);border-radius:10px;margin-bottom:14px;background:color-mix(in srgb, var(--surface, #fff) 92%, var(--accent, #F4A261))">
          <label style="display:flex;align-items:flex-start;gap:10px;margin:0 0 12px;cursor:pointer">
            <input type="checkbox" name="schoolpay_enabled" value="1" @checked(old('schoolpay_enabled', $school->schoolpay_enabled)) style="margin-top:3px">
            <span>
              <strong style="display:block">SchoolPay</strong>
              <span style="display:block;font-size:13px;color:var(--muted);margin-top:4px;font-weight:400">
                Live mobile-money fee collection via SchoolPay (Bank of Uganda–licensed). When off, parents keep the manual verification flow only.
              </span>
            </span>
          </label>

          <div style="padding-left:28px">
            <p style="margin:0 0 10px;font-size:12px;color:var(--muted)">
              Credentials are issued by Service Cops — contact
              <a href="mailto:support@schoolpay.co.ug">support@schoolpay.co.ug</a> / 0200 502 140.
            </p>
            <label>SchoolPay school code</label>
            <input name="schoolpay_school_code" value="{{ old('schoolpay_school_code', $school->schoolpay_school_code) }}" placeholder="Unique school identifier from Service Cops">
            <label>SchoolPay API password</label>
            <input type="password" name="schoolpay_api_password" value="" placeholder="{{ $school->schoolpay_api_password ? '•••••••• (leave blank to keep)' : 'API password from Service Cops' }}" autocomplete="new-password">
            <p style="margin:10px 0 4px;font-size:12px;color:var(--muted)">Register these HTTPS URLs in the SchoolPay portal:</p>
            <p style="margin:0;font-size:12px;word-break:break-all"><strong>Adhoc callback:</strong> {{ $schoolPayCallbackUrl }}</p>
            <p style="margin:4px 0 0;font-size:12px;word-break:break-all"><strong>Webhook notify:</strong> {{ $schoolPayNotifyUrl }}</p>
            <p style="margin:10px 0 0;font-size:12px;color:var(--muted)">Also set each learner’s 10-digit SchoolPay payment code on student records.</p>
          </div>
        </div>

        <h3>Modules</h3>
        <p style="margin:0 0 12px;color:var(--muted);font-size:13px">Turn off what this school does not use. Hostel, library, CBT and the rest stay hidden until you enable them.</p>
        @foreach($moduleCatalog ?? [] as $key => $label)
          @if(! in_array($key, ['emis', 'schoolpay'], true))
            <label class="check">
              <input type="checkbox" name="modules[{{ $key }}]" value="1" @checked(old('modules.'.$key, $moduleSnapshot[$key] ?? false))>
              <span>{{ $label }}</span>
            </label>
          @endif
        @endforeach

        <h3 style="margin-top:18px">Results settings</h3>
        <label class="check">
          <input type="checkbox" name="report_show_position" value="1" @checked(old('report_show_position', $reportSettings['show_position'] ?? true))>
          <span>Show class position</span>
        </label>
        <label class="check">
          <input type="checkbox" name="report_show_total" value="1" @checked(old('report_show_total', $reportSettings['show_total'] ?? true))>
          <span>Show total</span>
        </label>
        <label class="check">
          <input type="checkbox" name="report_show_average" value="1" @checked(old('report_show_average', $reportSettings['show_average'] ?? true))>
          <span>Show average</span>
        </label>
        <label class="check">
          <input type="checkbox" name="report_require_class_teacher_comment" value="1" @checked(old('report_require_class_teacher_comment', $reportSettings['require_class_teacher_comment'] ?? false))>
          <span>Require class-teacher comment</span>
        </label>
      </div>
    </div>

    @foreach($errors->all() as $e)<div class="err" style="margin-top:12px">{{ $e }}</div>@endforeach
    <p style="margin-top:14px"><button class="btn" type="submit">Save settings</button></p>
  </form>
@endsection
