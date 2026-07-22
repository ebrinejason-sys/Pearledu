@extends('layouts.app')
@section('title','Helpdesk · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Support</p><h2 class="page-header__title">Helpdesk</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">New ticket</h3>
      <form method="post" action="{{ route('app.helpdesk.store') }}">@csrf
        <label>Subject</label><input name="subject" required>
        <label>Details</label><textarea name="body" rows="5" required></textarea>
        <p style="margin-top:14px"><button class="btn" type="submit">Submit</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Tickets</h3>
      <table>
        <thead><tr><th>Subject</th><th>From</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($tickets as $t)
          <tr>
            <td><strong>{{ $t->subject }}</strong><br><span style="color:var(--muted);font-size:13px">{{ Str::limit($t->body, 80) }}</span></td>
            <td>{{ $t->user?->full_name }}</td>
            <td><span class="pill">{{ $t->status }}</span></td>
          </tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">No tickets.</td></tr>
        @endforelse
        </tbody>
      </table>
      {{ $tickets->links() }}
    </div>
  </div>
@endsection
