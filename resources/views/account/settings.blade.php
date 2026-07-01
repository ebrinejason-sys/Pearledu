@extends('layouts.app')
@section('title','Account')
@section('content')
  <h2>Account</h2>
  <div class="card">
    <h3>Profile</h3>
    <p>{{ auth()->user()->full_name }}<br><span style="color:var(--muted)">{{ auth()->user()->email ?? auth()->user()->phone }}</span></p>
  </div>
  <div class="card" style="border-color:#e6b4b0">
    <h3 style="color:#b3261e">Delete account</h3>
    <p style="color:var(--muted)">This permanently erases your personal data and removes your access everywhere. A child's school academic record is retained by the school but unlinked from your account. This cannot be undone.</p>
    <form method="post" action="{{ route('account.destroy') }}">
      @csrf @method('DELETE')
      <label>Password</label><input name="password" type="password" required style="max-width:320px">
      <label>Type DELETE to confirm</label><input name="confirm" required style="max-width:320px">
      @error('password')<div class="err">{{ $message }}</div>@enderror
      <p><button class="btn" type="submit" style="background:#b3261e">Permanently delete my account</button></p>
    </form>
  </div>
@endsection
