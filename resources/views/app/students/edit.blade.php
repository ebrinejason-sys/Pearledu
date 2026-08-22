@extends('layouts.app')
@section('title', 'Edit student')
@section('content')
  <h2>Edit {{ $student->full_name }}</h2>
  <div class="card">
    <form method="post" action="{{ route('app.students.update', $student) }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      @include('app.students._form')
      @if(!empty($canApplyFees))
        @include('app.students._fee_attach', [
          'layout' => 'form',
          'student' => $student,
          'canApplyFees' => true,
          'applyableStructures' => $applyableStructures ?? collect(),
          'invoicedStructureIds' => $invoicedStructureIds ?? [],
        ])
      @elseif(empty($profileOnly))
        <p style="color:var(--muted);font-size:13px;margin-top:14px">Class and day/boarding bill the matching fee types automatically when you save. The bursar attaches extras (van, PTA) on the Fees tab.</p>
      @endif
      <p style="margin-top:16px">
        <button class="btn" type="submit">Save</button>
        <a class="btn ghost" href="{{ route('app.students.show', $student) }}">Cancel</a>
      </p>
    </form>
  </div>
@endsection
