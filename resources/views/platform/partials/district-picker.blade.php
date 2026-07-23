{{-- Searchable Uganda district picker. Optional: $selected, $required (default true). --}}
@php
  use App\Support\UgandaDistricts;
  $selected = old('district', $selected ?? '');
  $required = $required ?? true;
  $districts = UgandaDistricts::optionsAllowing($selected ?: null);
  $uid = 'district-'.uniqid();
@endphp
<div class="district-picker" data-district-picker>
  <label for="{{ $uid }}-search">District</label>
  <input
    type="search"
    id="{{ $uid }}-search"
    placeholder="Type to search districts…"
    autocomplete="off"
    style="margin-bottom:8px"
    data-district-search
    aria-controls="{{ $uid }}-select"
  >
  <select
    name="district"
    id="{{ $uid }}-select"
    @if($required) required @endif
    data-district-select
  >
    <option value="">— Select district —</option>
    @foreach($districts as $d)
      <option value="{{ $d }}" @selected($selected === $d)>{{ $d }}</option>
    @endforeach
  </select>
  <p style="margin:6px 0 0;font-size:12px;color:var(--muted)">
    {{ count($districts) }} Uganda districts — search, then select.
  </p>
</div>
<script>
(function () {
  const root = document.currentScript && document.currentScript.previousElementSibling;
  if (!root || !root.matches('[data-district-picker]')) return;
  const search = root.querySelector('[data-district-search]');
  const select = root.querySelector('[data-district-select]');
  if (!search || !select) return;

  const all = Array.from(select.options).map((o) => ({
    value: o.value,
    label: (o.textContent || '').trim(),
  }));

  function render(q) {
    const needle = (q || '').trim().toLowerCase();
    const current = select.value;
    select.innerHTML = '';

    all.forEach((item) => {
      if (item.value !== '' && needle && !item.label.toLowerCase().includes(needle)) {
        return;
      }
      const opt = document.createElement('option');
      opt.value = item.value;
      opt.textContent = item.label;
      if (item.value === current) opt.selected = true;
      select.appendChild(opt);
    });

    const real = Array.from(select.options).filter((o) => o.value !== '');
    if (needle && real.length === 0) {
      const none = document.createElement('option');
      none.value = '';
      none.disabled = true;
      none.textContent = 'No districts match “‘ + q + '”';
      select.appendChild(none);
    } else if (!select.value && current) {
      // keep blank placeholder selected when filter drops current
      select.value = '';
    }
  }

  search.addEventListener('input', () => render(search.value));
  search.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const first = Array.from(select.options).find((o) => o.value !== '' && !o.disabled);
      if (first) {
        select.value = first.value;
        search.value = first.textContent;
      }
    }
  });
})();
</script>
