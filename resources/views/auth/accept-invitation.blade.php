@extends('layouts.app')
@section('title','Set your password')
@section('content')
  <div class="card" style="max-width:440px;margin:40px auto">
    <h2 style="margin-top:0">Welcome — set your password</h2>
    <p style="color:var(--muted)">Choose a strong password (10+ characters) to activate your account.</p>
    <form method="post" action="/invitations/{{ $invitation }}/accept">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <label>New password</label><input name="password" type="password" required minlength="10">
      <label>Confirm password</label><input name="password_confirmation" type="password" required>
      @error('password')<div class="err">{{ $message }}</div>@enderror
      <p><button class="btn" type="submit" style="width:100%">Activate account</button></p>
    </form>
  </div>
@endsection
