@extends('layouts.app')
@section('title', 'Fees')
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">PearlEdu</p><h2 class="page-header__title">Fees</h2></div></div>
  @if(session('status'))<p style="color:var(--brand);margin-bottom:12px">{{ session('status') }}</p>@endif
  <div class="grid g2">
    <div class="card"><h3 style="margin-top:0">Fee structure</h3>
      <form method="post" action="{{ route('app.fees.structures.store') }}">@csrf
        <label>Name</label><input name="name" required>
        <label>Amount (UGX)</label><input type="number" step="0.01" name="amount" required>
        <label>Class</label><select name="class_id"><option value="">All</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <label>Term</label><select name="term_id"><option value="">—</option>@foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
        <p><button class="btn" type="submit">Save</button></p>
      </form>
      <ul>@foreach($structures as $s)<li>{{ $s->name }} — {{ number_format($s->amount) }} UGX</li>@endforeach</ul>
    </div>
    <div class="card"><h3 style="margin-top:0">Invoice &amp; payment</h3>
      <form method="post" action="{{ route('app.fees.invoices.store') }}">@csrf
        <label>Student</label><select name="student_id">@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach</select>
        <label>Amount</label><input type="number" step="0.01" name="amount" required>
        <label>Due</label><input type="date" name="due_on">
        <p><button class="btn" type="submit">Create invoice</button></p>
      </form>
      <form method="post" action="{{ route('app.fees.payments.store') }}">@csrf
        <label>Invoice</label><select name="invoice_id">@foreach($invoices as $inv)<option value="{{ $inv->id }}">#{{ $inv->id }} bal {{ $inv->balance }}</option>@endforeach</select>
        <label>Amount</label><input type="number" step="0.01" name="amount" required>
        <label>Method</label><select name="method"><option value="cash">Cash</option><option value="mtn_momo">MTN MoMo</option><option value="airtel_money">Airtel Money</option><option value="bank">Bank</option></select>
        <label>Provider ref</label><input name="provider_ref">
        <p><button class="btn" type="submit">Record payment</button></p>
      </form>
    </div>
  </div>
@endsection
