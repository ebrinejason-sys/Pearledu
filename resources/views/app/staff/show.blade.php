@extends('layouts.app')
@section('title', $staff->full_name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('app.staff.index') }}">Staff</a></p>
      <h2 class="page-header__title">{{ $staff->full_name }}</h2>
      <p style="color:var(--muted)">{{ \App\Support\Gender::label($staff->gender) }}</p>
    </div>
    <div class="page-header__actions">
      @if(!empty($canPrintId))
        <a class="btn" href="{{ route('app.staff.id', $staff) }}">Print ID</a>
      @endif
    </div>
  </div>
  <div class="grid g2">
    <div class="card">
      @if($staff->avatarUrl())
        <img src="{{ $staff->avatarUrl() }}" alt="" width="96" height="96" style="width:96px;height:96px;border-radius:12px;object-fit:cover">
      @endif
      <p>Email: {{ $staff->email ?: '—' }}<br>Phone: {{ $staff->phone ?: '—' }}</p>
      <p>Roles:
        @foreach($roles as $assignment)
          <span class="pill">{{ $assignment->role?->label }}@if($assignment->schoolClass) · {{ $assignment->schoolClass->name }}@endif</span>
        @endforeach
      </p>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Clocking</h3>
      @forelse($punches as $punch)
        <p>{{ $punch->punched_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }} · {{ $punch->direction }}</p>
      @empty
        <p style="color:var(--muted)">No clock records, or you cannot view staff attendance.</p>
      @endforelse
    </div>
  </div>
  @if(!empty($canViewPayroll))
    <div class="card" style="margin-top:16px">
      <h3 style="margin-top:0">Salary</h3>
      <p>{{ $salary ? 'UGX '.number_format($salary->amount).' from '.$salary->effective_on->format('Y-m-d') : 'No salary set.' }}</p>
      <table>
        <thead><tr><th>Paid on</th><th>Amount</th><th>Method</th></tr></thead>
        <tbody>
        @forelse($payments as $payment)
          <tr><td>{{ $payment->paid_on->format('Y-m-d') }}</td><td>UGX {{ number_format($payment->amount) }}</td><td>{{ $payment->method }}</td></tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">No payments yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  @endif
@endsection
