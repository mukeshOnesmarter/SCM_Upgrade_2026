<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Account Verified</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/registration.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/animate.css') }}" />
</head>

<body>
  <div class="registration">
    <div class="accountVerified">
      <h3><img src="{{ asset('images/alert-icon/success.svg') }}" alt=""> Account Verified</h3>
      <p>Your account has been successfully verified. You can now log in and access your course materials.</p>
      <a href="{{ route('student.login') }}">Click</a> here to login
    </div>
  </div>
</body>

</html>
