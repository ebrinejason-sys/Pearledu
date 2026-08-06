@extends('layouts.app')
@section('title','Audit trail')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Security &amp; accountability</p>
      <h2 class="page-header__title">Audit trail</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px;max-width:72ch">
        Review who changed what, in which school, and from which IP address. Elevated imitation activity is recorded here.
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn ghost" href="{{ route('platform.system.index') }}">System overview</a>
    </div>
  </div>

  @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

  <div class="card" style="margin-bottom:16px">
    <form method="get" action="{{ route('platform.audit.index') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end">
      <div>
        <label for="audit-action">Action</label>
        <select id="audit-action" name="action">
          <option value="">All actions</option>
          @foreach($actions as $action)
            <option value="{{ $action }}" @selected(($data['action'] ?? '') === $action)>{{ $action }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="audit-school">School</label>
        <select id="audit-school" name="school_id">
          <option value="">All schools</option>
          @foreach($schools as $school)
            <option value="{{ $school->id }}" @selected((int)($data['school_id'] ?? 0) === (int)$school->id)>{{ $school->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="audit-actor">Actor</label>
        <select id="audit-actor" name="actor_id">
          <option value="">All actors</option>
          @foreach($actors as $actor)
            <option value="{{ $actor->id }}" @selected((int)($data['actor_id'] ?? 0) === (int)$actor->id)>{{ $actor->full_name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="audit-from">From</label>
        <input id="audit-from" type="date" name="from" value="{{ $data['from'] ?? '' }}">
      </div>
      <div>
        <label for="audit-to">To</label>
        <input id="audit-to" type="date" name="to" value="{{ $data['to'] ?? '' }}">
      </div>
      <div style="display:flex;gap:8px">
        <button class="btn" type="submit">Filter</button>
        <a class="btn ghost" href="{{ route('platform.audit.index') }}">Clear</a>
      </div>
    </form>
  </div>

  <div class="card">
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>Time</th><th>Action</th><th>Actor</th><th>School</th><th>Target</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
          <tr>
            <td style="white-space:nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
            <td><strong>{{ $log->action }}</strong></td>
            <td>{{ $log->actor?->full_name ?: 'System / deleted user' }}</td>
            <td>{{ $log->school?->name ?: 'Platform' }}</td>
            <td>
              @if($log->entity_type)
                {{ $log->entity_type }}@if($log->entity_id) #{{ $log->entity_id }}@endif
              @else
                &mdash;
              @endif
            </td>
            <td style="max-width:340px">
              @if(!empty($log->metadata))
                <details>
                  <summary>View metadata</summary>
                  <pre style="white-space:pre-wrap;word-break:break-word;font-size:11px;margin:8px 0 0">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
              @else
                &mdash;
              @endif
            </td>
            <td style="white-space:nowrap">{{ $log->ip_address ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" style="color:var(--muted)">No audit records match these filters.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div style="margin-top:16px">{{ $logs->links() }}</div>
  </div>
@endsection
