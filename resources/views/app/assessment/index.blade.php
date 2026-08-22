@extends('layouts.app')
@section('title','Assessment · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Assessment periods</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">
        @if($canEnter)<a href="{{ route('app.assessment.marks') }}">Marks</a> ·@endif
        <a href="{{ route('app.assessment.broadsheet') }}">Broadsheet</a> ·
        <a href="{{ route('app.assessment.reports') }}">Report cards</a>
      </p>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="grid g2">
    @if($canManage)
    <div class="card">
      <h3 style="margin-top:0">New period</h3>
      <form method="post" action="{{ route('app.assessment.periods.store') }}">
        @csrf
        <label>Name</label>
        <input name="name" required placeholder="e.g. Mid of term">
        <label>Set</label>
        <select name="kind">
          <option value="bot">Beginning of term (BOT)</option>
          <option value="mot">Mid of term (MOT)</option>
          <option value="eot">End of term (EOT)</option>
          <option value="custom">Custom test</option>
        </select>
        <label>Term</label>
        <select name="term_id">
          <option value="">—</option>
          @foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
        </select>
        <label>Marks upload deadline</label>
        <input type="date" name="entry_deadline">
        <label>Max score</label>
        <input type="number" step="0.01" name="max_score" value="100">
        <p style="margin-top:14px"><button class="btn" type="submit">Create</button></p>
      </form>
    </div>
    @endif
    <div class="card">
      <h3 style="margin-top:0">Periods</h3>
      <table>
        <thead><tr><th>Set</th><th>Name</th><th>Term</th><th>Deadline</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($periods as $p)
          <tr>
            <td><span class="pill pill--active">{{ $p->kindShort() }}</span></td>
            <td>{{ $p->name }}</td>
            <td>{{ $p->term?->name ?: '—' }}</td>
            <td>{{ $p->entry_deadline?->format('d M Y') ?: '—' }}</td>
            <td><span class="pill">{{ str_replace('_', ' ', $p->status) }}</span></td>
            <td style="display:flex;gap:6px;flex-wrap:wrap">
              @if($canManage)
                @foreach($periodActions[$p->id] ?? [] as $action)
                  <form method="post" action="{{ route('app.assessment.periods.transition', $p) }}">
                    @csrf
                    <input type="hidden" name="to" value="{{ $action['to'] }}">
                    <button class="btn ghost" type="submit">{{ $action['label'] }}</button>
                  </form>
                @endforeach
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="color:var(--muted)">None yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
