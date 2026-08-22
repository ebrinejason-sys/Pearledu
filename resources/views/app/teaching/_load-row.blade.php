@php
  $name = $name ?? 'teaching_assignments';
  $i = $i ?? 0;
  $row = is_array($row ?? null) ? $row : [];
  $selectedSubject = (string) ($row['subject_id'] ?? '');
  $selectedClasses = array_map('strval', (array) ($row['class_ids'] ?? []));
  $periods = (int) ($row['periods_per_week'] ?? 3);
  if ($periods < 1) {
      $periods = 3;
  }
@endphp
<div class="teach-row js-teach-row">
  <div class="teach-row__head">
    <span class="teach-row__badge" aria-hidden="true">{{ is_numeric($i) ? ((int) $i + 1) : '+' }}</span>
    <strong>What they teach</strong>
    <button type="button" class="teach-row__remove" data-remove-row aria-label="Remove this subject">Remove</button>
  </div>
  <label>Subject</label>
  <select name="{{ $name }}[{{ $i }}][subject_id]" data-subject>
    <option value="">Select subject</option>
    @foreach($subjects as $subject)
      <option value="{{ $subject->id }}" @selected($selectedSubject === (string) $subject->id)>{{ $subject->name }}</option>
    @endforeach
  </select>
  <fieldset class="teach-row__classes">
    <legend>To which class</legend>
    <p class="teach-row__hint">Tick every class that takes this subject with this teacher. One person may cover many classes.</p>
    <div class="teach-chips">
      @forelse($classes as $c)
        <label class="teach-chip">
          <input type="checkbox" name="{{ $name }}[{{ $i }}][class_ids][]" value="{{ $c->id }}" @checked(in_array((string) $c->id, $selectedClasses, true))>
          <span>{{ $c->displayName() }}</span>
        </label>
      @empty
        <p class="teach-row__hint" style="margin:0">Create classes first.</p>
      @endforelse
    </div>
  </fieldset>
  <div class="teach-row__periods">
    <label>Periods / week</label>
    <input type="number" name="{{ $name }}[{{ $i }}][periods_per_week]" min="1" max="20" value="{{ $periods }}" data-periods>
    <p class="teach-row__hint" style="margin:4px 0 0">Used by the timetable generator so this teacher is not double-booked.</p>
  </div>
</div>
