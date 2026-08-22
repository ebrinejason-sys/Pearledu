@extends('layouts.app')
@section('title','My Class · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Homeroom</p>
      <h1 class="page-header__title">{{ $homeroom['class_name'] ?? 'My Class' }}</h1>
      <p class="ws-mantra">Pastoral overview. Mark entry still requires a teaching assignment.</p>
    </div>
    @if(!empty($homeroom))
    <div class="page-header__actions">
      <a class="btn accent ws-cta" href="{{ route('app.attendance.index', ['class_id' => $homeroom['class_id']]) }}">Take register</a>
      <a class="btn ghost ws-cta" href="{{ route('app.students.index', ['class_id' => $homeroom['class_id']]) }}">Class roster</a>
    </div>
    @endif
  </div>

  @if(empty($homeroom))
    <div class="card"><p class="ws-hint">No homeroom class is linked to your Class Teacher role. Ask an administrator to set it under Staff.</p></div>
  @else
    @include('app.partials.workspace.homeroom', ['workspace' => ['homeroom' => $homeroom], 'compact' => false, 'permissions' => $permissions ?? []])
  @endif
@endsection
