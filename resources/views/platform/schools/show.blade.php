@extends('layouts.app')
@section('title',$school->name)
@section('content')
  <a href="{{ route('platform.schools.index') }}">← Schools</a>
  <h2>{{ $school->name }}</h2>
  <div class="grid g2">
    <div class="card">
      <h3>Details</h3>
      <p><strong>Subdomain:</strong> <a href="{{ $school->subdomainUrl() }}">{{ $school->subdomainUrl() }}</a></p>
      <p><strong>EMIS:</strong> {{ $school->emis_number ?: '—' }}</p>
      <p><strong>District:</strong> {{ $school->district ?: '—' }}</p>
      <p><strong>Theme:</strong> <span class="pill">{{ $school->theme }}</span></p>
      <p><strong>Status:</strong> {{ $school->status }}</p>
    </div>
    <div class="card">
      <h3>Levels offered</h3>
      @foreach($school->offerings as $o)<span class="pill">{{ $o->level }}</span> @endforeach
    </div>
  </div>
@endsection
