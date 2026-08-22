@extends('layouts.app')
@section('title', $title)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h1 class="page-header__title">{{ $title }}</h1>
    </div>
  </div>
  @if(session('receipt_id'))
    <div class="status" role="status">
      Payment recorded.
      <a class="btn accent" href="{{ route('app.fees.receipts.show', session('receipt_id')) }}?print=1" target="_blank" rel="noopener">Print receipt</a>
      @if(!empty($canManageFinance))
        <form method="post" action="{{ route('app.fees.receipts.email', session('receipt_id')) }}" style="display:inline">@csrf
          <button class="btn ghost" type="submit">Email receipt</button>
        </form>
      @endif
    </div>
  @endif

  @include('app.fees._nav', ['tab' => $statusFilter, 'summary' => $summary])

  <div class="emis-filter">
    <form method="get" action="{{ url()->current() }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">
      <div>
        <label>Class</label>
        <select name="class_id">
          <option value="">All classes</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected((int)($classId ?? 0) === (int)$c->id)>{{ $c->displayName() }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Term</label>
        <select name="term_id">
          <option value="">Any term</option>
          @foreach($terms as $t)
            <option value="{{ $t->id }}" @selected((int)($termId ?? 0) === (int)$t->id)>{{ $t->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Search</label>
        <input name="q" value="{{ $q ?? '' }}" placeholder="Name or invoice ref">
      </div>
      <button class="btn accent" type="submit">Apply</button>
    </form>
  </div>

  @if(($statusFilter ?? '') === 'demanded' && !empty($canManageFinance) && $school->schoolPayConfigured())
    <div class="card">
      <h3 style="margin-top:0">SchoolPay sync</h3>
      <form method="post" action="{{ route('app.fees.schoolpay.sync') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        @csrf
        <div>
          <label>Date</label>
          <input type="date" name="date" value="{{ now()->toDateString() }}" required>
        </div>
        <button class="btn" type="submit">Sync SchoolPay</button>
      </form>
      @error('schoolpay')<div class="err" style="margin-top:8px">{{ $message }}</div>@enderror
    </div>
  @endif

  @if(($statusFilter ?? '') === 'demanded' && $pendingPayments->isNotEmpty())
  <div class="card" id="payments">
    <h3 style="margin-top:0">Pending parent payments</h3>
    <table>
      <thead><tr><th>When</th><th>Invoice</th><th>Student</th><th>Amount</th><th></th></tr></thead>
      <tbody>
        @foreach($pendingPayments as $p)
          <tr>
            <td>{{ $p->created_at?->format('d M Y H:i') }}</td>
            <td>{{ $p->invoice?->reference ?? '—' }}</td>
            <td>{{ $p->invoice?->student?->full_name ?? '—' }}</td>
            <td>{{ number_format((float) $p->amount) }}</td>
            <td>
              @if(!empty($canManageFinance) && $p->method !== 'schoolpay')
                <form method="post" action="{{ route('app.fees.payments.confirm', $p) }}" style="display:inline">@csrf
                  <button class="btn accent" type="submit">Confirm</button>
                </form>
                <button type="button" class="btn ghost js-decide-pay"
                        data-open-modal="pay-decision-modal"
                        data-action="{{ route('app.fees.payments.reject', $p) }}"
                        data-title="Reject this payment"
                        data-submit="Reject payment"
                        data-label="{{ $p->invoice?->reference }} · {{ $p->invoice?->student?->full_name }} · UGX {{ number_format((float) $p->amount) }}">Reject</button>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif

  <div class="card">
    @forelse($groupedInvoices as $classLabel => $rows)
      <h3 style="margin:16px 0 8px;font-size:16px">{{ $classLabel }}</h3>
      <table>
        <thead><tr><th>Ref</th><th>Learner</th><th>Fee</th><th>Amount</th><th>Balance</th><th>Due</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach($rows as $inv)
            @php
              $tone = $inv->status === 'paid' ? 'pill--active' : (($inv->due_on && $inv->due_on->isPast() && (float)$inv->balance > 0) ? 'pill--danger' : ($inv->status === 'partial' ? 'pill--warning' : 'pill--muted'));
              $lastPay = $inv->payments->first();
            @endphp
            <tr>
              <td>{{ $inv->reference }}</td>
              <td>{{ $inv->student?->full_name ?? '—' }}</td>
              <td>{{ $inv->structure?->name ?? '—' }}</td>
              <td>UGX {{ number_format($inv->amount) }}</td>
              <td>UGX {{ number_format($inv->balance) }}</td>
              <td>{{ $inv->due_on?->format('d M Y') ?? '—' }}</td>
              <td><span class="pill {{ $tone }}">{{ strtoupper($inv->status) }}</span></td>
              <td style="white-space:nowrap">
                @if(!empty($canManageFinance) && in_array($inv->status, ['open', 'partial'], true))
                  <button type="button" class="btn accent js-pay" data-invoice="{{ $inv->id }}" data-balance="{{ $inv->balance }}" data-label="{{ $inv->reference }} · {{ $inv->student?->full_name }}">Record payment</button>
                @endif
                @if($lastPay)
                  <a class="btn ghost" href="{{ route('app.fees.receipts.show', $lastPay) }}?print=1" target="_blank" rel="noopener">Print</a>
                  @if(!empty($canManageFinance))
                    <form method="post" action="{{ route('app.fees.receipts.email', $lastPay) }}" style="display:inline">@csrf
                      <button class="btn ghost" type="submit">Email</button>
                    </form>
                    @if(empty($lastPay->reverses_payment_id))
                      <button type="button" class="btn ghost js-decide-pay"
                              data-open-modal="pay-decision-modal"
                              data-action="{{ route('app.fees.payments.reverse', $lastPay) }}"
                              data-title="Reverse this payment"
                              data-submit="Reverse payment"
                              data-label="{{ $inv->reference }} · {{ $inv->student?->full_name }} · UGX {{ number_format((float) $lastPay->amount) }}">Reverse</button>
                    @endif
                  @endif
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @empty
      <p style="color:var(--muted);margin:0">No invoices match this filter.</p>
    @endforelse
  </div>

  @if(!empty($canManageFinance))
  <dialog class="pe-modal" id="pay-modal">
    <form method="post" action="{{ route('app.fees.payments.store') }}" class="pe-modal__card">
      @csrf
      <h2 style="margin-top:0">Record payment</h2>
      <p id="pay-label" style="color:var(--muted);font-size:14px"></p>
      <input type="hidden" name="invoice_id" id="pay-invoice">
      <label>Amount</label>
      <input type="number" step="0.01" name="amount" id="pay-amount" required>
      <label>Method</label>
      <select name="method">
        <option value="cash">Cash</option>
        <option value="mtn_momo">MTN MoMo</option>
        <option value="airtel_money">Airtel Money</option>
        <option value="bank">Bank</option>
        <option value="schoolpay">SchoolPay</option>
      </select>
      <label>Provider ref</label>
      <input name="provider_ref">
      <p style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
        <button class="btn ghost" type="button" id="pay-cancel">Cancel</button>
        <button class="btn accent" type="submit">Save payment</button>
      </p>
    </form>
  </dialog>
  <dialog class="pe-modal pe-modal--form" id="pay-decision-modal">
    <form method="post" action="" class="pe-modal__card" id="pay-decision-form">
      @csrf
      <h2 id="pay-decision-title" style="margin-top:0">Document this decision</h2>
      <p id="pay-decision-label" style="color:var(--muted);font-size:14px"></p>
      <label for="pay-decision-reason">Reason</label>
      <textarea id="pay-decision-reason" name="reason" required minlength="8" maxlength="500" rows="3" placeholder="Why is this payment being reversed or rejected?">{{ old('reason') }}</textarea>
      @error('reason')<div class="err">{{ $message }}</div>@enderror
      <p style="color:var(--muted);font-size:13px;margin:8px 0 0">The reason is stored on the payment and written to the audit trail. Only the bursar (or school admin as break-glass) can reverse or reject a payment.</p>
      <p style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
        <button class="btn ghost" type="button" data-close-modal>Cancel</button>
        <button class="btn accent" type="submit" id="pay-decision-submit">Save</button>
      </p>
    </form>
  </dialog>
  @endif
@endsection
@section('head')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('pay-modal');
    if (modal) {
      document.querySelectorAll('.js-pay').forEach(function (btn) {
        btn.addEventListener('click', function () {
          document.getElementById('pay-invoice').value = btn.getAttribute('data-invoice');
          document.getElementById('pay-amount').value = btn.getAttribute('data-balance');
          document.getElementById('pay-label').textContent = btn.getAttribute('data-label');
          modal.showModal();
        });
      });
      document.getElementById('pay-cancel').addEventListener('click', function () { modal.close(); });
    }
    var decideForm = document.getElementById('pay-decision-form');
    document.querySelectorAll('.js-decide-pay').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!decideForm) return;
        decideForm.setAttribute('action', btn.getAttribute('data-action'));
        document.getElementById('pay-decision-title').textContent = btn.getAttribute('data-title');
        document.getElementById('pay-decision-label').textContent = btn.getAttribute('data-label');
        document.getElementById('pay-decision-submit').textContent = btn.getAttribute('data-submit');
      });
    });
    @if($errors->has('reason'))
    var decideModal = document.getElementById('pay-decision-modal');
    if (decideModal && decideModal.showModal && !decideModal.open) decideModal.showModal();
    @endif
  });
</script>
@endsection
