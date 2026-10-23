<div class="asideLeft">
  <div class="logo"> <a href="#"> <img src="{{ URL::asset('images/logo-faculty-admin.svg') }}" alt=""> </a>
  </div>


  @if (!session('faculty.has_course_access'))
    <div class="manageProfile">
      <h4>MANAGE PROFILE</h4>
      <ul>
        <li><a href="">UPDATE PROFILE</a></li>
        <li><a href="">CHANGE PASSWORD</a></li>
        <li><a href="">HELP / SUPPORT</a></li>
      </ul>
    </div>
    <div class="takeTour">
      <a href=""><img src="{{ URL::asset('images/take-tour.svg') }}" alt=""></a>
    </div>
  @else
    <div class="navigation">
      <ul>
        <li><a href="{{ route('faculty.dashboard') }}"
            class="{{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}">DASHBOARD</a></li>
        <li><a href="{{ route('faculty.courses.index') }}"
            class="{{ request()->routeIs('faculty.courses.*') ? 'active' : '' }}">COURSES / PROGRAMS</a></li>
        <li><a href="{{ route('faculty.students') }}"
            class="{{ request()->routeIs('faculty.students*') ? 'active' : '' }}">STUDENTS</a></li>
        <li><a href="{{ route('faculty.invitees') }}"
            class="{{ request()->routeIs('faculty.invitees') ? 'active' : '' }}">INVITEES</a></li>
      </ul>
    </div>
  @endif
</div>
