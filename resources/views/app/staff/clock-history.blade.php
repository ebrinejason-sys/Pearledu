@extends('layouts.app')
@section('title', 'Staff clock history')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('app.staff.clock') }}">Staff clock</a></p>
      <h2 class="page-header__title">Clock history</h2>
    </div>
  </div>
  <form method="get" class="card" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <div>
      <label>Staff</label>
      <select name="user_id">
        <option value="">Everyone</option>
        @foreach($staff as $person)
          <option value="{{ $person->id }}" @selected((int) $userId === (int) $person->id)>{{ $person->full_name }}</option>
        @endforeach
      </select>
    </div>
    <button class="btn" type="submit">Filter</button>
  </form>
  <div class="card">
    <table>
      <thead><tr><th>When</th><th>Staff</th><th>Direction</th><th>Recorded by</th></tr></thead>
      <tbody>
      @forelse($punches as $punch)
        <tr>
          <td>{{ $punch->punched_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
          <td>{{ $punch->user?->full_name }}</td>
          <td>{{ $punch->direction }}</td>
          <td>{{ $punch->recorder?->full_name ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No punches recorded.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
