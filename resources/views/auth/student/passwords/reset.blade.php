@extends('auth.layout.forgot-password')
@section('title', 'Reset Password')
@section('template')

  <form method="POST" action="{{ route('password.update', 'student') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="email" name="email" value="{{ request('email') }}" required>
    <input type="password" name="password" placeholder="New password" required>
    <input type="password" name="password_confirmation" placeholder="Confirm password" required>
    <button type="submit">Reset Password</button>
  </form>
@endsection
