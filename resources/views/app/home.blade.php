@extends('layouts.app')
@section('title', $school?->name ?? 'Home')
@section('content')
  <h2>{{ $school?->name ?? 'Welcome' }}</h2>
  @if($school)
    <div class="grid g2">
      <div class="card">
        <h3>Quick actions</h3>
        @if(in_array('learners.manage', $permissions))<a class="btn" href="{{ route('app.students.index') }}">Students</a>@endif
        @if(in_array('sms.send', $permissions))<a class="btn" href="{{ route('app.sms') }}">Send SMS</a>@endif
      </div>
      <div class="card">
        <h3>Your access</h3>
        @foreach($permissions as $p)<span class="pill">{{ $p }}</span> @endforeach
      </div>
    </div>
  @else
    <div class="card"><p>No active school context for this account.</p></div>
  @endif
@endsection
