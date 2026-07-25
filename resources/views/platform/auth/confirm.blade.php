@extends('layouts.app')

@section('title', 'Confirm password')

@section('content')
  <div class="page-header">
    <div>
      <p class="eyebrow">Security</p>
      <h1>Confirm your password</h1>
      <p class="lede">Sensitive PearlEdu actions require recent authentication.</p>
    </div>
  </div>

  @if (session('status'))
    <div class="flash" role="status">{{ session('status') }}</div>
  @endif

  <form method="post" action="{{ route('platform.auth.confirm.store') }}" class="card" style="max-width:420px;padding:24px;display:grid;gap:14px">
    @csrf
    <label>
      <span>Password</span>
      <input type="password" name="password" required autofocus autocomplete="current-password">
    </label>
    @error('password')
      <p class="field-error">{{ $message }}</p>
    @enderror
    <button type="submit" class="btn">Confirm</button>
  </form>
@endsection
