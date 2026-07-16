@extends('layouts.app')
@section('title','Invitations')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Operations</p>
      <h2 class="page-header__title">Invitations</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">School admin and staff activation links across all tenants.</p>
    </div>
  </div>

  @error('invitation')<div class="err" style="margin-bottom:12px">{{ $message }}</div>@enderror

  <div class="card" style="margin-bottom:16px">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      @foreach(['open' => 'Open', 'expired' => 'Expired', 'accepted' => 'Accepted'] as $key => $label)
        <a class="btn {{ $filter === $key ? '' : 'ghost' }}" href="{{ route('platform.invitations.index', ['filter' => $key]) }}">{{ $label }}</a>
      @endforeach
    </div>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>Invitee</th>
          <th>School</th>
          <th>Role</th>
          <th>Expires</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($invitations as $invite)
        <tr>
          <td>
            <strong>{{ $invite->user?->full_name ?? '—' }}</strong><br>
            <span style="color:var(--muted);font-size:12px">{{ $invite->email ?: $invite->phone ?: '—' }}</span>
          </td>
          <td>
            @if($invite->school)
              <a href="{{ route('platform.schools.show', $invite->school) }}">{{ $invite->school->name }}</a>
            @else
              —
            @endif
          </td>
          <td><span class="pill">{{ $invite->role_key }}</span></td>
          <td>
            @if($invite->accepted_at)
              Accepted {{ $invite->accepted_at->diffForHumans() }}
            @elseif($invite->isExpired())
              <span class="pill pill--muted">Expired</span>
            @else
              {{ $invite->expires_at->diffForHumans() }}
            @endif
          </td>
          <td>
            @if(! $invite->accepted_at)
              <form method="post" action="{{ route('platform.invitations.resend', $invite) }}">
                @csrf
                <button class="btn ghost" type="submit">Resend</button>
              </form>
            @else
              —
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No invitations in this filter.</td></tr>
      @endforelse
      </tbody>
    </table>
    <div style="margin-top:16px">{{ $invitations->links() }}</div>
  </div>
@endsection
