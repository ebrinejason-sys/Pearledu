<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', config('app.name'))</title>
@include('layouts.partials.favicons')
<style>
  {!! $themeCss !!}
  *{box-sizing:border-box} html,body{margin:0;height:100%}
  body{font-family:var(--font,'Inter',sans-serif)}
</style>
@yield('head')
</head>
<body>
  @yield('content')
</body>
</html>
