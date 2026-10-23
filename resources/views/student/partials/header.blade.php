 <div class="header" id="header">
   <div class="item brandLogo">
     <a href="{{ route('student.dashboard') }}"> <img src="{{ URL::asset('images/logo-text.svg') }}" alt=""> </a>
   </div>
   <div class="item activeUser">
     <div class="item">Welcome {{ Str::ucfirst($sharedActiveUserData['first_name']) }}!</div>
     <div class="item">
       <a href="{{ route('student.dashboard') }}">
         <img src="{{ URL::asset('images/icon/dashboard.svg') }}" alt="" class="dashboardIcon"></a>
     </div>
     <a href="{{ route('student.profile.edit') }}">My Profile</a>
     <div class="item profileImage">
       @if (!empty($sharedActiveUserData->profile?->profile_picture)) 
          <img src="{{ URL::asset('storage/uploads/student/' . $sharedActiveUserData->profile?->profile_picture) }}" alt="">
       @else 
          <img src="{{ URL::asset('images/avatar-user.svg') }}" alt=""> 
       @endif
     </div>
     @if (session()->has('mimic_student_id'))
       <div class="item">
         <a href="{{ route('faculty.mimic.stop', ['simulation_id' => request('simulation_id'), 'student_id' => $sharedActiveUserData->id]) }}"
           class="text-danger" style="font-weight: bold;">
           Stop Mimic Mode
         </a>
       </div>
     @else
       <div class="item">
         <a href="{{ route('student.logout') }}">
           <img src="{{ URL::asset('images/icon/log-out.svg') }}" alt="" class="logoutIcon"></a>
       </div>
     @endif
   </div>
 </div>
