@extends('layouts.app')
@section('title', 'Fees')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school?->name }}</p>
      <h1 class="page-header__title">Fees</h1>
    </div>
    <div class="page-header__actions"><a class="btn ghost" href="{{ route('app.portal.home', array_filter(['student_id' => $student?->id])) }}">Portal home</a></div>
  </div>
  @include('app.portal._learner_switcher')

  @if(!$student)
    <div class="card"><p>No linked learner yet. Ask the school to link your account to a student.</p></div>
  @else
  @foreach($invoices as $invoice)
    <div class="card">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:start">
        <div>
          <h3 style="margin:0 0 6px">{{ $invoice->reference }}</h3>
          <p style="margin:0;color:var(--muted);font-size:13px">
            {{ $invoice->structure?->name ?? 'Fee invoice' }}
            · Due {{ $invoice->due_on?->format('d M Y') ?? '—' }}
            · <span class="pill">{{ $invoice->status }}</span>
          </p>
        </div>
        <div style="text-align:right">
          <div class="stat"><div class="v" style="font-size:22px">UGX {{ number_format((float)$invoice->balance, 0) }}</div><div class="l">Balance of {{ number_format((float)$invoice->amount, 0) }}</div></div>
        </div>
      </div>

      @if($invoice->payments->isNotEmpty())
        <table style="margin-top:14px">
          <thead><tr><th>Date</th><th>Method</th><th>Ref</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
            @foreach($invoice->payments as $p)
              <tr>
                <td>{{ $p->created_at?->format('d M Y') }}</td>
                <td>{{ str_replace('_', ' ', $p->method) }}</td>
                <td>{{ $p->provider_ref ?? '—' }}</td>
                <td>UGX {{ number_format((float)$p->amount, 0) }}</td>
                <td><span class="pill">{{ $p->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif

      @php
        $pendingTotal = $invoice->payments->where('status', 'pending')->sum(fn ($p) => (float) $p->amount);
        $payable = max(0, round((float) $invoice->balance - $pendingTotal, 2));
      @endphp

      @if($canPay && $payable > 0 && !in_array($invoice->status, ['paid','void'], true))
        @if(!empty($schoolPayEnabled))
          <form method="post" action="{{ route('app.portal.fees.schoolpay', $invoice) }}" style="margin-top:14px" class="grid g2">
            @csrf
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <div>
              <label>Amount (UGX)</label>
              <input name="amount" type="number" step="0.01" min="0.01" max="{{ $payable }}" value="{{ $payable }}" required>
            </div>
            <div>
              <label>MoMo phone (MTN / Airtel)</label>
              <input name="phone" type="tel" inputmode="tel" placeholder="0770123456" required>
            </div>
            <div style="display:flex;align-items:end"><button class="btn accent" type="submit">Pay with SchoolPay</button></div>
          </form>
          <p style="color:var(--muted);font-size:12px;margin:8px 0 0">Approve the debit prompt on your phone. Invoice balance updates automatically when SchoolPay confirms payment.</p>
          <details style="margin-top:12px">
            <summary style="cursor:pointer;color:var(--muted);font-size:13px">Or submit a manual payment for school verification</summary>
        @endif
        <form method="post" action="{{ route('app.portal.fees.pay', $invoice) }}" style="margin-top:14px" class="grid g2">
          @csrf
          <input type="hidden" name="student_id" value="{{ $student->id }}">
          <div>
            <label>Amount (UGX)</label>
            <input name="amount" type="number" step="0.01" min="0.01" max="{{ $payable }}" value="{{ $payable }}" required>
          </div>
          <div>
            <label>Method</label>
            <select name="method" required>
              <option value="mtn_momo">MTN MoMo</option>
              <option value="airtel_money">Airtel Money</option>
              <option value="bank">Bank</option>
              <option value="cash">Cash (at school)</option>
            </select>
          </div>
          <div>
            <label>Transaction reference</label>
            <input name="provider_ref" placeholder="Optional MoMo / bank ref">
          </div>
          <div style="display:flex;align-items:end"><button class="btn {{ !empty($schoolPayEnabled) ? 'ghost' : 'accent' }}" type="submit">Submit for verification</button></div>
        </form>
        <p style="color:var(--muted);font-size:12px;margin:8px 0 0">Manual submissions stay pending until the school verifies the payment.</p>
        @if(!empty($schoolPayEnabled))
          </details>
        @endif
      @elseif($canPay && $pendingTotal > 0 && $payable <= 0)
        <p style="color:var(--muted);font-size:13px;margin:12px 0 0">Awaiting confirmation of your submitted payment(s).</p>
      @endif
    </div>
  @endforeach

  @if($invoices->isEmpty())
    <div class="card"><p>No fee invoices for this learner.</p></div>
  @endif
  @endif
@endsection
