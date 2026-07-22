@extends('layouts.app')
@section('title','Promotions · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Promotions</h2>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @error('from_class_id')<div class="err">{{ $message }}</div>@enderror
  @error('batch')<div class="err">{{ $message }}</div>@enderror

  <div class="card">
    <h3 style="margin-top:0">Draft batch</h3>
    <form method="post" action="{{ route('app.promotions.store') }}">
      @csrf
      <label>From year</label>
      <select name="from_year_id" required>@foreach($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach</select>
      <label>To year</label>
      <select name="to_year_id" required>@foreach($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach</select>
      <label>From class</label>
      <select name="from_class_id" required>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
      <label>To class</label>
      <select name="to_class_id">@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
      <label>Outcome</label>
      <select name="outcome">
        <option value="promote">Promote</option>
        <option value="repeat">Repeat</option>
        <option value="graduate">Graduate</option>
        <option value="transfer">Transfer</option>
      </select>
      <p style="margin-top:14px"><button class="btn" type="submit">Create draft</button></p>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Batches</h3>
    <table>
      <thead><tr><th>From → To</th><th>Items</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($batches as $batch)
        <tr>
          <td>{{ $batch->fromYear?->name }} → {{ $batch->toYear?->name }}</td>
          <td>{{ $batch->items->count() }}</td>
          <td><span class="pill">{{ $batch->status }}</span></td>
          <td>
            @if($batch->status !== 'committed')
              <form method="post" action="{{ route('app.promotions.commit', $batch) }}">@csrf
                <button class="btn" type="submit">Commit</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No batches.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
