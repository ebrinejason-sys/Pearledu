@extends('layouts.app')
@section('title', 'Staff clock')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h2 class="page-header__title">Staff clock</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Scan a staff ID barcode or type the code. The next scan toggles clock in / clock out.</p>
    </div>
    <div class="page-header__actions">
      <a class="btn ghost" href="{{ route('app.staff.clock.history') }}">History</a>
    </div>
  </div>
  @if(session('status'))<div class="status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @if(!empty($canMark))
    <form method="post" action="{{ route('app.staff.clock.punch') }}" class="card" style="margin-bottom:16px">
      @csrf
      <label for="badge-code">Barcode / ID code</label>
      <input id="badge-code" name="code" autofocus autocomplete="off" required placeholder="Scan or type, then Enter">
      @error('code')<div class="err">{{ $message }}</div>@enderror
      <p style="margin-top:14px"><button class="btn accent" type="submit">Clock</button></p>
    </form>
  @endif
  <div class="card">
    <h3 style="margin-top:0">Today</h3>
    <table>
      <thead><tr><th>Time</th><th>Staff</th><th>In / out</th></tr></thead>
      <tbody>
      @forelse($punches as $punch)
        <tr>
          <td>{{ $punch->punched_at->timezone(config('app.timezone'))->format('H:i') }}</td>
          <td>{{ $punch->user?->full_name }}</td>
          <td>{{ $punch->direction === 'in' ? 'Clock in' : 'Clock out' }}</td>
        </tr>
      @empty
        <tr><td colspan="3" style="color:var(--muted)">No punches yet today.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
