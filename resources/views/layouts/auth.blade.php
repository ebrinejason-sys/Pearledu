<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', config('app.name'))</title>
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
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
