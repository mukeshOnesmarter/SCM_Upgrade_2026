@extends('student.layout')
@section('title', 'Simulation')
@section('template')
  <div class="invitationContainer">
    <div class="pageTitle">
      <h4 class="text-warning">Payment Required</h4>
      <p>Your simulation access is pending due to incomplete payment.</p>
    </div>
    <p>Please complete your payment to continue.</p>
    <a href="{{ route('student.simulation.payment', ['simulation_id' => request('simulation_id')]) }}">
      Complete Payment
    </a>
  </div>
@endsection
