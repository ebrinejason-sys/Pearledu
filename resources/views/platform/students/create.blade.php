@extends('layouts.app')
@section('title','Add student · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.students.index') }}">Students</a></p>
      <h2 class="page-header__title">Add student</h2>
    </div>
  </div>
  <div class="card">
    <form method="post" action="{{ route('platform.students.store') }}">
      @csrf
      @include('app.students._form')
      <p style="margin-top:16px">
        <button class="btn" type="submit">Create</button>
        <a class="btn ghost" href="{{ route('platform.students.index') }}">Cancel</a>
      </p>
    </form>
  </div>
@endsection
