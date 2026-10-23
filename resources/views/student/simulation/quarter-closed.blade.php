@extends('student.layout')
@section('title', 'System Processing')
@section('template')
  <div id="staticTemplate">
    <div class="pageTitle">
      <h4>System Processing in Progress</h4>
      <p> Explore products linked to your simulation, analyze their market impact, and stay informed about key factors
        influencing your decisions.</p>
    </div>
    @if ($remainingTime)
      <h5>Quarter Ended: Access Temporarily Unavailable</h5>
      <p> The current phase has concluded, and the system is finalizing results and performing essential calculations to
        prepare for the next stage. <br> Access will be automatically restored once processing is complete. </p>
      <p style="font-size: 18px"> Estimated Time Remaining: <strong>{{ $remainingTime }}</strong> </p>
    @elseif ($isSyncing)
      <h5>Finalizing Simulation Data</h5>
      <p>The system has completed the scheduled phase but is still synchronizing simulation data and validating
        results.<br> This process may take a few additional moments. Please avoid refreshing repeatedly — access will
        resume automatically once synchronization is complete. </p>
    @elseif ($simulationInitialize)
      <h5>Simulation Setup in Progress</h5>
      <p>Your simulation is currently being prepared for this quarter. The system is configuring initial settings,
        allocating resources, and ensuring everything is ready for a smooth experience.<br>
        This usually takes a short while. Access will be enabled automatically once the setup is complete.</p>
    @endif
    <p>Thank you for your patience and understanding.</p>
  </div>
@endsection
