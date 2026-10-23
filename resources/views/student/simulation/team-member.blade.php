@extends('student.simulation.layout')
@section('title', 'Team Member')
@section('template')
  <div id="staticTemplate">
    <div class="pageTitle">
      <h4>Your Team Members in Simulation</h4>
      <p>Track your team members, view assigned roles, and monitor participation to ensure smooth collaboration throughout
        the
        simulation.</p>
    </div>
    @if ($teamMembers->isNotEmpty())
      <div class="studentListing">
        @foreach ($teamMembers as $item)
          @if ($item->student->id == $sharedActiveUserData['id'])
            @continue
          @endif
          <div class="listingRow">
            <div class="item profilePicture">
              @if (is_null($item->student->profile?->profile_picture))
                <img src="{{ URL::asset('images/avatar-user.svg') }}" alt="">
              @else
                <img src="{{ URL::asset('storage/uploads/student/' . $item->student->profile?->profile_picture) }}"
                  alt="">
              @endif
            </div>
            <div class="item title">
              <label for="heading">{{ $item->student->first_name . ' ' . $item->student->last_name }}</label>
              <small> <i class="fa fa-envelope-o" aria-hidden="true"></i> {{ $item->student->email }} </small>
              <small>Join Date: {{ \Carbon\Carbon::parse($item->student->join_date)->format('m/d/Y') }}</small>
            </div> 
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endsection
