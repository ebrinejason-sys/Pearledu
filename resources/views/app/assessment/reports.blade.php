@extends('layouts.app')
@section('title','Report cards · '.$school->name)
@section('head')
<style>
  @media print {
    .app-header,.sidebar,.page-header__actions,.no-print{display:none!important}
    .wrap{max-width:none;padding:0}
    .report-card{break-inside:avoid;page-break-inside:avoid;margin-bottom:18px}
  }
  .report-card{border:1px solid var(--line);border-radius:var(--radius);padding:18px;margin-bottom:16px;background:var(--surface)}
  .report-card__meta{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:12px 0;color:var(--muted);font-size:13px}
</style>
@endsection
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Assessment</p>
      <h2 class="page-header__title">Report cards</h2>
    </div>
    <div class="page-header__actions no-print">
      <button class="btn ghost" type="button" onclick="window.print()">Print cards</button>
    </div>
  </div>

  <div class="card no-print">
    <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
      <div>
        <label>Period</label>
        <select name="period_id" onchange="this.form.submit()">
          @foreach($periods as $p)<option value="{{ $p->id }}" @selected($periodId===$p->id)>{{ $p->name }}</option>@endforeach
        </select>
      </div>
      <div>
        <label>Class</label>
        <select name="class_id" onchange="this.form.submit()">
          @foreach($classes as $c)<option value="{{ $c->id }}" @selected($classId===$c->id)>{{ $c->name }}</option>@endforeach
        </select>
      </div>
    </form>
  </div>

  @forelse($reports as $row)
    <article class="report-card">
      @include('partials.school-badge', ['school' => $school, 'size' => 'md', 'showMotto' => true])
      <div class="report-card__meta">
        <div>
          <strong style="color:var(--ink);font-size:16px">{{ $row['full_name'] }}</strong><br>
          Class: {{ $klass?->name ?? '—' }} · Period: {{ $period?->name ?? '—' }}
        </div>
        <div style="text-align:right">
          @if($reportSettings['show_position'] ?? true)
            Position: <strong>{{ $row['position'] ?? '—' }}</strong><br>
          @endif
          @if($reportSettings['show_average'] ?? true)
            Average: <strong>{{ $row['average'] ?? '—' }}</strong>
          @endif
          @if($reportSettings['show_total'] ?? true)
            <br>Total: <strong>{{ $row['total'] ?? '—' }}</strong>
          @endif
        </div>
      </div>
      <table>
        <thead><tr><th>Subject</th><th>Score</th><th>Grade</th><th>Comment</th></tr></thead>
        <tbody>
          @forelse($row['subjects'] as $subj)
            <tr>
              <td>{{ $subj['name'] }}@if($subj['code']) <span style="color:var(--muted)">({{ $subj['code'] }})</span>@endif</td>
              <td>{{ $subj['score'] }}</td>
              <td>{{ $subj['grade'] ?? '—' }}@if(!empty($subj['remark'])) <span style="color:var(--muted)">({{ $subj['remark'] }})</span>@endif</td>
              <td>{{ $subj['comment'] ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="4">No subject marks.</td></tr>
          @endforelse
        </tbody>
      </table>
      <p style="margin:12px 0 0;font-size:12px;color:var(--muted)">{{ $school->address ?: $school->district }} · {{ $school->badgeLabel() }}</p>
    </article>
  @empty
    <div class="card"><p style="color:var(--muted);margin:0">No aggregates yet for this class/period.</p></div>
  @endforelse
@endsection
