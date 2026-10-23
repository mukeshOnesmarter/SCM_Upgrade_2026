@extends('faculty.layout')
@section('title', 'Students')
@section('template')
  <div class="coursesTemplate">
    <div class="sectionContainer">
      <div class="sectionTitle">
        <h4>Enrolled Students for Simulation</h4>
        <p>View the list of students who have successfully enrolled for the simulation session. Manage their details and
          track participation seamlessly.</p>
      </div>
      @if (count($students) > 0)
        <div class="studentListing">
          @foreach ($students as $item)
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
              <div class="item courseTitle">
                <small>Simulation Joined:</small>
                {{-- {{ $item->simulation->title }} --}}
              </div>
              <div class="item action">
                <a href="{{ route('faculty.students.profile', $item->student->id) }}"><i class="fa fa-angle-right"
                    aria-hidden="true"></i></a>
              </div>
            </div>
          @endforeach
        </div>
        {{ $students->links() }}
      @else
        <p>No Record Found.</p>
      @endif
      <p>&nbsp;</p>
    </div>
  </div>
@endsection
