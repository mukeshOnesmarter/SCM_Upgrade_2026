@extends('auth.layout.forgot-password')
@section('title', 'Password Reset Link Sent')
@section('template')
  <div class="revertSuccess" id="revertSuccess">
    <h5>Password Reset Link Sent</h5>
    <p> We’ve emailed a password reset link to <strong>{{ $email }}</strong>.
      <small> Please check your inbox (and spam folder just in case)</small> </p>
    <a href="{{ route($role . '.login') }}"> Return to Login </a>
  </div>
@endsection
