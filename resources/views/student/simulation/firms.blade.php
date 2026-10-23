@extends('student.simulation.layout')
@section('title', 'Firms')
@section('template')
  <div id="staticTemplate">
    <div class="pageTitle">
      <h4>Your Firms in Simulation</h4>
      <p>View all firms associated with your simulation, including their roles and contributions. Track firm details to
        understand their impact on the simulation process.</p>
    </div>
    <div class="firmDirectory">
      @foreach ($simulation->simulationFirms as $firm)
        <div class="tableRow">
          <div class="item">
            {{ $firm->firm_name_custome }}
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endsection
