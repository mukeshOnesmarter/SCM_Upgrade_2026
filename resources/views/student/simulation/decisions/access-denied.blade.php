@extends('student.simulation.layout')
@section('title', 'Forecasting')
@section('template')
  <div class="decisionTemplate">
    <h4>Access Denied</h4>
    <p>You are trying to access a decision page that is not available in the current quarter.</p>
    <p>Please return to the previous page or continue with the actions that are allowed for this quarter.</p>
    <a
      href="{{ route('student.simulation.decisions.forecasting', [
          'simulation_id' => request()->route('simulation_id'),
          'quarter_id' => request()->route('quarter_id'),
      ]) }}">Click
      here to return to the previous page.</a>
  </div>
@endsection
@include('student.simulation.decisionsModal.forecasting')
