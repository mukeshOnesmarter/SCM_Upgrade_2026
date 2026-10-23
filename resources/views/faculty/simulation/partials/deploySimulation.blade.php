@include('faculty.simulation.partials.inviteStudent')
@include('faculty.simulation.partials.aiAnalysisControl')
<div class="studentInviteGroups">
  @include('faculty.simulation.partials.bulkInvite')
  @include('faculty.simulation.partials.shareUrl')
</div>

<div class="deploySimulation"> 
  @php 
    $deployButton = true; 
  @endphp

  @foreach ($simulation->simulationTeams as $team)
    @if ($team->teamMembers->isEmpty())
        @php
            $deployButton = false;
        @endphp
        @break
    @endif
  @endforeach
  
  @if (!$deployButton)
    <img src="{{ URL::asset('images/deploy-button-disabled.svg') }}" alt=""> 
  @elseif ($simulation->is_published_by_current_faculty == 'No')
   <a id="deploySimulation" href="{{ route('faculty.courses.deploySimulation', $simulation->id) }}"> 
    <img src="{{ URL::asset('images/deploy-button.svg') }}" alt=""> </a> 
  @else
    <img src="{{ URL::asset('images/deploy-button-success.svg') }}" alt="">
  @endif
</div>
  
