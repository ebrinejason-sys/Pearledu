@extends('layouts.app')
@section('title','HR · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Operations</p><h2 class="page-header__title">HR · leave</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Request leave</h3>
      @if(!empty($canManageHr))
      <form method="post" action="{{ route('app.hr.leave.store') }}">@csrf
        <label>Starts</label><input type="date" name="starts_on" required>
        <label>Ends</label><input type="date" name="ends_on" required>
        <label>Reason</label><input name="reason">
        <p style="margin-top:14px"><button class="btn" type="submit">Submit</button></p>
      </form>
      @else
        <p style="color:var(--muted);margin:0">Read-only HR view.</p>
      @endif
    </div>
    <div class="card">
      <h3 style="margin-top:0">Requests</h3>
      <table>
        <thead><tr><th>Staff</th><th>Dates</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($leaves as $leave)
          <tr>
            <td>{{ $leave->user?->full_name }}</td>
            <td>{{ $leave->starts_on?->format('Y-m-d') }} → {{ $leave->ends_on?->format('Y-m-d') }}</td>
            <td>
              <span class="pill">{{ $leave->status }}</span>
              @if(!empty($canManageHr) && $leave->status === 'pending')
                <form method="post" action="{{ route('app.hr.leave.decide', $leave) }}" style="display:inline">@csrf
                  <input type="hidden" name="decision" value="approved">
                  <button class="btn ghost" type="submit">Approve</button>
                </form>
                <form method="post" action="{{ route('app.hr.leave.decide', $leave) }}" style="display:inline">@csrf
                  <input type="hidden" name="decision" value="rejected">
                  <button class="btn ghost" type="submit">Reject</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">None.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
