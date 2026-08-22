@extends('layouts.app')
@section('title', $conversation->subject ?: 'Conversation')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('app.staff.messages.index') }}">Messages</a></p>
      <h2 class="page-header__title">{{ $conversation->subject ?: 'Conversation' }}</h2>
      <p style="color:var(--muted);font-size:14px">{{ $conversation->participants->pluck('user.full_name')->filter()->implode(', ') }}</p>
    </div>
  </div>
  @if(session('status'))<div class="status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="card" style="margin-bottom:16px">
    @foreach($conversation->messages->sortBy('id') as $message)
      <p><strong>{{ $message->author?->full_name }}</strong> · <span style="color:var(--muted);font-size:12px">{{ $message->created_at->timezone(config('app.timezone'))->format('j M H:i') }}</span><br>{{ $message->body }}</p>
    @endforeach
  </div>
  <form method="post" action="{{ route('app.staff.messages.reply', $conversation) }}" class="card">
    @csrf
    <label>Reply</label>
    <textarea name="body" rows="3" required></textarea>
    @error('body')<div class="err">{{ $message }}</div>@enderror
    <p style="margin-top:14px"><button class="btn" type="submit">Send reply</button></p>
  </form>
@endsection
