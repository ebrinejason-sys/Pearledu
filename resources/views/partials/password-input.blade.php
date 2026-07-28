{{-- Reusable password field with show/hide. Params: name, id, label, required, autocomplete, value (rare) --}}
@php
  $name = $name ?? 'password';
  $id = $id ?? $name.'-'.uniqid();
  $label = $label ?? 'Password';
  $required = $required ?? true;
  $autocomplete = $autocomplete ?? 'current-password';
  $value = $value ?? null;
@endphp
<label for="{{ $id }}">{{ $label }}</label>
<div class="password-field" data-password-field>
  <input
    id="{{ $id }}"
    name="{{ $name }}"
    type="password"
    @if($required) required @endif
    autocomplete="{{ $autocomplete }}"
    @if($value !== null) value="{{ $value }}" @endif
    {{ $attributes ?? '' }}
  >
  <button type="button" class="password-field__toggle" data-password-toggle aria-pressed="false" aria-label="Show password">Show</button>
</div>
