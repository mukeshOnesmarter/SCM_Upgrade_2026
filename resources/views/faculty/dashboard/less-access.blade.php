@extends('faculty.layout')
@section('title', 'Admin Dashboard')
@section('template')
  <div class="dashboard">
    <h3>Dashboard</h3>
    <p>Welcome, <span class="text-primary">{{ $sharedActiveUserData['first_name']}}!</span> Currently, there are no active courses or simulations in your account.  Please check back soon or
      reach out for assistance.</p>
    <p>Call us at: +1 610-244-8065 or write to us at: support@flexee.com</p>
  </div>
@endsection
