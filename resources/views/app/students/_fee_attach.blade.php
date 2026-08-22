@php
  $structures = $applyableStructures ?? collect();
  $invoiced = $invoicedStructureIds ?? [];
  $canApply = ! empty($canApplyFees);
  $feeKinds = $feeKinds ?? \App\Support\FeeKind::keys();
  $studentModel = $student ?? null;
  $layout = $layout ?? 'profile';
@endphp
@if($canApply)
  <section class="fee-attach" aria-labelledby="fee-attach-heading">
    <h3 id="fee-attach-heading" style="margin-top:0">Attach fee type</h3>
    <p style="color:var(--muted);font-size:13px;margin-top:0">
      Pick the structure that matches this learner’s class and day/boarding. Named extras (van, club) can be attached even when they were first saved on someone else.
    </p>
    @if($structures->isNotEmpty())
      @if($layout === 'profile' && $studentModel)
        <form method="post" action="{{ route('app.students.fees.apply', $studentModel) }}" class="fee-attach__form">
          @csrf
          <label>Saved fee type</label>
          <select name="fee_structure_id" required>
            <option value="">— Select a fee type —</option>
            @foreach($structures as $structure)
              @php
                $already = in_array((int) $structure->id, $invoiced, true);
                $scope = $structure->isLearnerTargeted()
                  ? 'Named extra'
                  : trim(($structure->schoolClass?->displayName() ?: 'All classes').' · '.$structure->residencyLabel());
              @endphp
              <option value="{{ $structure->id }}" @selected((string) old('fee_structure_id') === (string) $structure->id)>
                {{ $structure->name }} · {{ $structure->kindLabel() }} · {{ $scope }} · UGX {{ number_format((float) $structure->amount) }}{{ $already ? ' (already billed)' : '' }}
              </option>
            @endforeach
          </select>
          @error('fee_structure_id')<div class="err">{{ $message }}</div>@enderror
          <label>Due on (optional)</label>
          <input type="date" name="due_on" value="{{ old('due_on') }}">
          <p style="margin-top:12px"><button class="btn accent" type="submit">Attach and bill</button></p>
        </form>
      @else
        <fieldset class="fee-attach__picks" style="border:1px solid var(--line);border-radius:var(--radius-sm);padding:12px;margin:0 0 12px">
          <legend style="font-size:13px;padding:0 6px">Fee structures for this learner</legend>
          <p style="margin:0 0 10px;color:var(--muted);font-size:13px">Tick every extra that should be billed now. Class/day-boarding defaults still apply automatically at enrollment.</p>
          @foreach($structures as $structure)
            @php
              $already = in_array((int) $structure->id, $invoiced, true);
              $scope = $structure->isLearnerTargeted()
                ? 'Named extra'
                : trim(($structure->schoolClass?->displayName() ?: 'All classes').' · '.$structure->residencyLabel());
            @endphp
            <label class="check" style="display:flex;gap:8px;align-items:flex-start;margin:0 0 8px">
              <input type="checkbox" name="fee_structure_ids[]" value="{{ $structure->id }}" @checked($already || collect(old('fee_structure_ids', []))->contains((string) $structure->id)) @disabled($already)>
              <span>
                <strong>{{ $structure->name }}</strong>
                <span style="display:block;font-size:12px;color:var(--muted)">{{ $structure->kindLabel() }} · {{ $scope }} · UGX {{ number_format((float) $structure->amount) }}{{ $already ? ' · already billed' : '' }}</span>
              </span>
            </label>
          @endforeach
        </fieldset>
      @endif
    @else
      <p style="color:var(--muted)">No saved fee types yet. Create them under Finance → Fees, or add a one-off extra below.</p>
    @endif

    @if($layout === 'profile' && $studentModel)
      <h4 style="margin:16px 0 8px">Or create a one-off extra</h4>
      <form method="post" action="{{ route('app.students.fees.apply', $studentModel) }}">
        @csrf
        <div class="grid g2">
          <div>
            <label>Custom fee name</label>
            <input name="name" value="{{ old('name') }}" placeholder="e.g. Van for {{ $studentModel->full_name }}">
            @error('name')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div>
            <label>Amount (UGX)</label>
            <input type="number" step="0.01" name="amount" value="{{ old('amount') }}">
            @error('amount')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div>
            <label>Kind</label>
            <select name="kind">
              @foreach($feeKinds as $kind)
                <option value="{{ $kind }}" @selected(old('kind', 'transport') === $kind)>{{ \App\Support\FeeKind::label($kind) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Due on (optional)</label>
            <input type="date" name="due_on" value="{{ old('due_on') }}">
          </div>
        </div>
        <p style="margin-top:12px"><button class="btn" type="submit">Apply custom fee</button></p>
      </form>
    @endif
  </section>
@elseif($structures->isNotEmpty())
  <section class="fee-attach fee-attach--readonly">
    <h4 style="margin-top:0">Saved fee types that match this learner</h4>
    <p style="color:var(--muted);font-size:13px">Bursar or school admin attaches these from this tab. Leadership can view the ledger only — fee writes stay with finance.</p>
    <ul class="fee-attach__list">
      @foreach($structures as $structure)
        @php
          $already = in_array((int) $structure->id, $invoiced, true);
          $scope = $structure->isLearnerTargeted()
            ? 'Named extra'
            : trim(($structure->schoolClass?->displayName() ?: 'All classes').' · '.$structure->residencyLabel());
        @endphp
        <li>
          <strong>{{ $structure->name }}</strong>
          · {{ $structure->kindLabel() }} · {{ $scope }} · UGX {{ number_format((float) $structure->amount) }}
          @if($already)<span class="pill">billed</span>@endif
        </li>
      @endforeach
    </ul>
  </section>
@endif
