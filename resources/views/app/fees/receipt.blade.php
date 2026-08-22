@extends('layouts.app')
@section('title', 'Receipt '.$payment->invoice?->reference)
@section('content')
  <div class="page-header no-print">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h1 class="page-header__title">Fee receipt</h1>
    </div>
    <div class="page-header__actions">
      <button class="btn accent" type="button" onclick="window.print()">Print</button>
      @if(in_array('finance.manage', auth()->user()->permissionsForSchool($school->id), true))
        <form method="post" action="{{ route('app.fees.receipts.email', $payment) }}">@csrf
          <button class="btn ghost" type="submit">Email</button>
        </form>
        @if($payment->status === 'confirmed' && empty($payment->reverses_payment_id))
          <button type="button" class="btn ghost" data-open-modal="pay-decision-modal">Reverse payment</button>
        @endif
      @endif
    </div>
  </div>
  <div class="card receipt-card">
    <p style="margin:0 0 4px;letter-spacing:.08em;text-transform:uppercase;font-size:12px;color:var(--muted)">{{ $school->name }}</p>
    <h2 style="margin:0 0 16px">Official receipt</h2>
    <p><strong>Learner:</strong> {{ $payment->invoice?->student?->full_name }}</p>
    <p><strong>Class:</strong> {{ $payment->invoice?->student?->schoolClass?->displayName() ?? '—' }}</p>
    <p><strong>Invoice:</strong> {{ $payment->invoice?->reference }}</p>
    <p><strong>Fee:</strong> {{ $payment->invoice?->structure?->name ?: 'School fees' }}</p>
    <p><strong>Amount paid:</strong> UGX {{ number_format((float) $payment->amount) }}</p>
    <p><strong>Balance:</strong> UGX {{ number_format((float) ($payment->invoice?->balance ?? 0)) }}</p>
    <p><strong>Method:</strong> {{ str_replace('_', ' ', $payment->method) }}</p>
    <p><strong>Date:</strong> {{ $payment->verified_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? $payment->created_at?->format('d M Y') }}</p>
    @if($payment->recordedBy)
      <p><strong>Recorded by:</strong> {{ $payment->recordedBy->full_name }}</p>
    @endif
    @if($payment->status === 'reversed' || $payment->reverses_payment_id)
      <p><strong>Status:</strong> {{ $payment->reverses_payment_id ? 'Reversal' : 'Reversed' }}</p>
    @endif
    @if($payment->decision_reason)
      <p><strong>Reason:</strong> {{ $payment->decision_reason }}</p>
    @endif
  </div>
  @if(in_array('finance.manage', auth()->user()->permissionsForSchool($school->id), true) && $payment->status === 'confirmed' && empty($payment->reverses_payment_id))
  <dialog class="pe-modal pe-modal--form no-print" id="pay-decision-modal">
    <form method="post" action="{{ route('app.fees.payments.reverse', $payment) }}" class="pe-modal__card">
      @csrf
      <h2 style="margin-top:0">Reverse this payment</h2>
      <p style="color:var(--muted);font-size:14px">UGX {{ number_format((float) $payment->amount) }} will be restored to the invoice balance.</p>
      <label for="receipt-decision-reason">Reason</label>
      <textarea id="receipt-decision-reason" name="reason" required minlength="8" maxlength="500" rows="3" placeholder="Why is this payment being reversed?">{{ old('reason') }}</textarea>
      @error('reason')<div class="err">{{ $message }}</div>@enderror
      <p style="color:var(--muted);font-size:13px;margin:8px 0 0">The reason is stored on the payment and written to the audit trail.</p>
      <p style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
        <button class="btn ghost" type="button" data-close-modal>Cancel</button>
        <button class="btn accent" type="submit">Reverse payment</button>
      </p>
    </form>
  </dialog>
  @endif
@endsection
@section('head')
<style>
  @media print {
    .no-print, .sidebar, .topbar, nav, .page-header__actions { display:none !important; }
    .wrap { max-width:none; padding:0; }
    .receipt-card { border:0; box-shadow:none; }
  }
</style>
@if(!empty($print))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
@if($errors->has('reason'))
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('pay-decision-modal');
    if (modal && modal.showModal && !modal.open) modal.showModal();
  });
</script>
@endif
@endsection
