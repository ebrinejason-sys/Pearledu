@php
  $h = $workspace['homeroom'];
  $compact = $compact ?? false;
  $ring = $h['ring'] ?? ['present_pct' => 0, 'absent_pct' => 0, 'late_pct' => 0, 'unmarked_pct' => 0];
  $p = (int) $ring['present_pct'];
  $l = $p + (int) $ring['late_pct'];
  $a = $l + (int) $ring['absent_pct'];
  $feesPct = $h['fees_total'] ? (int) round(($h['fees_cleared'] / max(1, $h['fees_total'])) * 100) : 0;
@endphp
<section class="ws-block {{ $compact ? 'ws-block--strip' : '' }}" aria-labelledby="ws-homeroom">
  <div class="dash-chart-card__head">
    <div>
      <p class="page-header__eyebrow">My Class</p>
      <h2 id="ws-homeroom" class="ws-block__title">{{ $h['class_name'] }}</h2>
    </div>
    @if(!$compact)
      <a class="btn ghost ws-cta" href="{{ route('app.teaching.homeroom') }}">Open class</a>
    @endif
  </div>

  @if($compact)
    <p class="ws-strip-meta">{{ $h['students'] }} learners · {{ $h['present'] }} present · {{ $h['absent'] }} absent</p>
  @else
    <div class="ws-hero">
      <div class="ws-ring" role="img" aria-label="Today’s attendance: {{ $h['present'] }} present, {{ $h['absent'] }} absent, {{ $h['late'] }} late, {{ $h['unmarked'] }} unmarked of {{ $h['students'] }}" style="background:conic-gradient(var(--success) 0 {{ $p }}%, var(--warning) {{ $p }}% {{ $l }}%, var(--danger) {{ $l }}% {{ $a }}%, var(--surface-2) {{ $a }}% 100%)">
        <div class="ws-ring__hole">
          <strong>{{ $h['present'] }}</strong>
          <span>present</span>
        </div>
      </div>
      <div class="ws-hero__copy">
        <p class="ws-legend">
          <span class="ws-dot ws-dot--success"></span> Present {{ $h['present'] }}
          <span class="ws-dot ws-dot--warning"></span> Late {{ $h['late'] }}
          <span class="ws-dot ws-dot--danger"></span> Absent {{ $h['absent'] }}
          <span class="ws-dot ws-dot--muted"></span> Unmarked {{ $h['unmarked'] }}
        </p>
        <a class="btn accent ws-cta ws-cta--xl" href="{{ route('app.attendance.index', ['class_id' => $h['class_id']]) }}">Take register</a>
        <p class="ws-hint">Works offline after you open the register once.</p>
        @if(!empty($h['report_url']))
          <p><a href="{{ $h['report_url'] }}">View published report cards</a> — assembled marks only, you cannot type scores.</p>
        @endif
      </div>
    </div>

    <div class="ws-fee-bar" role="img" aria-label="{{ $h['fees_cleared'] }} of {{ $h['fees_total'] }} learners have cleared fees">
      <div class="dash-bar__meta"><span>Fees cleared</span><strong>{{ $h['fees_cleared'] }}/{{ $h['fees_total'] }}</strong></div>
      <div class="dash-bar__track"><span style="width:{{ $feesPct }}%"></span></div>
      <p class="ws-hint">Counts only. Amounts stay with the bursar.</p>
    </div>

    <h3 class="ws-sub">Roster</h3>
    <div class="face-row">
      @forelse($h['roster'] as $student)
        <a class="face-chip" href="{{ route('app.students.show', $student) }}">
          @include('app.partials.person-face', ['url' => $student->photoUrl(), 'initial' => $student->photoInitial(), 'name' => $student->full_name])
          <span>{{ explode(' ', $student->full_name)[0] }}</span>
        </a>
      @empty
        <p class="ws-hint">No learners in this class yet. Ask school admin to place them.</p>
      @endforelse
    </div>

    <h3 class="ws-sub">Examination sets</h3>
    @forelse($h['exam_sets'] as $set)
      <div class="exam-set">
        <h4><span class="pill pill--active">{{ $set['kind'] }}</span> {{ $set['name'] }}
          @if($set['deadline'])
            <span class="ws-hint">Deadline {{ $set['deadline'] }}{{ $set['deadline_passed'] ? ' (passed)' : '' }}</span>
          @endif
        </h4>
        <div class="exam-set__grid">
          @forelse($set['subjects'] as $row)
            @php
              $pill = $row['revoked'] ? 'danger' : match ($row['status']) {
                'verified' => 'success',
                'submitted' => 'accent',
                default => 'warning',
              };
            @endphp
            <div class="exam-set__card">
              @include('app.partials.person-face', ['url' => $row['teacher_photo'] ?? null, 'initial' => $row['teacher_initial'] ?? '?', 'name' => $row['teacher'] ?? 'Teacher'])
              <div>
                <strong>{{ $row['subject'] }}</strong>
                <span class="pill pill--{{ $pill }}">{{ $row['revoked'] ? 'revoked' : str_replace('_', ' ', $row['status']) }}</span>
                <span class="ws-hint">{{ $row['teacher'] ?? 'No teacher' }}</span>
              </div>
              @if(!empty($row['can_revoke']))
                <form method="post" action="{{ route('app.assessment.marksheets.revoke') }}">
                  @csrf
                  <input type="hidden" name="period_id" value="{{ $set['id'] }}">
                  <input type="hidden" name="class_id" value="{{ $h['class_id'] }}">
                  <input type="hidden" name="subject_id" value="{{ $row['subject_id'] }}">
                  <button class="btn ghost ws-cta" type="submit">Lock upload</button>
                </form>
              @endif
            </div>
          @empty
            <p class="ws-hint">No subject teachers assigned yet.</p>
          @endforelse
        </div>
      </div>
    @empty
      <p class="ws-hint">No examination sets yet. The Director of Studies creates BOT / MOT / EOT under Assessment periods.</p>
    @endforelse

    @if(!empty($h['parents']))
      <h3 class="ws-sub">Parents</h3>
      <div class="face-row">
        @foreach(array_slice($h['parents'], 0, 12) as $parent)
          <div class="face-chip">
            @include('app.partials.person-face', ['url' => $parent['photo'], 'initial' => $parent['initial'], 'name' => $parent['name']])
            <span>{{ $parent['name'] }}</span>
          </div>
        @endforeach
      </div>
    @endif

    @if(!empty($h['can_message'] ?? true) && in_array('staff.messages', $permissions ?? [], true))
      <form class="ws-flag" method="post" action="{{ route('app.staff.messages.store') }}">
        @csrf
        <input type="hidden" name="intent" value="escalate">
        <label>Escalate this class
          <select name="role_key">
            <option value="deputy_head_teacher">Deputy Head Teacher</option>
            <option value="director_of_studies">Director of Studies</option>
            <option value="head_teacher">Head Teacher</option>
          </select>
        </label>
        <label>Note <textarea name="body" rows="2" required placeholder="What needs a decision?"></textarea></label>
        <button class="btn ghost ws-cta" type="submit">Send escalation</button>
      </form>
    @endif
  @endif
</section>
