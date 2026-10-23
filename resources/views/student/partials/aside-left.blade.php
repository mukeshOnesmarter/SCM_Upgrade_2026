<div class="asideLeft">
  <div class="logo"> <a href="#"> <img src="{{ URL::asset('images/logo-student-admin.svg') }}" alt=""> </a>
  </div>
  <div class="manageProfile">
    <h4>MANAGE PROFILE</h4>
    <ul>
      <li><a class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
        href="{{ route('student.dashboard') }}">DASHBOARD</a></li>
      <li><a class="{{ request()->routeIs('student.invitations') ? 'active' : '' }}" 
        href="{{ route('student.invitations') }}">JOIN SIMULATIONS</a></li> 
      <li><a class="{{ request()->routeIs('student.simulation.index') ? 'active' : '' }}" 
        href="{{ route('student.simulation.index') }}">MY SIMULATIONS</a></li>
    </ul>
  </div>
  <div class="takeTour">
    <a href=""><img src="{{ URL::asset('images/take-tour.svg') }}" alt=""></a>
  </div>
</div>
