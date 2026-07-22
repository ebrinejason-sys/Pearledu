<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', config('app.name'))</title>
@include('layouts.partials.favicons')
@if(!empty($themeFontUrl))
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ $themeFontUrl }}" rel="stylesheet">
@endif
<style>
  {!! $themeCss !!}
  *{box-sizing:border-box} html,body{margin:0;height:100%}
  body{font-family:var(--font);-webkit-font-smoothing:antialiased}
  :focus-visible{outline:3px solid var(--focus);outline-offset:2px}
</style>
@yield('head')
</head>
<body>
  @yield('content')
</body>
</html>
