<div class="headerWrap">
  <div class="header">
    <div class="item logo">
      <a href="{{ route('admin.dashboard') }}"> <img src="{{ URL::asset('images/logo-full.svg') }}" alt=""> </a>
    </div>
    <div class="item">
      Welcome, <a
        href="{{ route('admin.profile.edit') }}">{{ $sharedActiveUserData['first_name'] . ' ' . $sharedActiveUserData['last_name'] }}</a>
      |
      <a href="{{ route('admin.logout') }}">Log Out</a>
    </div>
  </div>
</div>
