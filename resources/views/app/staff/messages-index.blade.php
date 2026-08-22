@extends('layouts.app')
@section('title', 'Staff messages')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h2 class="page-header__title">Staff messages</h2>
    </div>
  </div>
  @if(session('status'))<div class="status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">New message</h3>
      <form method="post" action="{{ route('app.staff.messages.store') }}">
        @csrf
        <label>To</label>
        <select name="user_ids[]" multiple size="8" required>
          @foreach($directory as $person)
            <option value="{{ $person->id }}" @selected(collect(old('user_ids'))->contains($person->id))>{{ $person->full_name }}</option>
          @endforeach
        </select>
        @error('user_ids')<div class="err">{{ $message }}</div>@enderror
        <label>Subject</label>
        <input name="subject" value="{{ old('subject') }}">
        <label>Message</label>
        <textarea name="body" rows="4" required>{{ old('body') }}</textarea>
        @error('body')<div class="err">{{ $message }}</div>@enderror
        <p style="margin-top:14px"><button class="btn" type="submit">Send</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Inbox</h3>
      @forelse($conversations as $conversation)
        <p><a href="{{ route('app.staff.messages.show', $conversation) }}"><strong>{{ $conversation->subject ?: 'Conversation' }}</strong></a><br>
          <span style="color:var(--muted);font-size:13px">{{ $conversation->participants->pluck('user.full_name')->filter()->implode(', ') }}</span></p>
      @empty
        <p style="color:var(--muted)">No conversations yet.</p>
      @endforelse
    </div>
  </div>
@endsection
