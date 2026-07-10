@extends('layouts.app')
@section('title',$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.schools.index') }}">Schools</a></p>
      <h2 class="page-header__title">{{ $school->name }}</h2>
    </div>
    <div class="page-header__actions">
      <form method="post" action="{{ route('platform.schools.enter', $school) }}">
        @csrf
        <button type="submit" class="btn ghost">Enter school scope</button>
      </form>
    </div>
  </div>

  <div class="grid g2">
    <div class="card">
      <h3>Details</h3>
      <p><strong>Subdomain:</strong> <a href="{{ $school->subdomainUrl() }}">{{ $school->subdomainUrl() }}</a></p>
      <p><strong>EMIS:</strong> {{ $school->emis_number ?: '—' }}</p>
      <p><strong>District:</strong> {{ $school->district ?: '—' }}</p>
      <p><strong>Theme:</strong> <span class="pill">{{ $school->theme }}</span></p>
      <p><strong>Status:</strong> {{ $school->status }}</p>
      @php($provisioning = $school->provisioningState())
      <p>
        <strong>Provisioning:</strong>
        <span class="pill @if($provisioning !== 'ready') pill--muted @endif">
          {{ ['pending_invite' => 'Pending invite', 'invite_accepted' => 'Invite accepted', 'ready' => 'Ready'][$provisioning] }}
        </span>
        @if($school->activated_at)
          <span style="color:var(--muted);font-size:13px"> — Verified live since {{ $school->activated_at->diffForHumans() }}</span>
        @endif
      </p>
    </div>
    <div class="card">
      <h3>Levels offered</h3>
      @foreach($school->offerings as $o)<span class="pill">{{ $o->level }}</span> @endforeach
    </div>
  </div>

  <div class="card">
    <h3>Staff &amp; accounts — imitate</h3>
    <p style="color:var(--muted);font-size:14px;margin-top:0">
      View the school app exactly as a user sees it. Every imitation is audited.
    </p>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Roles</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($members as $member)
        @php $user = $member['user']; @endphp
        <tr>
          <td><strong>{{ $user->full_name }}</strong></td>
          <td>{{ $user->email ?? $user->phone ?? '—' }}</td>
          <td>
            @foreach($member['roles'] as $role)
              <span class="pill">{{ $role }}</span>
            @endforeach
          </td>
          <td>{{ $user->status }}</td>
          <td>
            @if($user->status === 'active' && ! $user->is_platform)
              <form method="post" action="{{ route('platform.schools.imitate', [$school, $user]) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn ghost">Imitate</button>
              </form>
            @else
              <span style="color:var(--muted);font-size:13px">—</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No active accounts at this school yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
