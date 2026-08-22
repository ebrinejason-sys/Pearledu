@extends('layouts.app')
@section('title','My classes · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h1 class="page-header__title">My classes</h1>
      <p class="ws-mantra">Subjects you teach, by class. You cannot open another teacher’s subject in the same class.</p>
    </div>
    <div class="page-header__actions">
      <a class="btn ghost ws-cta" href="{{ route('app.attendance.index') }}">Take attendance</a>
    </div>
  </div>

  @if(empty($workspace))
    <div class="card">
      <p class="ws-hint">No current teaching assignments. Ask the Director of Studies to assign you a class and subject.</p>
    </div>
  @else
    @include('app.partials.workspace.teacher', ['workspace' => ['teacher' => $workspace], 'compact' => false, 'permissions' => $permissions ?? []])
  @endif
@endsection
