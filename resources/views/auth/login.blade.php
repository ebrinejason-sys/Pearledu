@extends('layouts.app')
@section('title','Sign in')
@section('content')
  <div class="card" style="max-width:420px;margin:40px auto">
    <h2 style="margin-top:0">Sign in</h2>
    <form method="post" action="/login">
      @csrf
      <label>Email</label><input name="email" type="email" value="{{ old('email') }}" required>
      <label>Password</label><input name="password" type="password" required>
      <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="remember" style="width:auto"> Remember me</label>
      @error('email')<div class="err">{{ $message }}</div>@enderror
      <p><button class="btn" type="submit" style="width:100%">Sign in</button></p>
    </form>
  </div>
@endsection
