@extends('layouts.app')
@section('title','Ticket #'.$ticket->id)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.support.index') }}">Support</a></p>
      <h2 class="page-header__title">{{ $ticket->subject }}</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">
        From {{ $ticket->user?->full_name ?: 'Unknown' }}
        @if($ticket->school)
          · <a href="{{ route('platform.schools.show', $ticket->school) }}">{{ $ticket->school->name }}</a>
          (tenant #{{ $ticket->school->tenantId() }})
        @endif
      </p>
    </div>
  </div>

  @if(session('status'))<div class="status">{{ session('status') }}</div>@endif

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Message</h3>
      <p style="white-space:pre-wrap;line-height:1.55">{{ $ticket->body }}</p>
      <p style="margin:16px 0 0;font-size:12px;color:var(--muted)">Opened {{ $ticket->created_at }}</p>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Handle ticket</h3>
      <form method="post" action="{{ route('platform.support.update', $ticket) }}">
        @csrf
        @method('PUT')
        <label>Status</label>
        <select name="status" required>
          @foreach(['open','in_progress','closed'] as $s)
            <option value="{{ $s }}" @selected(old('status', $ticket->status) === $s)>{{ $s }}</option>
          @endforeach
        </select>
        <label>Priority</label>
        <select name="priority" required>
          @foreach(['low','normal','high','urgent'] as $p)
            <option value="{{ $p }}" @selected(old('priority', $ticket->priority ?: 'normal') === $p)>{{ $p }}</option>
          @endforeach
        </select>
        <label>Category</label>
        <input name="category" value="{{ old('category', $ticket->category) }}" placeholder="billing, access, data, other">
        <label>Assign to</label>
        <select name="assigned_to">
          <option value="">— Unassigned —</option>
          @foreach($agents as $agent)
            <option value="{{ $agent->id }}" @selected((int) old('assigned_to', $ticket->assigned_to) === (int) $agent->id)>
              {{ $agent->full_name }} ({{ $agent->email }})
            </option>
          @endforeach
        </select>
        <label>Internal notes</label>
        <textarea name="admin_notes" rows="5">{{ old('admin_notes', $ticket->admin_notes) }}</textarea>
        @error('assigned_to')<div class="err">{{ $message }}</div>@enderror
        <p style="margin-top:14px"><button class="btn" type="submit">Save</button></p>
      </form>
    </div>
  </div>
@endsection
