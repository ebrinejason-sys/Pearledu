@extends('layouts.app')
@section('title','Edit · '.$student->full_name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.students.show', $student) }}">{{ $student->full_name }}</a></p>
      <h2 class="page-header__title">Edit student</h2>
    </div>
  </div>
  <div class="card">
    <form method="post" action="{{ route('platform.students.update', $student) }}">
      @csrf
      @method('PUT')
      @include('app.students._form')
      <p style="margin-top:16px">
        <button class="btn" type="submit">Save</button>
        <a class="btn ghost" href="{{ route('platform.students.show', $student) }}">Cancel</a>
      </p>
    </form>
  </div>
@endsection
