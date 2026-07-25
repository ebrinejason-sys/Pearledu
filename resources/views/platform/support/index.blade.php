@extends('layouts.app')
@section('title','Support tickets')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Support</p>
      <h2 class="page-header__title">Support inbox</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">
        Tickets opened by schools. Assign to PearlEdu support staff and resolve here.
      </p>
    </div>
    <div class="page-header__actions">
      @if(auth()->user()->hasPlatformPermission('platform.staff.manage'))
        <a class="btn ghost" href="{{ route('platform.operators.create') }}">Add support agent</a>
      @endif
    </div>
  </div>

  @if(session('status'))<div class="status">{{ session('status') }}</div>@endif

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
    @foreach([
      'open' => 'Open ('.$counts['open'].')',
      'unassigned' => 'Unassigned ('.$counts['unassigned'].')',
      'mine' => 'Assigned to me ('.$counts['mine'].')',
      'closed' => 'Closed ('.$counts['closed'].')',
      'all' => 'All',
    ] as $key => $label)
      <a class="btn {{ $filter === $key ? '' : 'ghost' }}" href="{{ route('platform.support.index', ['filter' => $key]) }}">{{ $label }}</a>
    @endforeach
  </div>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Subject</th>
          <th>School</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Assignee</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($tickets as $t)
        <tr>
          <td>{{ $t->id }}</td>
          <td>
            <strong>{{ $t->subject }}</strong><br>
            <span style="font-size:12px;color:var(--muted)">{{ $t->user?->full_name }} · {{ $t->created_at?->diffForHumans() }}</span>
          </td>
          <td>
            @if($t->school)
              <a href="{{ route('platform.schools.show', $t->school) }}">{{ $t->school->name }}</a>
            @else
              —
            @endif
          </td>
          <td><span class="pill">{{ $t->priority }}</span></td>
          <td><span class="pill">{{ $t->status }}</span></td>
          <td>{{ $t->assignee?->full_name ?: '—' }}</td>
          <td><a href="{{ route('platform.support.show', $t) }}">Open</a></td>
        </tr>
      @empty
        <tr><td colspan="7" style="color:var(--muted)">No tickets in this view.</td></tr>
      @endforelse
      </tbody>
    </table>
    <div style="margin-top:12px">{{ $tickets->links() }}</div>
  </div>
@endsection
