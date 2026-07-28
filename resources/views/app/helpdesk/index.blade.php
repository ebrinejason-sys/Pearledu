@extends('layouts.app')
@section('title','Helpdesk · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Support</p><h2 class="page-header__title">Helpdesk</h2>
    <p style="margin:8px 0 0;color:var(--muted);font-size:14px">
      @if($canManage)
        School-wide tickets. PearlEdu support staff also see these in the admin inbox.
      @else
        Your tickets only. PearlEdu support staff see them in the admin inbox.
      @endif
    </p>
  </div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    @if($canCreate)
    <div class="card">
      <h3 style="margin-top:0">New ticket</h3>
      <form method="post" action="{{ route('app.helpdesk.store') }}">@csrf
        <label>Subject</label><input name="subject" required>
        <label>Details</label><textarea name="body" rows="5" required></textarea>
        <p style="margin-top:14px"><button class="btn" type="submit">Submit</button></p>
      </form>
    </div>
    @endif
    <div class="card">
      <h3 style="margin-top:0">{{ $canManage ? 'All tickets' : 'My tickets' }}</h3>
      <table>
        <thead><tr><th>Subject</th>@if($canManage)<th>From</th>@endif<th>Status</th></tr></thead>
        <tbody>
        @forelse($tickets as $t)
          <tr>
            <td><strong>{{ $t->subject }}</strong><br><span style="color:var(--muted);font-size:13px">{{ \Illuminate\Support\Str::limit($t->body, 80) }}</span></td>
            @if($canManage)<td>{{ $t->user?->full_name }}</td>@endif
            <td>
              <span class="pill">{{ $t->status }}</span>
              @if($t->status !== 'closed')
                <form method="post" action="{{ route('app.helpdesk.close', $t) }}" style="display:inline">@csrf
                  <button class="btn ghost" type="submit">Close</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ $canManage ? 3 : 2 }}" style="color:var(--muted)">No tickets.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
