<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>@yield('title')</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/jquery-ui.css') }}" />
</head>

<body>
  <div class="wrapper">
    Courses
    @yield('template')
  </div>
  @include('admin.partials.script')
</body>

</html>
