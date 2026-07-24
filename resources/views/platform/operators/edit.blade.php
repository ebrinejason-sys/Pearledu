@extends('layouts.app')
@section('title','Edit PearlEdu staff')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.operators.index') }}">PearlEdu staff</a></p>
      <h2 class="page-header__title">Edit {{ $operator->full_name }}</h2>
    </div>
  </div>

  <div class="card" style="max-width:640px">
    <form method="post" action="{{ route('platform.operators.update', $operator) }}">
      @csrf
      @method('PUT')
      <label>Full name</label>
      <input name="full_name" value="{{ old('full_name', $operator->full_name) }}" required>
      @error('full_name')<div class="err">{{ $message }}</div>@enderror

      <label>Email</label>
      <input name="email" type="email" value="{{ old('email', $operator->email) }}" required>
      @error('email')<div class="err">{{ $message }}</div>@enderror

      <label>Phone (optional)</label>
      <input name="phone" value="{{ old('phone', $operator->phone) }}">

      <label>Role</label>
      <select name="role_key" required>
        @foreach($roles as $key => $label)
          <option value="{{ $key }}" @selected(old('role_key', $roleKey) === $key)>{{ $label }}</option>
        @endforeach
      </select>

      <label>Status</label>
      <select name="status" required>
        <option value="active" @selected(old('status', $operator->status) === 'active')>active</option>
        <option value="disabled" @selected(old('status', $operator->status) === 'disabled')>disabled</option>
      </select>

      @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
      <p style="margin-top:16px">
        <button class="btn" type="submit">Save changes</button>
        <a class="btn ghost" href="{{ route('platform.operators.index') }}">Cancel</a>
      </p>
    </form>
  </div>
@endsection
