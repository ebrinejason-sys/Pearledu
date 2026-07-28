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
        <input name="name" required>
        <label>Term</label>
        <select name="term_id">
          <option value="">—</option>
          @foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
        </select>
        <label>Max score</label>
        <input type="number" step="0.01" name="max_score" value="100">
        <p style="margin-top:14px"><button class="btn" type="submit">Create</button></p>
      </form>
    </div>
    @endif
    <div class="card">
      <h3 style="margin-top:0">Periods</h3>
      <table>
        <thead><tr><th>Name</th><th>Term</th><th>Max</th><th>Locked</th></tr></thead>
        <tbody>
        @forelse($periods as $p)
          <tr>
            <td>{{ $p->name }}</td>
            <td>{{ $p->term?->name ?: '—' }}</td>
            <td>{{ $p->max_score }}</td>
            <td>{{ $p->is_locked ? 'Yes' : 'No' }}</td>
          </tr>
        @empty
          <tr><td colspan="4" style="color:var(--muted)">None yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
