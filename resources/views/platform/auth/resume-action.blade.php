@extends('layouts.app')
@section('title', 'Continuing…')
@section('content')
  <div class="card" style="max-width:420px">
    <p style="margin:0">Password confirmed. Continuing your action…</p>
    <form id="resume-sensitive" method="post" action="{{ $uri }}">
      @csrf
      @if(! in_array($method, ['POST', 'GET'], true))
        @method($method)
      @endif
      @foreach($input as $key => $value)
        @if(is_array($value))
          @foreach($value as $item)
            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
          @endforeach
        @else
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
      @endforeach
      <noscript>
        <p style="margin-top:14px"><button class="btn" type="submit">Continue</button></p>
      </noscript>
    </form>
  </div>
@endsection
@section('head')
<script>
  document.getElementById('resume-sensitive')?.submit();
</script>
@endsection
