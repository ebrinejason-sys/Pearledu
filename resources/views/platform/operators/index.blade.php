@extends('layouts.app')
@section('title','PearlEdu staff')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">People</p>
      <h2 class="page-header__title">PearlEdu staff</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px;max-width:70ch">
        Platform admins, EMIS data entrants, operations, and support agents. These accounts use
        <code>/admin</code> — not school tenant logins.
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn accent" href="{{ route('platform.operators.create') }}">Add staff</a>
    </div>
  </div>

  @if(session('status'))<div class="status">{{ session('status') }}</div>@endif

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($operators as $op)
        <tr>
          <td><strong>{{ $op->full_name }}</strong></td>
          <td>{{ $op->email }}</td>
          <td>
            <form method="post" action="{{ route('platform.operators.update', $op) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
              @csrf
              @method('PUT')
              <select name="role_key" style="min-width:160px">
                @foreach($roles as $key => $label)
                  <option value="{{ $key }}" @selected(($op->platform_role ?? 'platform_ops') === $key)>{{ $label }}</option>
                @endforeach
              </select>
              <select name="status">
                <option value="active" @selected($op->status === 'active')>active</option>
                <option value="disabled" @selected($op->status === 'disabled')>disabled</option>
              </select>
              <button class="btn ghost" type="submit">Save</button>
            </form>
          </td>
          <td><span class="pill">{{ $op->status }}</span></td>
          <td>
            @if($op->id !== auth()->id())
              <form method="post" action="{{ route('platform.operators.reset-password', $op) }}" onsubmit="return confirm('Reset password for {{ $op->full_name }}?')">
                @csrf
                <button class="btn ghost" type="submit">Reset password</button>
              </form>
            @else
              <span style="color:var(--muted);font-size:13px">You</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No PearlEdu staff yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
