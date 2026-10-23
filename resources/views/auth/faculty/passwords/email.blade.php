@extends('auth.layout.forgot-password')
@section('title', 'Forgot Password')
@section('template')
  <div class="authContainer">
    <div class="loginForm" id="loginForm">
      <h5>Forgot Password</h5>
      <p>Enter the email address associated with your account.
        <small>Please check your inbox (and spam folder just in case).</small>
      </p>
      <form method="POST" action="{{ route('password.email', 'faculty') }}">
        @csrf
        <div class="inputComponent">
          <label for="title">Registered Email Address *</label>
          <div class="inputField">
            <input type="email" name="email" id="email" placeholder="Enter your registered email">
            <span class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></span>
          </div>
          @error('email')
            <small class="error_text">{{ $message }}</small>
          @enderror
        </div>
        <div class="inputComponent action_button">
          <button type="submit">Send Reset Link</button>
          <a href="{{ route('faculty.login') }}">Back to Login</a>
        </div>
      </form>
    </div>
  </div>
@endsection
