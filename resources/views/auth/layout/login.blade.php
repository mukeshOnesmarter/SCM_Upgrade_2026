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
  <div class="authentication">
    <div class="logo"> 
      <a href="/"><img src="{{ URL::asset('images/logo-1x.svg') }}" alt=""></a>
    </div>
    <div class="waitingContainer" id="waitingContainer">
      <div class="contentWrapper">Please wait while we process your request...</div>
    </div>
    <div class="alertMessage"></div>
    <div class="authContainer">
      @yield('template')
    </div>
  </div>
  {{-- LOGIN SUCCESSFULLY --}}
  <div class="loginSuccessfully">
    <div class="alertContainer">
      <div class="success">
        <img src="" alt="">
        <span>Login Successfully <small>Please Wait...</small></span>
      </div>
    </div>
  </div>
  <script type="text/javascript" src="{{ URL::asset('js/jquery-3.2.1.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/jquery-ui.js') }}"></script>
  @yield('templateScript')
</body>

</html>
