@extends('auth.layout.forgot-password')
@section('title', 'Password Reset Successful')
@section('template')
<div class="revertSuccess" id="revertSuccess">
  <h5>Password Reset Successful</h5>
  <p>Your password has been reset successfully. You can now log in with your new credentials. </p>
  <a href="{{ route($role . '.login') }}"> Go to Login</a>
  </div>
@endsection
