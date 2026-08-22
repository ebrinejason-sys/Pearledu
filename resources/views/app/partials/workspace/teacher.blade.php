@php
  $t = $workspace['teacher'];
  $compact = $compact ?? false;
@endphp
<section class="ws-block {{ $compact ? 'ws-block--strip' : '' }}" aria-labelledby="ws-teacher">
  <div class="dash-chart-card__head">
    <div>
      <p class="page-header__eyebrow">My classes</p>
      <h2 id="ws-teacher" class="ws-block__title">{{ $t['period'] ? 'This set · '.$t['period'] : 'Teaching load' }}</h2>
    </div>
    @if(!$compact)
      <a class="btn ghost ws-cta" href="{{ route('app.teaching.mine') }}">Open workspace</a>
    @endif
  </div>

  @if($compact)
    <p class="ws-strip-meta">{{ $t['load'] }} class+subject rows today</p>
  @else
    <h3 class="ws-sub">Today’s lessons</h3>
    @if(empty($t['lessons']))
      <p class="ws-hint">No lessons on the timetable for today. Take attendance from a class card.</p>
    @else
      <ol class="lesson-tl">
        @foreach($t['lessons'] as $lesson)
          <li class="lesson-tl__item {{ !empty($lesson['current']) ? 'is-now' : '' }}">
            <span class="lesson-tl__time">{{ $lesson['starts_at'] ?? $lesson['period'] }}</span>
            <div>
              <strong>{{ $lesson['subject'] }}</strong>
              <span>{{ $lesson['class'] }}@if(!empty($lesson['room'])) · {{ $lesson['room'] }}@endif</span>
            </div>
          </li>
        @endforeach
      </ol>
    @endif

    <div class="load-cards">
      @forelse($t['classes'] as $row)
        @foreach($row['subjects'] as $subject)
          @php
            $pill = !empty($subject['revoked']) ? 'danger' : match ($subject['status']) {
              'verified' => 'success',
              'submitted' => 'accent',
              default => 'warning',
            };
          @endphp
          <div class="load-card">
            <h3 style="margin-top:0;font-size:16px">{{ $row['class'] }}</h3>
            <p><span class="pill pill--active">{{ $subject['name'] }}</span>
              <span class="pill pill--{{ $pill }}">{{ !empty($subject['revoked']) ? 'revoked' : str_replace('_', ' ', $subject['status']) }}</span>
            </p>
            <p class="ws-actions">
              <a class="btn ghost ws-cta" href="{{ route('app.attendance.index', ['class_id' => $row['class_id']]) }}">Attendance</a>
              <a class="btn accent ws-cta" href="{{ route('app.assessment.marks', ['class_id' => $row['class_id'], 'subject_id' => $subject['id']]) }}">Enter marks</a>
            </p>
            @if(!empty($t['can_message']) && !empty($row['class_teacher_id']))
              <form method="post" action="{{ route('app.staff.messages.store') }}" class="ws-flag">
                @csrf
                <input type="hidden" name="intent" value="concern">
                <input type="hidden" name="class_id" value="{{ $row['class_id'] }}">
                <label>Flag to {{ $row['class_teacher_name'] ?? 'class teacher' }}
                  <input name="body" required placeholder="Pastoral or academic concern">
                </label>
                <button class="btn ghost ws-cta" type="submit">Send</button>
              </form>
            @endif
          </div>
        @endforeach
      @empty
        <p class="ws-hint">No current teaching assignments. Ask the Director of Studies to classify your load.</p>
      @endforelse
    </div>

    @if(!empty($t['lms_due']))
      <h3 class="ws-sub">LMS due</h3>
      @foreach($t['lms_due'] as $due)
        <p class="ws-hint">{{ $due['title'] }} · {{ $due['due'] }}</p>
      @endforeach
    @endif
  @endif
</section>
