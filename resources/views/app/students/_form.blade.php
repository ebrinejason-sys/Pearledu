@php
  $isEdit = isset($student);
  $profileOnly = !empty($profileOnly);
@endphp
<div class="grid g2">
  <div>
    <label>Full name</label>
    <input name="full_name" value="{{ old('full_name', $isEdit ? $student->full_name : '') }}" required>
    @error('full_name')<div class="err">{{ $message }}</div>@enderror
  </div>
  @unless($profileOnly)
  <div>
    <label>EMIS number</label>
    <input name="emis_number" value="{{ old('emis_number', $isEdit ? ($student->emis_number ?? '') : '') }}">
    @error('emis_number')<div class="err">{{ $message }}</div>@enderror
  </div>
  @endunless
  <div>
    <label>{{ $profileOnly ? 'Stream' : 'Class' }}</label>
    <select name="class_id">
      @unless($profileOnly)
        <option value="">— Unassigned —</option>
      @endunless
      @foreach($classes as $class)
        <option value="{{ $class->id }}" @selected((string) old('class_id', $isEdit ? ($student->class_id ?? '') : '') === (string) $class->id)>
          {{ $class->displayName() }} ({{ $class->level }})
        </option>
      @endforeach
    </select>
    @error('class_id')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>Gender</label>
    <select name="gender">
      <option value="">— Unspecified —</option>
      <option value="male" @selected(old('gender', $isEdit ? ($student->gender ?? '') : '') === 'male')>Male</option>
      <option value="female" @selected(old('gender', $isEdit ? ($student->gender ?? '') : '') === 'female')>Female</option>
    </select>
    @error('gender')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>Day / boarding</label>
    <select name="residency">
      <option value="day" @selected(old('residency', $isEdit ? ($student->residency ?? 'day') : 'day') === 'day')>Day</option>
      <option value="boarding" @selected(old('residency', $isEdit ? ($student->residency ?? '') : '') === 'boarding')>Boarding</option>
    </select>
  </div>
  <div>
    <label>Nationality</label>
    <input name="nationality" value="{{ old('nationality', $isEdit ? ($student->nationality ?? 'Uganda') : 'Uganda') }}">
  </div>
  <div>
    <label>Date of birth</label>
    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $isEdit && $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '') }}">
    @error('date_of_birth')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>Religion</label>
    <input name="religion" value="{{ old('religion', $isEdit ? ($student->religion ?? '') : '') }}">
  </div>
  <div>
    <label>Home address</label>
    <input name="home_address" value="{{ old('home_address', $isEdit ? ($student->home_address ?? '') : '') }}">
  </div>
  <div style="grid-column:1 / -1">
    <label>Medical notes</label>
    <textarea name="medical_notes" rows="3">{{ old('medical_notes', $isEdit ? ($student->medical_notes ?? '') : '') }}</textarea>
  </div>
  <div>
    <label>Photo (upload or camera)</label>
    <input type="file" name="photo" accept="image/*" capture="user">
    @error('photo')<div class="err">{{ $message }}</div>@enderror
    @if($isEdit && $student->photoUrl())
      <p style="margin:8px 0 0"><img src="{{ $student->photoUrl() }}" alt="" width="72" height="72" style="width:72px;height:72px;object-fit:cover;border-radius:8px"></p>
    @endif
  </div>
  @unless($profileOnly)
  <div>
    <label>Status</label>
    <select name="status" required>
      @foreach($statuses as $status)
        <option value="{{ $status }}" @selected(old('status', $isEdit ? $student->status : 'active') === $status)>{{ $status }}</option>
      @endforeach
    </select>
    @error('status')<div class="err">{{ $message }}</div>@enderror
  </div>
  @endunless
  <div>
    <label>LIN (optional)</label>
    <input name="lin" value="{{ old('lin') }}" autocomplete="off" placeholder="{{ $isEdit && ($student->getAttributes()['lin'] ?? null) ? 'Leave blank to keep the current LIN' : '' }}">
    @error('lin')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>NIN (optional)</label>
    <input name="nin" value="{{ old('nin') }}" autocomplete="off" placeholder="{{ $isEdit && ($student->getAttributes()['nin'] ?? null) ? 'Leave blank to keep the current NIN' : '' }}">
    @error('nin')<div class="err">{{ $message }}</div>@enderror
  </div>
  @unless($profileOnly)
  <div>
    <label>SchoolPay payment code</label>
    <input name="schoolpay_payment_code" value="{{ old('schoolpay_payment_code', $isEdit ? ($student->schoolpay_payment_code ?? '') : '') }}" placeholder="10 digits, e.g. 1005416321" inputmode="numeric" pattern="\d{10}" maxlength="10" autocomplete="off">
    @error('schoolpay_payment_code')<div class="err">{{ $message }}</div>@enderror
    <p style="margin:4px 0 0;font-size:12px;color:var(--muted)">SchoolPay’s unique 10-digit student payment code. Required to match channel/agent payments and avoid orphaned receipts.</p>
  </div>
  @endunless
</div>
@if($isEdit)
  <p style="color:var(--muted);font-size:13px;margin-top:8px">LIN/NIN stay encrypted. Leave those fields blank to keep the stored values.@if($profileOnly) Restream only moves the learner between streams of this class.@endif</p>
@endif
