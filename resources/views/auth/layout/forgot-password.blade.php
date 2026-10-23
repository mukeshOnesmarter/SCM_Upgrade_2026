<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>@yield('title')</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/auth.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/animate.css') }}" />
</head>

<body>
  @if ($errors->any())
    <div class="alert alert-danger">
      {{ $errors->first() }}
    </div>
  @endif
  <div class="forgetPassword">
    @yield('template')
  </div>
  <script type="text/javascript" src="{{ URL::asset('js/jquery-3.2.1.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/jquery-ui.js') }}"></script>
  @yield('templateScript')
</body>

</html>
