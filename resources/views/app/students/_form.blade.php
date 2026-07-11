@php
  $isEdit = isset($student);
@endphp
<div class="grid g2">
  <div>
    <label>Full name</label>
    <input name="full_name" value="{{ old('full_name', $isEdit ? $student->full_name : '') }}" required>
    @error('full_name')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>EMIS number</label>
    <input name="emis_number" value="{{ old('emis_number', $isEdit ? ($student->emis_number ?? '') : '') }}">
    @error('emis_number')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>Class</label>
    <select name="class_id">
      <option value="">— Unassigned —</option>
      @foreach($classes as $class)
        <option value="{{ $class->id }}" @selected((string) old('class_id', $isEdit ? ($student->class_id ?? '') : '') === (string) $class->id)>
          {{ $class->name }} ({{ $class->level }})
        </option>
      @endforeach
    </select>
    @error('class_id')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>Status</label>
    <select name="status" required>
      @foreach($statuses as $status)
        <option value="{{ $status }}" @selected(old('status', $isEdit ? $student->status : 'active') === $status)>{{ $status }}</option>
      @endforeach
    </select>
    @error('status')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>LIN (optional)</label>
    <input name="lin" value="{{ old('lin', $isEdit ? ($student->lin ?? '') : '') }}" autocomplete="off">
    @error('lin')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div>
    <label>NIN (optional)</label>
    <input name="nin" value="{{ old('nin', $isEdit ? ($student->nin ?? '') : '') }}" autocomplete="off">
    @error('nin')<div class="err">{{ $message }}</div>@enderror
  </div>
</div>
@if($isEdit)
  <p style="color:var(--muted);font-size:13px;margin-top:8px">LIN/NIN are encrypted at rest. Opening this form audits a sensitive read.</p>
@endif
