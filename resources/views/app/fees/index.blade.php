@extends('layouts.app')
@section('title', 'Fees')
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">{{ $school->name }}</p><h2 class="page-header__title">Fees</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @if(empty($canManageFinance))
    <p class="vx-auth-hint" style="margin-bottom:16px">Read-only finance view. Recording payments and changing invoices is limited to the bursar.</p>
  @endif

  <div class="card" id="ledger">
    <h3 style="margin-top:0">Fee follow-up</h3>
    <p style="margin:0 0 12px;color:var(--muted);font-size:13px">Demanded = balance still owed. Cleared = paid in full.</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
      <a class="btn {{ ($statusFilter ?? 'all') === 'demanded' ? 'accent' : 'ghost' }}" href="{{ route('app.fees.index', array_filter(['status'=>'demanded','class_id'=>$classId,'term_id'=>$termId,'q'=>$q])) }}">Demanded ({{ $summary['demanded'] ?? 0 }})</a>
      <a class="btn {{ ($statusFilter ?? '') === 'cleared' ? 'accent' : 'ghost' }}" href="{{ route('app.fees.index', array_filter(['status'=>'cleared','class_id'=>$classId,'term_id'=>$termId,'q'=>$q])) }}">Cleared ({{ $summary['cleared'] ?? 0 }})</a>
      <a class="btn {{ ($statusFilter ?? '') === 'overdue' ? 'accent' : 'ghost' }}" href="{{ route('app.fees.index', array_filter(['status'=>'overdue','class_id'=>$classId,'term_id'=>$termId,'q'=>$q])) }}">Overdue ({{ $summary['overdue'] ?? 0 }})</a>
      <a class="btn {{ ($statusFilter ?? 'all') === 'all' ? 'accent' : 'ghost' }}" href="{{ route('app.fees.index', array_filter(['class_id'=>$classId,'term_id'=>$termId,'q'=>$q])) }}">All open ledgers</a>
      <span class="pill" style="align-self:center">Outstanding UGX {{ number_format($summary['outstanding'] ?? 0) }}</span>
    </div>
    <form method="get" action="{{ route('app.fees.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">
      <input type="hidden" name="status" value="{{ $statusFilter ?? 'all' }}">
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
        <label>Term (structure)</label>
        <select name="term_id">
          <option value="">Any term</option>
          @foreach($terms as $t)
            <option value="{{ $t->id }}" @selected((int)($termId ?? 0) === (int)$t->id)>{{ $t->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Search student / ref</label>
        <input name="q" value="{{ $q ?? '' }}" placeholder="Name or invoice ref">
      </div>
      <button class="btn" type="submit">Filter</button>
    </form>
  </div>

  @if(!empty($canManageFinance))
  <div class="grid g2">
    <div class="card"><h3 style="margin-top:0">Fee structure</h3>
      <form method="post" action="{{ route('app.fees.structures.store') }}">@csrf
        <label>Name</label><input name="name" required>
        <label>Amount (UGX)</label><input type="number" step="0.01" name="amount" required>
        <label>Class</label><select name="class_id"><option value="">All</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <label>Term</label><select name="term_id"><option value="">—</option>@foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
        <p><button class="btn" type="submit">Save</button></p>
      </form>
    </div>
    <div class="card"><h3 style="margin-top:0">Invoice one student</h3>
      <form method="post" action="{{ route('app.fees.invoices.store') }}">@csrf
        <label>Student</label><select name="student_id">@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach</select>
        <label>Structure (optional)</label>
        <select name="fee_structure_id">
          <option value="">—</option>
          @foreach($structures->sortByDesc('is_active') as $s)
            <option value="{{ $s->id }}">{{ $s->name }}{{ $s->is_active ? '' : ' (archived)' }}</option>
          @endforeach
        </select>
        <label>Amount</label><input type="number" step="0.01" name="amount" required>
        <label>Due</label><input type="date" name="due_on">
        <p><button class="btn" type="submit">Create invoice</button></p>
      </form>
    </div>
  </div>
  @endif

  <div class="card">
    <h3 style="margin-top:0">Structures</h3>
    <table>
      <thead><tr><th>Name</th><th>Amount</th><th>Class</th><th>Term</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($structures as $s)
        <tr>
          <td>{{ $s->name }}</td>
          <td>{{ number_format($s->amount) }} UGX</td>
          <td>{{ $s->schoolClass?->name ?? 'All' }}</td>
          <td>{{ $s->term?->name ?? '—' }}</td>
          <td><span class="pill">{{ $s->is_active ? 'active' : 'archived' }}</span></td>
          <td>
            @if(!empty($canManageFinance))
            <form method="post" action="{{ route('app.fees.structures.archive', $s) }}" style="margin-bottom:8px">@csrf
              <button class="btn" type="submit">{{ $s->is_active ? 'Archive' : 'Reactivate' }}</button>
            </form>
            <form method="post" action="{{ route('app.fees.structures.update', $s) }}" style="display:flex;gap:4px;flex-wrap:wrap;align-items:center">
              @csrf
              @method('PUT')
              <input name="name" value="{{ $s->name }}" required style="width:110px">
              <input type="number" step="0.01" name="amount" value="{{ $s->amount }}" required style="width:90px">
              <select name="class_id" style="width:auto">
                <option value="">All</option>
                @foreach($classes as $c)
                  <option value="{{ $c->id }}" @selected((int) $s->class_id === (int) $c->id)>{{ $c->name }}</option>
                @endforeach
              </select>
              <select name="term_id" style="width:auto">
                <option value="">—</option>
                @foreach($terms as $t)
                  <option value="{{ $t->id }}" @selected((int) $s->term_id === (int) $t->id)>{{ $t->name }}</option>
                @endforeach
              </select>
              <button class="btn" type="submit">Update</button>
            </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="color:var(--muted)">No structures yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @if(!empty($canManageFinance))
  <div class="grid g2">
    <div class="card"><h3 style="margin-top:0">Bulk invoice a class</h3>
      <form method="post" action="{{ route('app.fees.invoices.bulk') }}">@csrf
        <label>Fee structure</label>
        <select name="fee_structure_id" required>
          @foreach($structures->sortByDesc(fn ($s) => $s->is_active ? 1 : 0)->values() as $s)
            <option value="{{ $s->id }}" @selected($loop->first)>
              {{ $s->name }} ({{ number_format($s->amount) }}){{ $s->is_active ? '' : ' — archived' }}
            </option>
          @endforeach
        </select>
        <label>Class</label><select name="class_id" required>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <label>Due</label><input type="date" name="due_on">
        <p><button class="btn accent" type="submit">Invoice whole class</button></p>
        <p style="font-size:13px;color:var(--muted);margin:8px 0 0">Safe to click twice — existing invoices are skipped, not duplicated.</p>
      </form>
    </div>
    <div class="card"><h3 style="margin-top:0">Record payment</h3>
      <form method="post" action="{{ route('app.fees.payments.store') }}">@csrf
        <label>Invoice</label><select name="invoice_id">@foreach($invoices as $inv)<option value="{{ $inv->id }}">{{ $inv->reference }} · {{ $inv->student?->full_name }} · bal {{ number_format($inv->balance) }}</option>@endforeach</select>
        <label>Amount</label><input type="number" step="0.01" name="amount" required>
        <label>Method</label><select name="method"><option value="cash">Cash</option><option value="mtn_momo">MTN MoMo</option><option value="airtel_money">Airtel Money</option><option value="bank">Bank</option><option value="schoolpay">SchoolPay</option></select>
        <label>Provider ref</label><input name="provider_ref">
        <p><button class="btn" type="submit">Record payment</button></p>
      </form>
    </div>
  </div>
  @endif

  <div class="card" id="payments">
    <table>
      <thead><tr><th>When</th><th>Invoice</th><th>Student</th><th>Method</th><th>Ref</th><th>Amount</th><th></th></tr></thead>
      <tbody>
        @forelse($pendingPayments as $p)
          <tr>
            <td>{{ $p->created_at?->format('d M Y H:i') }}</td>
            <td>{{ $p->invoice?->reference ?? '—' }}</td>
            <td>{{ $p->invoice?->student?->full_name ?? '—' }}</td>
            <td>{{ str_replace('_', ' ', $p->method) }}</td>
            <td>{{ $p->provider_ref ?? $p->schoolpay_reference ?? $p->external_reference ?? '—' }}</td>
            <td>{{ number_format((float) $p->amount) }}</td>
            <td style="white-space:nowrap">
              @if($p->method === 'schoolpay')
                <span class="pill">awaiting SchoolPay</span>
              @elseif(!empty($canManageFinance))
                <form method="post" action="{{ route('app.fees.payments.confirm', $p) }}" style="display:inline">@csrf
                  <button class="btn accent" type="submit" style="padding:4px 10px;font-size:12px">Confirm</button>
                </form>
                <form method="post" action="{{ route('app.fees.payments.reject', $p) }}" style="display:inline">@csrf
                  <button class="btn ghost" type="submit" style="padding:4px 10px;font-size:12px">Reject</button>
                </form>
              @else
                <span class="pill">pending</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7">No pending parent payments.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if(!empty($canManageFinance) && $school->schoolPayConfigured())
    <div class="card">
      <h3 style="margin-top:0">SchoolPay sync</h3>
      <p style="margin:0 0 10px;color:var(--muted);font-size:13px">Pull completed SchoolPay receipts for a day and apply any that are not yet on invoices. Student SchoolPay payment codes must be set on learner records.</p>
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

  <div class="card" id="invoices">
    <h3 style="margin-top:0">
      @if(($statusFilter ?? 'all') === 'demanded') Demanded invoices
      @elseif(($statusFilter ?? '') === 'cleared') Cleared invoices
      @elseif(($statusFilter ?? '') === 'overdue') Overdue invoices
      @else Invoices
      @endif
      <span style="font-weight:400;color:var(--muted);font-size:13px">({{ $invoices->count() }} shown)</span>
    </h3>
    @forelse($groupedInvoices as $classLabel => $rows)
      <h4 style="margin:16px 0 8px;font-size:14px">{{ $classLabel }} <span style="color:var(--muted);font-weight:400">({{ $rows->count() }})</span></h4>
      <table>
        <thead><tr><th>Ref</th><th>Student</th><th>Amount</th><th>Balance</th><th>Due</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach($rows as $inv)
            <tr>
              <td>{{ $inv->reference }}</td>
              <td>{{ $inv->student?->full_name ?? '—' }}</td>
              <td>{{ number_format($inv->amount) }}</td>
              <td>{{ number_format($inv->balance) }}</td>
              <td>{{ $inv->due_on?->format('d M Y') ?? '—' }}</td>
              <td><span class="pill">{{ $inv->status }}</span></td>
              <td style="white-space:nowrap">
                @if(!empty($canManageFinance) && $inv->status !== 'void')
                  <form method="post" action="{{ route('app.fees.invoices.void', $inv) }}" style="display:inline" onsubmit="return confirm('Void this invoice?')">
                    @csrf
                    <button class="btn ghost" type="submit" style="padding:4px 10px;font-size:12px">Void</button>
                  </form>
                  <form method="post" action="{{ route('app.fees.invoices.discount', $inv) }}" style="display:inline-flex;gap:4px;align-items:center">
                    @csrf
                    <input type="number" step="0.01" name="amount" placeholder="Discount" style="width:90px">
                    <input name="reason" placeholder="Reason" style="width:110px">
                    <button class="btn ghost" type="submit" style="padding:4px 10px;font-size:12px">Apply</button>
                  </form>
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
@endsection
