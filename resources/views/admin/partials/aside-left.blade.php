<div class="asideLeft">
  <div class="logo">
    <a href="#"> <img src="{{ URL::asset('images/logo-admin.svg') }}" alt=""> </a>
  </div>
  <div class="navigation">
    <ul>
      <li><a href="{{ route('admin.simulation.index') }}"
          class="{{ request()->routeIs('admin.simulation.*') ? 'active' : '' }}">SIMULATIONS / COURSES</a></li>
      @if ($sharedActiveUserData->role === 'super-admin')
        <li><a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">USER /
            ADMIN</a>
        </li>
      @endif
      <li><a href="{{ route('admin.faculties.index') }}"
          class="{{ request()->routeIs('admin.faculties.*') ? 'active' : '' }}">FACULTY</a></li>
      <li><a href="{{ route('admin.regions') }}"
          class="{{ request()->routeIs('admin.regions') ? 'active' : '' }}">REGION</a></li>
      <li><a href="{{ route('admin.firms') }}" class="{{ request()->routeIs('admin.firms') ? 'active' : '' }}">FIRM</a>
      </li>
      <li><a href="{{ route('admin.suppliers') }}"
          class="{{ request()->routeIs('admin.suppliers') ? 'active' : '' }}">SUPPLIER</a></li>
      <li><a href="{{ route('admin.products') }}"
          class="{{ request()->routeIs('admin.products') ? 'active' : '' }}">PRODUCT</a></li>
    </ul>
  </div>
</div>
