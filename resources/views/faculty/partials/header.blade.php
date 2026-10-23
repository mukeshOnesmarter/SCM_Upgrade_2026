<div class="header" id="header">
  <div class="item brandLogo">
    <a href="{{ route('faculty.dashboard') }}"> <img src="{{ URL::asset('images/logo-text.svg') }}" alt=""> </a>
  </div>
  <div class="item activeUser">
    <div class="item">Welcome {{ $sharedActiveUserData['first_name']}}!</div>
    <div class="item profileImage">
      @if (!empty($sharedActiveUserData->profile?->profile_picture))
        <a href="{{ route('faculty.profile.edit') }}"><img src="{{ URL::asset('storage/uploads/faculty/' . $sharedActiveUserData->profile?->profile_picture )}}" alt=""></a>
      @else
       <a href="{{ route('faculty.profile.edit') }}"><img src="{{ URL::asset('images/avatar-user.svg')}}" alt=""></a>
      @endif
    </div>
    <div class="item"><a href="{{ route('faculty.logout') }}">Log Out</a></div>
  </div>
  
</div>
