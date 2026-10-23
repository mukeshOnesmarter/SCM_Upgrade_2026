<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <title>@yield('title')</title>
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/student.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/jquery-ui.css') }}" />
</head>

<body>
  @if (
      !Session::has('message') &&
          !Session::has('success') &&
          !Session::has('error') &&
          !Session::has('info') &&
          !Session::has('warning') &&
          !$errors->any())
    @include('student.partials.pageloader')
  @endif

  <div class="wrapper">
    <div class="adminTemplate">
      @include('student.partials.aside-left')
      <div class="asideRight">
        @include('student.partials.header')
        <div class="adminLayout" id="adminLayout">
          @yield('template')
        </div>
        @include('student.partials.footer')
      </div>
    </div>
  </div>
  @include('student.partials.sessionMessages')
  @include('student.partials.process')
  @include('student.partials.script')
</body>

</html>
