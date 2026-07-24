@extends('layouts.app')
@section('title','Schools')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Organisation</p>
      <h2 class="page-header__title">Schools</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">
        <strong>Edit</strong> school details, <strong>Delete</strong> a tenant (cascades its database rows), or
        <strong>Enter workspace</strong> for data entry. School users sign in at <code>/login</code>.
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn accent" href="{{ route('platform.schools.create') }}">Onboard school</a>
    </div>
  </div>

  @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
  @error('confirm_name')<div class="err">{{ $message }}</div>@enderror

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>School</th>
          <th>Tenant ID</th>
          <th>District</th>
          <th>Learners</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($schools as $s)
        <tr>
          <td><strong>{{ $s->name }}</strong></td>
          <td><code>{{ $s->tenantId() }}</code></td>
          <td>{{ $s->district ?: '—' }}</td>
          <td>{{ number_format($s->students_count) }}</td>
          <td><span class="pill">{{ $s->status }}</span></td>
          <td style="white-space:nowrap">
            <a href="{{ route('platform.schools.show', $s) }}">Edit</a>
            ·
            <form method="post" action="{{ route('platform.schools.enter', $s) }}" style="display:inline">
              @csrf
              <button type="submit" class="btn-link-action">Enter workspace</button>
            </form>
            ·
            <form
              method="post"
              action="{{ route('platform.schools.destroy', $s) }}"
              style="display:inline"
              data-school-name="{{ $s->name }}"
              onsubmit="return confirmSchoolDelete(this)"
            >
              @csrf
              @method('DELETE')
              <input type="hidden" name="confirm_name" value="">
              <button type="submit" class="btn-link-action btn-link-danger">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="color:var(--muted)">
            No schools yet.
            <a href="{{ route('platform.schools.create') }}">Onboard the first school</a>.
          </td>
        </tr>
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
function confirmSchoolDelete(form) {
  var name = form.getAttribute('data-school-name') || '';
  if (!window.confirm('Delete “‘ + name + '” and all of its tenant database data? This cannot be undone.')) {
    return false;
  }
  var typed = window.prompt('Type the school name exactly to confirm:\n' + name);
  if (typed !== name) {
    window.alert('Name did not match. School was not deleted.');
    return false;
  }
  form.querySelector('input[name="confirm_name"]').value = typed;
  return true;
}
</script>
@endsection
