@extends('layouts.app')
@section('title', 'Continuing…')
@section('content')
  <div class="card" style="max-width:420px">
    <p style="margin:0 0 14px">Password confirmed. Continuing your action…</p>
    <p style="margin:0 0 14px;color:var(--muted);font-size:14px">If nothing happens, click Continue.</p>
    <form id="resume-sensitive" method="post" action="{{ $uri }}">
      @csrf
      @if(! in_array($method, ['POST', 'GET'], true))
        @method($method)
      @endif
      @foreach($fields as $field)
        <input type="hidden" name="{{ $field['name'] }}" value="{{ $field['value'] }}">
      @endforeach
      <p style="margin:0"><button class="btn" type="submit">Continue</button></p>
    </form>
  </div>
  <script>
    document.getElementById('resume-sensitive').submit();
  </script>
@endsection
