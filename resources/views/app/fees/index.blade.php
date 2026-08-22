@extends('layouts.app')
@section('title', 'Fees')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h1 class="page-header__title">Fee structures</h1>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Save a class amount for <strong>day</strong> and a separate amount for <strong>boarding</strong>. A learner is charged the structure that matches their class and residence. Named extras (van, club) are saved for a specific learner and applied on their profile; the profile statement is the cumulative amount due.</p>
    </div>
  </div>
  @if(empty($canManageFinance))
    <p class="vx-auth-hint" style="margin-bottom:16px">Read-only finance view. Recording payments and changing invoices is limited to the bursar.</p>
  @endif

  @include('app.fees._nav', ['tab' => 'structures', 'summary' => $summary])

  <div class="emis-kpis" style="margin:16px 0">
    <div class="emis-card emis-card--teal">
      <div class="emis-card__value">{{ $summary['demanded'] ?? 0 }}</div>
      <div class="emis-card__label">Demanded</div>
    </div>
    <div class="emis-card emis-card--pink">
      <div class="emis-card__value">{{ $summary['cleared'] ?? 0 }}</div>
      <div class="emis-card__label">Cleared</div>
    </div>
    <div class="emis-card emis-card--navy">
      <div class="emis-card__value">{{ $summary['overdue'] ?? 0 }}</div>
      <div class="emis-card__label">Overdue</div>
    </div>
  </div>

  @if(!empty($canManageFinance))
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">New fee type</h3>
      <form method="post" action="{{ route('app.fees.structures.store') }}" id="fee-structure-form">
        @csrf
        <label>Name</label>
        <input name="name" required placeholder="e.g. P6 day tuition or Van for Aisha" value="{{ old('name') }}">
        @error('name')<div class="err">{{ $message }}</div>@enderror
        <label>Kind</label>
        <select name="kind" id="fee-kind">
          <option value="tuition" @selected(old('kind', 'tuition') === 'tuition')>Tuition</option>
          <option value="boarding" @selected(old('kind') === 'boarding')>Boarding</option>
          <option value="transport" @selected(old('kind') === 'transport')>Transport / van</option>
          <option value="other" @selected(old('kind') === 'other')>Other (custom)</option>
        </select>
        <label>Amount (UGX)</label>
        <input type="number" step="0.01" name="amount" required value="{{ old('amount') }}">
        @error('amount')<div class="err">{{ $message }}</div>@enderror
        <label>Target</label>
        <select name="applies_to" id="fee-applies">
          <option value="class" @selected(old('applies_to', 'class') === 'class')>A class (day or boarding amount)</option>
          <option value="learners" @selected(old('applies_to') === 'learners')>A named learner (custom extra)</option>
        </select>
        <div id="fee-class-wrap">
          <label>Class</label>
          <select name="class_id">
            <option value="">—</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}" @selected((string) old('class_id') === (string) $c->id)>{{ $c->displayName() }}</option>
            @endforeach
          </select>
          @error('class_id')<div class="err">{{ $message }}</div>@enderror
          <label>Residence</label>
          <select name="residency" id="fee-residency">
            <option value="day" @selected(old('residency', 'day') === 'day')>Day</option>
            <option value="boarding" @selected(old('residency') === 'boarding')>Boarding</option>
          </select>
          @error('residency')<div class="err">{{ $message }}</div>@enderror
          <p style="color:var(--muted);font-size:13px;margin:8px 0 0">Save two structures when day and boarding amounts differ. Enrollment bills only the matching one.</p>
        </div>
        <div id="fee-learners-wrap" hidden>
          <input type="hidden" name="residency" id="fee-residency-any" value="any" disabled>
          <label>Learner</label>
          <select name="student_ids[]" multiple size="8">
            @foreach($students as $s)
              <option value="{{ $s->id }}" @selected(in_array($s->id, old('student_ids', []), true))>{{ $s->full_name }} · {{ $s->schoolClass?->displayName() ?? '—' }}</option>
            @endforeach
          </select>
          @error('student_ids')<div class="err">{{ $message }}</div>@enderror
          <p style="color:var(--muted);font-size:13px;margin:8px 0 0">This saves the extra. Open the learner’s profile to apply it; their statement is the cumulative amount due.</p>
        </div>
        <label>Term</label>
        <select name="term_id"><option value="">—</option>@foreach($terms as $t)<option value="{{ $t->id }}" @selected((string) old('term_id') === (string) $t->id)>{{ $t->name }}</option>@endforeach</select>
        <p><button class="btn accent" type="submit">Save fee type</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Generate invoices</h3>
      <form method="post" action="{{ route('app.fees.invoices.bulk') }}">
        @csrf
        <label>Fee structure</label>
        <select name="fee_structure_id" required>
          @foreach($structures->where('is_active', true) as $s)
            <option value="{{ $s->id }}">{{ $s->name }} · {{ $s->kindLabel() }} · {{ number_format($s->amount) }}</option>
          @endforeach
        </select>
        <label>Class (optional for learner extras)</label>
        <select name="class_id">
          <option value="">—</option>
          @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->displayName() }}</option>@endforeach
        </select>
        <label>Due</label>
        <input type="date" name="due_on">
        <p><button class="btn" type="submit">Invoice matching learners</button></p>
      </form>
    </div>
  </div>
  @endif

  <div class="card">
    <h3 style="margin-top:0">Saved fee types</h3>
    @error('structure')<div class="err" style="margin-bottom:12px">{{ $message }}</div>@enderror
    <table>
      <thead><tr><th>Name</th><th>Kind</th><th>Residence</th><th>Charged to</th><th>Amount</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($structures as $s)
        <tr>
          <td>{{ $s->name }}</td>
          <td><span class="pill">{{ $s->kindLabel() }}</span></td>
          <td>{{ $s->isLearnerTargeted() ? 'Named learner' : $s->residencyLabel() }}</td>
          <td>
            @if($s->isLearnerTargeted())
              {{ $s->learners->pluck('full_name')->filter()->join(', ') ?: ($s->learners->count().' learners') }}
            @else
              {{ $s->schoolClass?->displayName() ?? '—' }}
            @endif
            @if($s->term) · {{ $s->term->name }}@endif
          </td>
          <td>UGX {{ number_format($s->amount) }}</td>
          <td><span class="pill {{ $s->is_active ? 'pill--active' : 'pill--muted' }}">{{ $s->is_active ? 'ACTIVE' : 'archived' }}</span></td>
          <td>
            @if(!empty($canManageFinance))
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
              <form method="post" action="{{ route('app.fees.structures.archive', $s) }}">@csrf
                <button class="btn ghost" type="submit">{{ $s->is_active ? 'Archive' : 'Reactivate' }}</button>
              </form>
              <form method="post" action="{{ route('app.fees.structures.destroy', $s) }}" onsubmit="return confirm(@json('Delete '.$s->name.'? Unpaid invoices for this type will be voided.'))">
                @csrf @method('DELETE')
                <button class="btn ghost" type="submit">Delete</button>
              </form>
            </div>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="color:var(--muted)">No structures yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
@section('head')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var applies = document.getElementById('fee-applies');
    var classWrap = document.getElementById('fee-class-wrap');
    var learnersWrap = document.getElementById('fee-learners-wrap');
    var residency = document.getElementById('fee-residency');
    var residencyAny = document.getElementById('fee-residency-any');
    function sync() {
      var group = applies && applies.value === 'learners';
      if (classWrap) classWrap.hidden = group;
      if (learnersWrap) learnersWrap.hidden = !group;
      if (residency) residency.disabled = group;
      if (residencyAny) residencyAny.disabled = !group;
    }
    if (applies) applies.addEventListener('change', sync);
    sync();
  });
</script>
@endsection
