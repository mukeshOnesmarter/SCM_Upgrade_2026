@extends('emails.layout')
@section('title', 'FLEXEE Registration Confirmation')
@section('emailBody')
  <h4>Dear {{ $studentName }}!</h4>
  <p>Thank you for registering with FLEXEE, the global supply chain management simulation.</p>
  <p>Your account has been successfully created. To complete your registration and begin participating in simulation
    rounds, please validate your account by clicking the link below:</p>
  <p><a href="{{ $accountValidateUrl }}" target="_blank">Click here to Validate Account</a></p>

  <p><strong>Login Details:</strong></p>
  <ul>
    <li>Email: {{ $userEmail }}</li>
    <li>Password: {{ $userPassword }}</li>
  </ul>
  <p>For your security, please change your password after your first login.</p>
  <p>If you did not register for a FLEXEE account, please ignore this email or contact our support team at <a
      href="mailto:support@flexee.org">support@flexee.org</a>.</p>
@endsection
