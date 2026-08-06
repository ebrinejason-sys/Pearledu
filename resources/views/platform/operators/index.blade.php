@extends('layouts.app')
@section('title','PearlEdu staff')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">People</p>
      <h2 class="page-header__title">PearlEdu staff</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px;max-width:72ch">
        You are <strong>{{ $roles[$actorRole] ?? ($actorRole ?: 'unknown') }}</strong>.
        Platform Admins can recover or manage any other staff account; other roles can manage only staff below them.
        Staff accounts use <code>/admin</code> — not school tenant logins.
      </p>
    </div>
    <div class="page-header__actions">
      @if(count($assignableRoles))
        <a class="btn accent" href="{{ route('platform.operators.create') }}">Add staff</a>
      @endif
    </div>
  </div>

  @if($misconfigured->isNotEmpty())
    <div class="err" role="alert" style="margin-bottom:16px">
      <strong>Misconfigured accounts:</strong>
      {{ $misconfigured->pluck('email')->filter()->implode(', ') }}
      — platform flag is set but no platform role is assigned. Assign a role before they can use /admin.
    </div>
  @endif

  @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
  @error('delete')<div class="err">{{ $message }}</div>@enderror
  @error('password')<div class="err">{{ $message }}</div>@enderror
  @error('security')<div class="err">{{ $message }}</div>@enderror

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($operators as $op)
        @php
          $isYou = (int) $op->id === (int) $actor->id;
          $canManage = ! $isYou && $staff->canManage($actor, $op);
        @endphp
        <tr>
          <td>
            <strong>{{ $op->full_name }}</strong>
            @if($isYou)<span style="color:var(--muted);font-size:12px"> · you</span>@endif
          </td>
          <td>{{ $op->email }}</td>
          <td><span class="pill">{{ $op->platform_role ? ($roles[$op->platform_role] ?? $op->platform_role) : 'Misconfigured' }}</span></td>
          <td><span class="pill">{{ $op->status }}</span></td>
          <td style="white-space:nowrap">
            @if($canManage)
              <a href="{{ route('platform.operators.edit', $op) }}">Edit</a>
              ·
              <form method="post" action="{{ route('platform.operators.reset-password', $op) }}" style="display:inline" class="js-confirm-reset" data-label="{{ e($op->full_name) }}">
                @csrf
                <button type="submit" class="btn-link-action">Reset password</button>
              </form>
              ·
              <form method="post" action="{{ route('platform.operators.force-logout', $op) }}" style="display:inline" onsubmit="return confirm('End every active session for this staff account?')">
                @csrf
                <button type="submit" class="btn-link-action">Force logout</button>
              </form>
              @if($op->hasTwoFactorEnabled())
                <form method="post" action="{{ route('platform.operators.reset-two-factor', $op) }}" style="display:inline" onsubmit="return confirm('Reset two-factor authentication? This staff member must enroll again.')">
                  @csrf
                  <button type="submit" class="btn-link-action btn-link-danger">Reset 2FA</button>
                </form>
              @endif
              <form method="post" action="{{ route('platform.operators.destroy', $op) }}" style="display:inline" class="js-confirm-delete" data-label="{{ e($op->full_name) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-link-action btn-link-danger">Delete</button>
              </form>
            @elseif($isYou)
              <span style="color:var(--muted);font-size:13px">Your account</span>
            @else
              <span style="color:var(--muted);font-size:13px">No access</span>
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

@section('head')
<style>
  .btn-link-action{background:none;border:0;padding:0;color:var(--brand);font:inherit;font-weight:600;cursor:pointer}
  .btn-link-danger{color:var(--danger,#b42318)}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form.js-confirm-reset').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var label = form.getAttribute('data-label') || 'this staff member';
      if (!window.confirm('Reset password for ' + label + '? A temporary password will be emailed.')) {
        e.preventDefault();
      }
    });
  });
  document.querySelectorAll('form.js-confirm-delete').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var label = form.getAttribute('data-label') || 'this staff member';
      if (!window.confirm('Delete PearlEdu staff account for ' + label + '? They will lose /admin access.')) {
        e.preventDefault();
      }
    });
  });
});
</script>
@endsection
