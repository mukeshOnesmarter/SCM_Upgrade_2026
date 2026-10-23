<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>@yield('title')</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/admin.css') }}" />
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
    @include('admin.partials.pageloader')
  @endif

  <div class="wrapper">
    <div class="adminTemplate" id="adminTemplate">
      @include('admin.partials.aside-left')
      <div class="asideRight">
        @include('admin.partials.header')
        <div class="adminLayout" id="adminLayout">
          @yield('template')
        </div>
        @include('admin.partials.footer')
      </div>
    </div>
  </div>

  @include('admin.partials.sessionMessages')
  @include('admin.partials.process')
  @include('admin.partials.script')
</body>

</html>
