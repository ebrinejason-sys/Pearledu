@php
  $name = $name ?? 'teaching_assignments';
  $builderId = $builderId ?? 'teach-load-'.uniqid();
  $hint = $hint ?? 'Classify what they teach and which class takes it. Add as many subject rows as you need — one staff member is not limited to a single entry.';
  $oldRows = old($name);
  if (! is_array($oldRows) || $oldRows === []) {
      $oldRows = $rows ?? [['subject_id' => '', 'class_ids' => [], 'periods_per_week' => 3]];
  }
@endphp
<div class="teach-builder" id="{{ $builderId }}" data-teach-builder data-name="{{ $name }}">
  <p class="teach-builder__hint">{{ $hint }}</p>
  <div class="teach-builder__rows" data-rows>
    @foreach($oldRows as $i => $row)
      @include('app.teaching._load-row', [
        'i' => $i,
        'name' => $name,
        'subjects' => $subjects,
        'classes' => $classes,
        'row' => is_array($row) ? $row : [],
      ])
    @endforeach
  </div>
  <template data-row-template>
    @include('app.teaching._load-row', [
      'i' => '__INDEX__',
      'name' => $name,
      'subjects' => $subjects,
      'classes' => $classes,
      'row' => ['subject_id' => '', 'class_ids' => [], 'periods_per_week' => 3],
    ])
  </template>
  <div class="teach-builder__actions">
    <button type="button" class="btn ghost" data-add-row>Add another subject</button>
    <p class="teach-builder__summary" data-summary></p>
  </div>
  @error('teaching_assignments')<div class="err" role="alert">{{ $message }}</div>@enderror
</div>
@once
<script>
(function () {
  function summary(root) {
    var lines = [];
    root.querySelectorAll('.js-teach-row').forEach(function (row) {
      var subject = row.querySelector('[data-subject]');
      var subjectLabel = subject && subject.options[subject.selectedIndex] ? subject.options[subject.selectedIndex].text : '';
      if (!subject || !subject.value) return;
      var classes = Array.from(row.querySelectorAll('.teach-chip input:checked')).map(function (el) {
        return el.parentElement ? el.parentElement.textContent.trim() : '';
      }).filter(Boolean);
      var periods = row.querySelector('[data-periods]');
      var wk = periods && periods.value ? periods.value : '3';
      if (classes.length) {
        lines.push(subjectLabel + ' → ' + classes.join(', ') + ' · ' + wk + '/wk');
      }
    });
    var box = root.querySelector('[data-summary]');
    if (box) box.textContent = lines.length ? ('Timetable load: ' + lines.join(' · ')) : '';
  }

  function reindex(root) {
    var name = root.getAttribute('data-name') || 'teaching_assignments';
    root.querySelectorAll('.js-teach-row').forEach(function (row, idx) {
      var badge = row.querySelector('.teach-row__badge');
      if (badge) badge.textContent = String(idx + 1);
      row.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(new RegExp(name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[[^\\]]+\\]'), name + '[' + idx + ']');
      });
    });
    var many = root.querySelectorAll('.js-teach-row').length > 1;
    root.querySelectorAll('[data-remove-row]').forEach(function (btn) {
      btn.hidden = !many;
    });
    summary(root);
  }

  function bind(root) {
    if (root.dataset.teachBound) return;
    root.dataset.teachBound = '1';
    var rows = root.querySelector('[data-rows]');
    var add = root.querySelector('[data-add-row]');
    var tpl = root.querySelector('[data-row-template]');
    if (add && rows && tpl) {
      add.addEventListener('click', function () {
        var idx = rows.querySelectorAll('.js-teach-row').length;
        var wrap = document.createElement('div');
        wrap.innerHTML = tpl.innerHTML.replace(/__INDEX__/g, String(idx));
        var node = wrap.querySelector('.js-teach-row');
        if (node) rows.appendChild(node);
        reindex(root);
      });
    }
    root.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove-row]');
      if (!btn || !root.contains(btn)) return;
      var row = btn.closest('.js-teach-row');
      if (row && rows.querySelectorAll('.js-teach-row').length > 1) {
        row.remove();
        reindex(root);
      }
    });
    root.addEventListener('change', function () { summary(root); });
    root.addEventListener('input', function () { summary(root); });
    reindex(root);
  }

  function bindAll() {
    document.querySelectorAll('[data-teach-builder]').forEach(bind);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindAll);
  } else {
    bindAll();
  }
})();
</script>
@endonce
