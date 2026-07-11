@extends('layouts.app')
@section('title', 'Edit student')
@section('content')
  <h2>Edit {{ $student->full_name }}</h2>
  <div class="card">
    <form method="post" action="{{ route('app.students.update', $student) }}">
      @csrf
      @method('PUT')
      @include('app.students._form')
      <p style="margin-top:16px">
        <button class="btn" type="submit">Save</button>
        <a class="btn ghost" href="{{ route('app.students.show', $student) }}">Cancel</a>
      </p>
    </form>
  </div>
@endsection
