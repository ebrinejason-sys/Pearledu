@extends('layouts.app')
@section('title','Landing pricing')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Marketing</p>
      <h2 class="page-header__title">PearlEdu landing pricing</h2>
    </div>
  </div>
  <p style="color:var(--muted);font-size:14px;margin:-8px 0 18px">
    These plans render on the public PearlEdu landing page. Leave the price empty to show
    &ldquo;Contact us&rdquo; instead of an amount. Features are one per line. Lower sort order appears first.
  </p>

  @foreach($errors->all() as $e)<div class="err" style="margin-bottom:10px">{{ $e }}</div>@endforeach

  @foreach($plans as $plan)
    <div class="card">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
        <h3 style="margin:0">{{ $plan->name }}</h3>
        @if($plan->is_highlighted)<span class="pill">Highlighted</span>@endif
        @unless($plan->is_active)<span class="pill" style="background:#fdecea;color:#b3261e">Inactive</span>@endunless
      </div>
      <form method="post" action="{{ route('platform.pricing.update', $plan) }}">
        @csrf @method('PUT')
        <div class="grid g4">
          <div><label>Name</label><input name="name" value="{{ old('name', $plan->name) }}" required></div>
          <div><label>Tagline</label><input name="tagline" value="{{ old('tagline', $plan->tagline) }}"></div>
          <div><label>Price (empty = &ldquo;Contact us&rdquo;)</label><input name="price" type="number" min="0" value="{{ old('price', $plan->price) }}"></div>
          <div><label>Currency</label><input name="currency" value="{{ old('currency', $plan->currency) }}" required></div>
        </div>
        <div class="grid g4">
          <div><label>Billing period</label><input name="billing_period" value="{{ old('billing_period', $plan->billing_period) }}" required></div>
          <div><label>Sort order</label><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $plan->sort_order) }}" required></div>
          <div><label>Highlighted</label>
            <select name="is_highlighted"><option value="1" @selected($plan->is_highlighted)>Yes</option><option value="0" @selected(!$plan->is_highlighted)>No</option></select>
          </div>
          <div><label>Active</label>
            <select name="is_active"><option value="1" @selected($plan->is_active)>Yes</option><option value="0" @selected(!$plan->is_active)>No</option></select>
          </div>
        </div>
        <label>Features (one per line)</label>
        <textarea name="features_text" rows="4">{{ old('features_text', implode("\n", $plan->features ?? [])) }}</textarea>
        <p style="display:flex;gap:8px;align-items:center">
          <button class="btn" type="submit">Save plan</button>
        </p>
      </form>
      <form method="post" action="{{ route('platform.pricing.destroy', $plan) }}"
            onsubmit="return confirm('Delete the {{ $plan->name }} plan? This removes it from the landing page immediately.')">
        @csrf @method('DELETE')
        <button class="btn ghost" type="submit" style="color:#b3261e;border-color:#f0c0bc">Delete plan</button>
      </form>
    </div>
  @endforeach

  <div class="card">
    <h3>Add a plan</h3>
    <form method="post" action="{{ route('platform.pricing.store') }}">
      @csrf
      <div class="grid g4">
        <div><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
        <div><label>Tagline</label><input name="tagline" value="{{ old('tagline') }}"></div>
        <div><label>Price (empty = &ldquo;Contact us&rdquo;)</label><input name="price" type="number" min="0" value="{{ old('price') }}"></div>
        <div><label>Currency</label><input name="currency" value="{{ old('currency', 'UGX') }}" required></div>
      </div>
      <div class="grid g4">
        <div><label>Billing period</label><input name="billing_period" value="{{ old('billing_period', 'per term') }}" required></div>
        <div><label>Sort order</label><input name="sort_order" type="number" min="0" value="{{ old('sort_order', ($plans->max('sort_order') ?? 0) + 1) }}" required></div>
        <div><label>Highlighted</label>
          <select name="is_highlighted"><option value="0">No</option><option value="1">Yes</option></select>
        </div>
        <div><label>Active</label>
          <select name="is_active"><option value="1">Yes</option><option value="0">No</option></select>
        </div>
      </div>
      <label>Features (one per line)</label>
      <textarea name="features_text" rows="4">{{ old('features_text') }}</textarea>
      <p><button class="btn accent" type="submit">Add plan</button></p>
    </form>
  </div>
@endsection
