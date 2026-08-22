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
  </div>
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
@endsection
