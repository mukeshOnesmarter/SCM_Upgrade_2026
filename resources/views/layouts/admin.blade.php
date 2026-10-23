<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>@yield('title')</title>
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/app.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/jquery-ui.css') }}" />
</head>

<body>
  {{-- pageLoader --}}
  <div class="ajaxLoader" id="pageLoader">
    <div class="sk-chase">
      <div class="sk-chase-dot"></div>
      <div class="sk-chase-dot"></div>
      <div class="sk-chase-dot"></div>
      <div class="sk-chase-dot"></div>
      <div class="sk-chase-dot"></div>
      <div class="sk-chase-dot"></div>
    </div>
  </div>
  <div class="wrapper">
    @include('layout.component.header')
    <div class="adminLayout" id="adminLayout">
      @yield('template')
    </div>
    @include('layout.component.footer')
  </div>


  <div class="process" id="Process">
    <div class="processContainer">
      <div class="cv-spinner">
        <span class="spinner"></span>
      </div>
      <p>Please wait while we are processing your request. <br> don't refresh or leave this page.</p>
    </div>
  </div>
  @include('layout.component.script')
</body>

</html>
