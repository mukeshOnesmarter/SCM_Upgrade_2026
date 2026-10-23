@extends('student.layout')
@section('title', 'Simulation Joined')
@section('template')
  <div class="invitationContainer">
    <div class="pageTitle">
      <h4>Stay Ready for Your Upcoming Simulation</h4>
      <p>You’ve successfully enrolled in <strong>{{ $foundSimulation->title }}</strong>. The simulation begins soon — keep
        an eye out for updates!.</p>
    </div>
    <p>Start Date: {{ \Carbon\Carbon::parse($foundSimulation->start_date)->format('d F Y') }} </p>
  </div>
@endsection
