@php
  $tab = $tab ?? 'structures';
@endphp
<nav class="fee-tabs" aria-label="Fees pages">
  <a class="fee-tabs__link {{ $tab === 'structures' ? 'is-active' : '' }}" href="{{ route('app.fees.index') }}">Fee structures</a>
  <a class="fee-tabs__link {{ $tab === 'demanded' ? 'is-active' : '' }}" href="{{ route('app.fees.invoices') }}">Demanded <span class="pill">{{ $summary['demanded'] ?? 0 }}</span></a>
  <a class="fee-tabs__link {{ $tab === 'cleared' ? 'is-active' : '' }}" href="{{ route('app.fees.cleared') }}">Cleared <span class="pill pill--success">{{ $summary['cleared'] ?? 0 }}</span></a>
  <a class="fee-tabs__link {{ $tab === 'overdue' ? 'is-active' : '' }}" href="{{ route('app.fees.overdue') }}">Overdue <span class="pill pill--danger">{{ $summary['overdue'] ?? 0 }}</span></a>
  <a class="fee-tabs__link" href="{{ route('app.fees.defaulters') }}">Defaulters</a>
  <span class="pill" style="margin-left:auto">Outstanding UGX {{ number_format($summary['outstanding'] ?? 0) }}</span>
</nav>
