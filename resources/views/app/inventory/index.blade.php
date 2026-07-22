@extends('layouts.app')
@section('title','Inventory · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Operations</p><h2 class="page-header__title">Inventory</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Add item</h3>
      <form method="post" action="{{ route('app.inventory.store') }}">@csrf
        <label>Name</label><input name="name" required>
        <label>SKU</label><input name="sku">
        <label>Quantity</label><input type="number" name="quantity" value="0" min="0">
        <label>Location</label><input name="location">
        <p style="margin-top:14px"><button class="btn" type="submit">Save</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Stock</h3>
      <table>
        <thead><tr><th>Name</th><th>SKU</th><th>Qty</th><th>Location</th></tr></thead>
        <tbody>
        @forelse($items as $item)
          <tr><td>{{ $item->name }}</td><td>{{ $item->sku ?: '—' }}</td><td>{{ $item->quantity }}</td><td>{{ $item->location ?: '—' }}</td></tr>
        @empty
          <tr><td colspan="4" style="color:var(--muted)">Empty.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
