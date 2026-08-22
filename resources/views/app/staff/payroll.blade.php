@extends('layouts.app')
@section('title', 'Salaries')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h2 class="page-header__title">Staff salaries</h2>
      @if(empty($canManage))
        <p style="color:var(--muted);font-size:14px">Read-only. The bursar records salary amounts and payments.</p>
      @endif
    </div>
  </div>
  @if(session('status'))<div class="status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="card" style="margin-bottom:16px">
    <table>
      <thead><tr><th>Staff</th><th>Current salary</th><th>Effective</th>@if(!empty($canManage))<th></th>@endif</tr></thead>
      <tbody>
      @foreach($staff as $person)
        @php($salary = $salaries[$person->id] ?? null)
        <tr>
          <td>
            @if(in_array('staff.view', auth()->user()->permissionsForSchool($school->id), true) || in_array('staff.manage', auth()->user()->permissionsForSchool($school->id), true))
              <a href="{{ route('app.staff.show', $person) }}">{{ $person->full_name }}</a>
            @else
              {{ $person->full_name }}
            @endif
          </td>
          <td>{{ $salary ? 'UGX '.number_format($salary->amount) : '—' }}</td>
          <td>{{ $salary?->effective_on?->format('Y-m-d') ?? '—' }}</td>
          @if(!empty($canManage))
            <td>
              <form method="post" action="{{ route('app.staff.payroll.salary', $person) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">
                @csrf
                <input name="amount" type="number" min="0" value="{{ $salary->amount ?? '' }}" placeholder="Amount" required style="width:120px">
                <input name="effective_on" type="date" value="{{ old('effective_on', now()->toDateString()) }}" required>
                <button class="btn ghost" type="submit">Save</button>
              </form>
              <form method="post" action="{{ route('app.staff.payroll.pay', $person) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-top:8px">
                @csrf
                <input name="amount" type="number" min="1" placeholder="Payment" required style="width:120px">
                <input name="paid_on" type="date" value="{{ now()->toDateString() }}" required>
                <select name="method"><option value="bank">Bank</option><option value="mobile_money">Mobile money</option><option value="cash">Cash</option></select>
                <input name="reference" placeholder="Reference" style="width:120px">
                <button class="btn" type="submit">Record payment</button>
              </form>
            </td>
          @endif
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Payment history</h3>
    <table>
      <thead><tr><th>Date</th><th>Staff</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
      <tbody>
      @forelse($payments as $payment)
        <tr>
          <td>{{ $payment->paid_on->format('Y-m-d') }}</td>
          <td>{{ $payment->user?->full_name }}</td>
          <td>UGX {{ number_format($payment->amount) }}</td>
          <td>{{ $payment->method }}</td>
          <td>{{ $payment->reference ?: '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No payments recorded.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
