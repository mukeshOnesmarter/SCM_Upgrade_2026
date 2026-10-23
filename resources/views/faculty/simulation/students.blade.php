@extends('faculty.simulation.layout')
@section('title', 'Students')
@section('sectionTitle', 'Enrolled Students for Simulation')
@section('sectionDescription',
  'View the list of students who have successfully enrolled for the simulation session.
  Manage their details and track participation seamlessly.')
@section('inheritTemplate')
  @if ($students->isNotEmpty())
    <div class="studentEnroll">
      @foreach ($students as $item)
        <div class="listingRow">
          {{-- PROFILE IMAGE --}}
          <div class="item profilePicture">
            @php
              $profilePicture = optional($item->student->profile)->profile_picture;
            @endphp

            @if ($profilePicture)
              <img src="{{ asset('storage/uploads/student/' . $profilePicture) }}" alt="">
            @else
              <img src="{{ asset('images/avatar-user.svg') }}" alt="">
            @endif
          </div>

          {{-- STUDENT INFO --}}
          <div class="item title">
            <label>{{ $item->student->first_name }} {{ $item->student->last_name }}</label>
            <small>{{ $item->student->email }}</small>
            <small> Join Date:
              {{ \Carbon\Carbon::parse($item->student->join_date)->format('m/d/Y') }}
            </small>
          </div>
          <div class="item groupStatus">
            {{ $item->teamMember ? 'Group ' . $item->teamMember->simulation_team_id : 'Group Not Assigned' }}
          </div>
          <div class="item paymentStatus">
            @if ($item->teamMember)
              @if ($item->teamMember->payment_status === 'paid')
                <span class="success">Paid</span>
              @else
                <span class="warning">—</span>
              @endif
            @else
              <span class="text-muted">—</span>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @else
    <p>No students found.</p>
  @endif
@endsection
