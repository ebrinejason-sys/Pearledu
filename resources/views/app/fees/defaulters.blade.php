@extends('layouts.app')
@section('title', $class ? 'Defaulters · '.$class->name : 'Fee defaulters')
@section('content')
  <div class="page-header {{ !empty($print) ? 'no-print' : '' }}">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('app.fees.index') }}">Fees</a></p>
      <h2 class="page-header__title">{{ $class ? 'Fee defaulters · '.$class->displayName() : 'Fee defaulters' }}</h2>
    </div>
    <div class="page-header__actions">
      @if($class)
        <a class="btn ghost" href="{{ route('app.fees.defaulters', ['class_id' => $class->id, 'print' => 1]) }}">Print</a>
        @if(!empty($canNotify))
          <form method="post" action="{{ route('app.fees.defaulters.notify') }}">
            @csrf
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <button class="btn accent" type="submit">Notify class teacher</button>
          </form>
        @endif
      @endif
    </div>
  </div>
  @if(session('status'))<div class="status no-print" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @error('class_id')<div class="err no-print">{{ $message }}</div>@enderror

  <form method="get" action="{{ route('app.fees.defaulters') }}" class="card no-print" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
    <div>
      <label>Class</label>
      <select name="class_id" required>
        <option value="">Select class</option>
        @foreach($classes as $option)
          <option value="{{ $option->id }}" @selected($class && (int) $class->id === (int) $option->id)>{{ $option->displayName() }}</option>
        @endforeach
      </select>
    </div>
    <button class="btn" type="submit">Show defaulters</button>
  </form>

  @if($class)
  <div class="card">
    <p><strong>{{ $school->name }}</strong> — {{ $class->displayName() }} — {{ now()->timezone(config('app.timezone'))->format('j F Y') }}</p>
    <table>
      <thead><tr><th>Learner</th><th>Gender</th><th>Open invoices</th><th>Balance</th></tr></thead>
      <tbody>
      @forelse($rows as $row)
        <tr>
          <td>
            @if(!empty($canViewLearners))
              <a href="{{ route('app.students.show', $row['student']) }}">{{ $row['student']->full_name }}</a>
            @else
              {{ $row['student']->full_name }}
            @endif
          </td>
          <td>{{ \App\Support\Gender::label($row['student']->gender) }}</td>
          <td>{{ $row['invoices'] }}</td>
          <td>UGX {{ number_format($row['balance']) }}</td>
        </tr>
      @empty
        <tr><td colspan="4">No defaulters in this class.</td></tr>
      @endforelse
      </tbody>
    </table>
    <p><strong>Total outstanding:</strong> UGX {{ number_format($rows->sum('balance')) }}</p>
  </div>
  @endif
@endsection
@section('head')
<style>
  @media print {
    .no-print, .sidebar, .topbar, nav { display:none !important; }
  }
</style>
@if(!empty($print))
<script>window.addEventListener('load', () => window.print());</script>
@endif
@endsection
