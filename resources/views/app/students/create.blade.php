@extends('layouts.app')
@section('title', 'Add student')
@section('content')
  <h2>Add student</h2>
  <div class="card">
    <form method="post" action="{{ route('app.students.store') }}">
      @csrf
      @include('app.students._form')
      <p style="margin-top:16px">
        <button class="btn" type="submit">Create</button>
        <a class="btn ghost" href="{{ route('app.students.index') }}">Cancel</a>
      </p>
    </form>
  </div>
@endsection
